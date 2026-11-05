<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    public function run(): void
    {
        $subjects = [
            ['name' => 'Matematika', 'code' => 'MAT'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIN'],
            ['name' => 'Bahasa Inggris', 'code' => 'BIG'],
            ['name' => 'IPA', 'code' => 'IPA'],
            ['name' => 'IPS', 'code' => 'IPS'],
            ['name' => 'PPKn', 'code' => 'PKN'],
            ['name' => 'Informatika', 'code' => 'INF'],
            ['name' => 'Seni Budaya', 'code' => 'SBD'],
            ['name' => 'PJOK', 'code' => 'PJK'],
            ['name' => 'Pendidikan Agama', 'code' => 'PAI'],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(['name' => $subject['name']], $subject);
        }
    }
}
