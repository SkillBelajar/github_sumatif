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

class Slice7Test extends TestCase
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

    /**
     * 2 TP: TP-1 punya 1 soal (benar), TP-2 punya 2 soal (1 benar).
     * Soal esai di TP-2 harus null (tidak dinilai otomatis).
     *
     * @return array{0: AssessmentSession, 1: Student, 2: AssessmentResult}
     */
    private function gradedSetup(): array
    {
        $teacher = $this->teacher();
        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MAT']);
        $lo1 = LearningObjective::create(['subject_id' => $subject->id, 'name' => 'TP-1']);
        $lo2 = LearningObjective::create(['subject_id' => $subject->id, 'name' => 'TP-2']);

        $q1 = Question::create([
            'learning_objective_id' => $lo1->id, 'phase' => 'Fase D', 'type' => 'multiple_choice',
            'content' => '2 + 2 = ?', 'options' => ['A' => '3', 'B' => '4'],
            'correct_answer' => 'B', 'explanation' => '2 ditambah 2 hasilnya 4.',
        ]);
        $q2 = Question::create([
            'learning_objective_id' => $lo2->id, 'phase' => 'Fase D', 'type' => 'multiple_choice',
            'content' => '5 x 3 = ?', 'options' => ['A' => '15', 'B' => '20'],
            'correct_answer' => 'A', 'explanation' => '5 dikali 3 hasilnya 15.',
        ]);
        $q3 = Question::create([
            'learning_objective_id' => $lo2->id, 'phase' => 'Fase D', 'type' => 'essay',
            'content' => 'Jelaskan pengurangan.', 'correct_answer' => null,
        ]);

        $class = Classroom::create(['user_id' => $teacher->id, 'name' => 'VII-A']);
        $student = Student::create(['classroom_id' => $class->id, 'name' => 'Ahmad']);

        $session = AssessmentSession::create([
            'user_id' => $teacher->id, 'name' => 'Sumatif 1', 'duration_minutes' => 30,
            'token' => 'ABC123', 'status' => 'active',
        ]);
        $session->classrooms()->attach($class->id);
        $session->questions()->attach([$q1->id, $q2->id, $q3->id]);

        $result = AssessmentResult::create([
            'assessment_session_id' => $session->id, 'student_id' => $student->id,
            'start_time' => now(), 'status' => 'in_progress',
        ]);
        AssessmentAnswer::create(['assessment_result_id' => $result->id, 'question_id' => $q1->id, 'student_answer' => 'B']);
        AssessmentAnswer::create(['assessment_result_id' => $result->id, 'question_id' => $q2->id, 'student_answer' => 'A']);
        AssessmentAnswer::create(['assessment_result_id' => $result->id, 'question_id' => $q3->id, 'student_answer' => 'kurang']);

        return [$session, $student, $result];
    }

    public function test_submit_grades_and_builds_scores_per_tp(): void
    {
        [$session, $student, $result] = $this->gradedSetup();

        $this->post('/ujian/'.$session->id.'/submit', ['student_id' => $student->id])
            ->assertRedirect(route('student.hasil', $result->id));

        $result->refresh();

        $this->assertSame('completed', $result->status);
        $this->assertCount(2, $result->scores_per_tp);

        $byName = collect($result->scores_per_tp)->keyBy('tp_name');

        // TP-1: 1/1 benar = 100, tuntas.
        $this->assertSame(100, $byName['TP-1']['score']);
        $this->assertTrue($byName['TP-1']['is_passed']);

        // TP-2: 1/2 benar (esai tidak dinilai) = 50, belum tuntas.
        $this->assertSame(50, $byName['TP-2']['score']);
        $this->assertFalse($byName['TP-2']['is_passed']);
        $this->assertSame(1, $byName['TP-2']['correct']);
        $this->assertSame(2, $byName['TP-2']['total']);

        // Soal esai tetap null, bukan false.
        $essay = $result->answers->firstWhere(fn ($a) => $a->question->type === 'essay');
        $this->assertNull($essay->is_correct);
    }

    public function test_feedback_page_shows_scores_and_explanation(): void
    {
        [$session, $student, $result] = $this->gradedSetup();
        $this->post('/ujian/'.$session->id.'/submit', ['student_id' => $student->id]);

        $this->get('/ujian/hasil/'.$result->id)
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Student/Feedback', false)
                ->where('version', '1.0 (Final)')
                ->has('result.scores_per_tp', 2)
                ->has('review', 3)
                // review diurutkan per question_id: q1 benar (TP-1), q2 benar, q3 esai.
                ->where('review.0.explanation', '2 ditambah 2 hasilnya 4.')
                ->where('review.0.is_correct', true)
                ->where('review.1.is_correct', true)
                // Esai -> butuh review guru.
                ->where('review.2.needs_review', true));
    }

    public function test_feedback_page_404_when_result_not_completed(): void
    {
        [, , $result] = $this->gradedSetup(); // masih in_progress

        $this->get('/ujian/hasil/'.$result->id)->assertNotFound();
    }
}
