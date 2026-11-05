<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::create([
            'name' => 'Guru', 'email' => 'g'.uniqid().'@t.local', 'password' => 'password',
            'role' => 'teacher', 'school_name' => 'S', 'email_verified_at' => now(),
        ]);
    }

    public function test_index_lists_only_own_classrooms(): void
    {
        $me = $this->teacher();
        $other = $this->teacher();
        Classroom::create(['user_id' => $me->id, 'name' => 'VII-A']);
        Classroom::create(['user_id' => $other->id, 'name' => 'IX-Z']);

        $this->actingAs($me)->get('/teacher/classrooms')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Classrooms/Index', false)
                ->has('classrooms', 1)
                ->where('classrooms.0.name', 'VII-A'));
    }

    public function test_add_student_manually(): void
    {
        $me = $this->teacher();
        $class = Classroom::create(['user_id' => $me->id, 'name' => 'VII-A']);

        $this->actingAs($me)->post('/teacher/classrooms/'.$class->id.'/students', ['name' => 'Budi'])
            ->assertRedirect();

        $this->assertSame(1, $class->students()->count());
        $this->assertSame('Budi', $class->students()->first()->name);
    }

    public function test_import_csv_adds_students_and_skips_duplicates(): void
    {
        $me = $this->teacher();
        $class = Classroom::create(['user_id' => $me->id, 'name' => 'VII-A']);
        Student::create(['classroom_id' => $class->id, 'name' => 'Budi']);

        $csv = "Nama\nBudi\nSiti\nAni\nbudi\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('siswa.csv', $csv);

        $this->actingAs($me)->post('/teacher/classrooms/'.$class->id.'/students/import', ['file' => $file])
            ->assertRedirect();

        // Budi (2x, beda huruf besar) dilewati, Siti & Ani masuk.
        $names = $class->students()->pluck('name')->sort()->values()->all();
        $this->assertSame(['Ani', 'Budi', 'Siti'], $names);
    }

    public function test_cannot_add_student_to_other_teachers_classroom(): void
    {
        $me = $this->teacher();
        $other = Classroom::create(['user_id' => $this->teacher()->id, 'name' => 'Bukan Milik Saya']);

        $this->actingAs($me)->post('/teacher/classrooms/'.$other->id.'/students', ['name' => 'Bocor'])
            ->assertForbidden();

        $this->assertSame(0, $other->students()->count());
    }
}
