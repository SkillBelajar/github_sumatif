<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
        $subjects = Subject::where('user_id', $request->user()->id)
            ->withCount('learningObjectives')
            ->orderBy('name')
            ->get()
            ->map(fn (Subject $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'code' => $s->code,
                'objectives_count' => $s->learning_objectives_count,
            ]);

        return inertia('Teacher/Subjects/Index', ['subjects' => $subjects]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:20'],
        ]);

        $name = trim($validated['name']);

        if (Subject::where('user_id', $request->user()->id)->where('name', $name)->exists()) {
            return back()->with('error', 'Mata pelajaran "'.$name.'" sudah ada.');
        }

        Subject::create([
            'user_id' => $request->user()->id,
            'name' => $name,
            'code' => trim($validated['code'] ?? ''),
        ]);

        return back()->with('success', 'Mata pelajaran ditambahkan.');
    }

    public function destroy(Request $request, Subject $subject)
    {
        abort_unless($subject->user_id === $request->user()->id, 403);

        try {
            $subject->delete();

            return back()->with('success', 'Mata pelajaran dihapus.');
        } catch (\PDOException) {
            return back()->with('error', 'Mata pelajaran tidak bisa dihapus: masih dipakai TP/soal.');
        }
    }
}
