<?php

namespace Tests\Feature;

use App\Models\AssessmentAnswer;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Classroom;
use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Slice6Test extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::create([
            'name' => 'Guru Test',
            'email' => 'guru'.uniqid().'@test.local',
            'password' => 'password',
            'role' => 'teacher',
            'school_name' => 'Sekolah Test',
            'email_verified_at' => now(),
        ]);
    }

    private function sessionWithStudent(string $token = 'ABC123', string $status = 'active'): array
    {
        $teacher = $this->teacher();
        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MAT']);
        $lo = LearningObjective::create([
            'subject_id' => $subject->id,
            'name' => 'Operasi hitung',
            'kktp_rubric' => [['level' => 'Baik', 'min_score' => 80, 'criteria' => 'Tepat']],
        ]);
        $q1 = Question::create([
            'learning_objective_id' => $lo->id, 'phase' => 'Fase D', 'type' => 'multiple_choice',
            'content' => '2 + 2 = ?', 'options' => ['A' => '3', 'B' => '4'], 'correct_answer' => 'B',
        ]);
        $q2 = Question::create([
            'learning_objective_id' => $lo->id, 'phase' => 'Fase D', 'type' => 'short_answer',
            'content' => '5 - 3 = ?', 'correct_answer' => '2',
        ]);

        $class = Classroom::create(['user_id' => $teacher->id, 'name' => 'VII-A']);
        $student = Student::create(['classroom_id' => $class->id, 'name' => 'Ahmad']);

        $session = AssessmentSession::create([
            'user_id' => $teacher->id,
            'name' => 'Sumatif 1',
            'duration_minutes' => 30,
            'token' => $token,
            'status' => $status,
        ]);
        $session->classrooms()->attach($class->id);
        $session->questions()->attach([$q1->id, $q2->id]);

        return [$session, $student];
    }

    public function test_check_token_returns_student_list(): void
    {
        [$session] = $this->sessionWithStudent();

        $this->post('/ujian/check', ['token' => 'ABC123'])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Access', false)
                ->has('students', 1)
                ->where('sessionInfo.name', 'Sumatif 1')
                ->where('sessionInfo.question_count', 2));
    }

    public function test_check_token_rejects_inactive_session(): void
    {
        $this->sessionWithStudent(status: 'draft');

        $this->post('/ujian/check', ['token' => 'ABC123'])
            ->assertSessionHasErrors('token');

        $this->assertSame(0, AssessmentResult::count());
    }

    public function test_prepare_blocks_student_not_in_assigned_class(): void
    {
        [$session] = $this->sessionWithStudent();
        $otherClass = Classroom::create(['user_id' => $session->user_id, 'name' => 'IX-C']);
        $outsider = Student::create(['classroom_id' => $otherClass->id, 'name' => 'Bocor']);

        $this->get('/ujian/'.$session->id.'/prepare?student_id='.$outsider->id)
            ->assertForbidden();
    }

    public function test_start_sets_start_time_and_is_idempotent(): void
    {
        [$session, $student] = $this->sessionWithStudent();

        $this->post('/ujian/'.$session->id.'/start', ['student_id' => $student->id])->assertOk();
        $first = AssessmentResult::firstOrFail();

        $this->post('/ujian/'.$session->id.'/start', ['student_id' => $student->id])->assertOk();

        $this->assertSame(1, AssessmentResult::count());
        $this->assertSame($first->id, AssessmentResult::first()->id);
        $this->assertSame('in_progress', $first->status);
        $this->assertNotNull($first->start_time);
    }

    public function test_answer_autosave_upserts(): void
    {
        [$session, $student] = $this->sessionWithStudent();
        $result = AssessmentResult::create([
            'assessment_session_id' => $session->id,
            'student_id' => $student->id,
            'start_time' => now(),
            'status' => 'in_progress',
        ]);
        $question = $session->questions->first();

        $this->postJson('/ujian/'.$session->id.'/answer', [
            'student_id' => $student->id, 'question_id' => $question->id, 'student_answer' => 'A',
        ])->assertOk();
        $this->postJson('/ujian/'.$session->id.'/answer', [
            'student_id' => $student->id, 'question_id' => $question->id, 'student_answer' => 'B',
        ])->assertOk();

        $this->assertSame('B', AssessmentAnswer::first()->student_answer);
        $this->assertSame(1, AssessmentAnswer::count());
    }

    public function test_answer_rejected_for_question_not_in_session(): void
    {
        [$session, $student] = $this->sessionWithStudent();
        AssessmentResult::create([
            'assessment_session_id' => $session->id,
            'student_id' => $student->id,
            'start_time' => now(),
            'status' => 'in_progress',
        ]);

        $this->postJson('/ujian/'.$session->id.'/answer', [
            'student_id' => $student->id, 'question_id' => 999999, 'student_answer' => 'x',
        ])->assertJsonValidationErrors('question_id');
    }

    public function test_submit_marks_completed_and_scores(): void
    {
        [$session, $student] = $this->sessionWithStudent();
        $result = AssessmentResult::create([
            'assessment_session_id' => $session->id,
            'student_id' => $student->id,
            'start_time' => now(),
            'status' => 'in_progress',
        ]);
        [$q1, $q2] = $session->questions->sortBy('id')->values();
        AssessmentAnswer::create([
            'assessment_result_id' => $result->id, 'question_id' => $q1->id, 'student_answer' => 'B',
        ]);
        AssessmentAnswer::create([
            'assessment_result_id' => $result->id, 'question_id' => $q2->id, 'student_answer' => '99',
        ]);

        $this->post('/ujian/'.$session->id.'/submit', ['student_id' => $student->id])
            ->assertRedirect(route('student.hasil', $result->id));

        $result->refresh();
        $this->assertSame('completed', $result->status);
        $this->assertNotNull($result->end_time);
        // PG dinilai otomatis: 'B' == kunci 'B'.
        $this->assertTrue($result->answers->firstWhere('question_id', $q1->id)->is_correct);
        // Isian singkat: tidak dinilai otomatis -> null (menunggu review guru).
        $this->assertNull($result->answers->firstWhere('question_id', $q2->id)->is_correct);
    }

    public function test_submit_and_answer_blocked_after_completed(): void
    {
        [$session, $student] = $this->sessionWithStudent();
        AssessmentResult::create([
            'assessment_session_id' => $session->id,
            'student_id' => $student->id,
            'start_time' => now(),
            'status' => 'completed',
        ]);

        $this->postJson('/ujian/'.$session->id.'/answer', [
            'student_id' => $student->id, 'question_id' => $session->questions->first()->id, 'student_answer' => 'A',
        ])->assertStatus(409);

        $this->postJson('/ujian/'.$session->id.'/submit', ['student_id' => $student->id])->assertStatus(409);
    }

    public function test_answer_blocked_after_deadline(): void
    {
        [$session, $student] = $this->sessionWithStudent();
        AssessmentResult::create([
            'assessment_session_id' => $session->id,
            'student_id' => $student->id,
            'start_time' => now()->subMinutes($session->duration_minutes + 5),
            'status' => 'in_progress',
        ]);

        $this->postJson('/ujian/'.$session->id.'/answer', [
            'student_id' => $student->id, 'question_id' => $session->questions->first()->id, 'student_answer' => 'A',
        ])->assertStatus(410);
    }

    public function test_attempt_page_returns_randomized_questions_with_saved_answers(): void
    {
        [$session, $student] = $this->sessionWithStudent();
        $result = AssessmentResult::create([
            'assessment_session_id' => $session->id,
            'student_id' => $student->id,
            'start_time' => now(),
            'status' => 'in_progress',
        ]);
        AssessmentAnswer::create([
            'assessment_result_id' => $result->id,
            'question_id' => $session->questions->first()->id,
            'student_answer' => 'B',
        ]);

        $this->post('/ujian/'.$session->id.'/start', ['student_id' => $student->id])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Attempt', false)
                ->has('attempt.questions', 2)
                ->has('attempt.answers'));
    }
}
