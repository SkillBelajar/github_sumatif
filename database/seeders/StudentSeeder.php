<?php

namespace Database\Seeders;

use App\Models\AssessmentSession;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Uji coba Slice 6: kelas + siswa + sesi aktif berisi 3 soal.
     * Aman dipanggil berulang (firstOrCreate).
     */
    public function run(): void
    {
        $teacher = \App\Models\User::where('role', 'teacher')->first();

        if (!$teacher) {
            return;
        }

        $class = Classroom::firstOrCreate(
            ['user_id' => $teacher->id, 'name' => 'VII-A'],
        );

        foreach (['Ahmad Fauzi', 'Dewi Lestari', 'Rizky Pratama'] as $name) {
            Student::firstOrCreate(['classroom_id' => $class->id, 'name' => $name]);
        }

        $questions = Question::latest()->take(3)->get();

        if ($questions->isEmpty()) {
            return;
        }

        $session = AssessmentSession::firstOrCreate(
            ['token' => 'UJI001'],
            [
                'user_id' => $teacher->id,
                'name' => 'Sumatif Tengah Semester Ganjil',
                'duration_minutes' => 45,
                'status' => 'active',
            ]
        );

        $session->classrooms()->sync([$class->id]);
        $session->questions()->sync($questions->pluck('id'));
    }
}
