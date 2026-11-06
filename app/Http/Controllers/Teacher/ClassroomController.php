<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClassroomController extends Controller
{
    public function index(Request $request)
    {
        $classrooms = Classroom::where('user_id', $request->user()->id)
            ->with('students:id,classroom_id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (Classroom $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'students' => $c->students
                    ->sortBy(fn (Student $s) => $s->name, SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->name]),
            ]);

        return inertia('Teacher/Classrooms/Index', ['classrooms' => $classrooms]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        Classroom::create(['user_id' => $request->user()->id, 'name' => trim($validated['name'])]);

        return back()->with('success', 'Kelas dibuat.');
    }

    public function destroy(Request $request, Classroom $classroom)
    {
        abort_unless($classroom->user_id === $request->user()->id, 403);

        // FK assessment_results.students memblok hapus bila siswa punya riwayat ujian.
        try {
            $classroom->delete();

            return back()->with('success', 'Kelas dihapus.');
        } catch (\PDOException) {
            return back()->with('error', 'Kelas tidak bisa dihapus: masih ada siswa dengan riwayat ujian.');
        }
    }

    public function storeStudent(Request $request, Classroom $classroom)
    {
        abort_unless($classroom->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $name = trim($validated['name']);

        if ($classroom->students()->where('name', $name)->exists()) {
            return back()->with('error', 'Siswa "' . $name . '" sudah ada di kelas ini.');
        }

        Student::create(['classroom_id' => $classroom->id, 'name' => $name]);

        return back()->with('success', 'Siswa ditambahkan.');
    }

    public function destroyStudent(Request $request, Classroom $classroom, $student)
    {
        abort_unless($classroom->user_id === $request->user()->id, 403);

        $student = Student::where('classroom_id', $classroom->id)->findOrFail($student);

        try {
            $student->delete();

            return back()->with('success', 'Siswa dihapus.');
        } catch (\PDOException) {
            return back()->with('error', 'Siswa tidak bisa dihapus: punya riwayat ujian.');
        }
    }

    /** Import siswa per kelas: CSV satu kolom "Nama", satu nama per baris. */
    public function importStudents(Request $request, Classroom $classroom)
    {
        abort_unless($classroom->user_id === $request->user()->id, 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        if ($handle === false) {
            return back()->with('error', 'Gagal membuka file.');
        }

        $imported = 0;
        $skipped = 0;
        $existing = $classroom->students()
            ->pluck('name')
            ->map(fn ($n) => mb_strtolower(trim($n)))
            ->flip();

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $name = trim((string) ($row[0] ?? ''));

                // Lewati header dan baris kosong.
                if ($name === '' || strcasecmp($name, 'Nama') === 0 || strcasecmp($name, 'Name') === 0) {
                    continue;
                }

                $key = mb_strtolower($name);
                if ($existing->has($key)) {
                    $skipped++;
                    continue;
                }

                Student::create(['classroom_id' => $classroom->id, 'name' => $name]);
                $existing[$key] = 1;
                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        fclose($handle);

        $message = "{$imported} siswa ditambahkan";
        if ($skipped > 0) {
            $message .= ", {$skipped} duplikat dilewati";
        }

        return back()->with('success', $message.'.');
    }
}
