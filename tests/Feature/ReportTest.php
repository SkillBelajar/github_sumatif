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

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function teacher(): User
    {
        return User::create([
            'name' => 'Guru', 'email' => 'g'.uniqid().'@t.local', 'password' => 'password',
            'role' => 'teacher', 'school_name' => 'S', 'email_verified_at' => now(),
        ]);
    }

    /**
     * 1 sesi, 2 siswa, 2 TP:
     * - TP-1 (1 soal): S1 benar, S2 salah
     * - TP-2 (2 soal): S1 1 benar, S2 0 benar
     */
    protected function buildScenario(): array
    {
        $teacher = $this->teacher();
        $subject = Subject::create(['name' => 'Matematika', 'code' => 'MAT']);
        $lo1 = LearningObjective::create(['subject_id' => $subject->id, 'name' => 'TP-1']);
        $lo2 = LearningObjective::create(['subject_id' => $subject->id, 'name' => 'TP-2']);

        $q1 = Question::create([
            'learning_objective_id' => $lo1->id, 'phase' => 'Fase D', 'type' => 'multiple_choice',
            'content' => '2+2=?', 'options' => ['A' => '3', 'B' => '4'], 'correct_answer' => 'B',
        ]);
        $q2 = Question::create([
            'learning_objective_id' => $lo2->id, 'phase' => 'Fase D', 'type' => 'multiple_choice',
            'content' => '3x3=?', 'options' => ['A' => '9', 'B' => '6'], 'correct_answer' => 'A',
        ]);
        $q3 = Question::create([
            'learning_objective_id' => $lo2->id, 'phase' => 'Fase D', 'type' => 'multiple_choice',
            'content' => '10-4=?', 'options' => ['A' => '6', 'B' => '7'], 'correct_answer' => 'A',
        ]);

        $class = Classroom::create(['user_id' => $teacher->id, 'name' => 'VII-A']);
        $s1 = Student::create(['classroom_id' => $class->id, 'name' => 'Ahmad']);
        $s2 = Student::create(['classroom_id' => $class->id, 'name' => 'Budi']);

        $session = AssessmentSession::create([
            'user_id' => $teacher->id, 'name' => 'Sumatif', 'duration_minutes' => 30,
            'token' => 'ABC123', 'status' => 'active',
        ]);
        $session->classrooms()->attach($class->id);
        $session->questions()->attach([$q1->id, $q2->id, $q3->id]);

        $r1 = AssessmentResult::create([
            'assessment_session_id' => $session->id, 'student_id' => $s1->id,
            'start_time' => now(), 'status' => 'completed',
            'scores_per_tp' => [
                ['tp_id' => $lo1->id, 'tp_name' => 'TP-1', 'score' => 100, 'is_passed' => true, 'correct' => 1, 'total' => 1],
                ['tp_id' => $lo2->id, 'tp_name' => 'TP-2', 'score' => 50, 'is_passed' => false, 'correct' => 1, 'total' => 2],
            ],
        ]);
        $r2 = AssessmentResult::create([
            'assessment_session_id' => $session->id, 'student_id' => $s2->id,
            'start_time' => now(), 'status' => 'completed',
            'scores_per_tp' => [
                ['tp_id' => $lo1->id, 'tp_name' => 'TP-1', 'score' => 0, 'is_passed' => false, 'correct' => 0, 'total' => 1],
                ['tp_id' => $lo2->id, 'tp_name' => 'TP-2', 'score' => 0, 'is_passed' => false, 'correct' => 0, 'total' => 2],
            ],
        ]);

        foreach ([$r1, $r2] as $r) {
            foreach ([$q1, $q2, $q3] as $q) {
                AssessmentAnswer::create([
                    'assessment_result_id' => $r->id, 'question_id' => $q->id, 'student_answer' => 'A',
                ]);
            }
        }

        return [$session, $teacher, $lo1, $lo2];
    }

    public function test_results_page_shows_summary_and_dynamic_columns(): void
    {
        [$session, $teacher] = $this->buildScenario();

        $this->actingAs($teacher)->get('/teacher/assessments/'.$session->id.'/results')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Teacher/Reports/Results', false)
                ->where('summary.total_students', 2)
                // rata-rata nilai akhir: (75 + 0) / 2 = 37.5 -> 38
                ->where('summary.average', 38)
                ->has('tpColumns', 2)
                ->has('rows', 2)
                ->where('rows.0.student_name', 'Ahmad')
                ->where('rows.0.final_score', 75)
                ->where('rows.1.student_name', 'Budi')
                ->where('rows.1.final_score', 0));
    }

    public function test_summary_counts_passed_vs_failed_per_tp(): void
    {
        [$session, $teacher] = $this->buildScenario();

        $this->actingAs($teacher)->get('/teacher/assessments/'.$session->id.'/results')
            ->assertInertia(fn ($page) => $page
                ->where('summary.per_tp.0.passed', 1)
                ->where('summary.per_tp.0.failed', 1)
                ->where('summary.per_tp.1.passed', 0)
                ->where('summary.per_tp.1.failed', 2));
    }

    public function test_export_csv_contains_student_class_and_tp_scores(): void
    {
        [$session, $teacher] = $this->buildScenario();

        $response = $this->actingAs($teacher)->get('/teacher/assessments/'.$session->id.'/results/export');

        $response->assertOk();
        $csv = $response->streamedContent();
        ob_start();
        echo $csv;
        $csv = ob_get_clean();

        $this->assertStringContainsString('"Nama Siswa",Kelas', $csv);
        $this->assertStringContainsString('Ahmad,VII-A,"100 ✓","50 *",75', $csv);
        $this->assertStringContainsString('Budi,VII-A,"0 *","0 *",0', $csv);
    }

    public function test_other_teacher_cannot_view_results(): void
    {
        [$session] = $this->buildScenario();
        $outsider = $this->teacher();

        $this->actingAs($outsider)->get('/teacher/assessments/'.$session->id.'/results')->assertForbidden();
        $this->actingAs($outsider)->get('/teacher/assessments/'.$session->id.'/results/export')->assertForbidden();
    }
}
