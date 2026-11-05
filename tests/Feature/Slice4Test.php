<?php

namespace Tests\Feature;

use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class Slice4Test extends TestCase
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

    private function subject(): Subject
    {
        return Subject::create(['name' => 'Matematika', 'code' => 'MAT']);
    }

    public function test_teacher_can_create_learning_objective_with_kktp_rubric(): void
    {
        $subject = $this->subject();

        $this->actingAs($this->teacher())
            ->post('/teacher/learning-objectives', [
                'subject_id' => $subject->id,
                'name' => 'Menyelesaikan operasi hitung bilangan bulat.',
                'kktp_rubric' => [
                    ['level' => 'Perlu Bimbingan', 'min_score' => 60, 'criteria' => 'Belum tepat'],
                    ['level' => 'Baik', 'min_score' => 80, 'criteria' => 'Tepat'],
                ],
            ])
            ->assertRedirect(route('teacher.learning-objectives.index'));

        $lo = LearningObjective::first();
        $this->assertNotNull($lo);
        $this->assertSame($subject->id, $lo->subject_id);
        $this->assertCount(2, $lo->kktp_rubric);
        $this->assertSame('Baik', $lo->kktp_rubric[1]['level']);
    }

    public function test_teacher_can_create_multiple_choice_question(): void
    {
        $lo = LearningObjective::create(['subject_id' => $this->subject()->id, 'name' => 'TP 1']);

        $this->actingAs($this->teacher())
            ->post('/teacher/question-bank', [
                'learning_objective_id' => $lo->id,
                'phase' => 'Fase D',
                'type' => 'multiple_choice',
                'content' => 'Hasil dari 2 + 3 x 4 adalah...',
                'options' => ['A' => '14', 'B' => '20', 'C' => '24', 'D' => '11'],
                'correct_answer' => 'A',
                'explanation' => 'Perkalian lebih dulu.',
            ])
            ->assertRedirect();

        $q = Question::first();
        $this->assertSame('multiple_choice', $q->type);
        $this->assertSame('A', $q->correct_answer);
        $this->assertSame('14', $q->options['A']);
    }

    public function test_validation_rejects_multiple_choice_without_options(): void
    {
        $lo = LearningObjective::create(['subject_id' => $this->subject()->id, 'name' => 'TP 1']);

        $this->actingAs($this->teacher())
            ->post('/teacher/question-bank', [
                'learning_objective_id' => $lo->id,
                'phase' => 'Fase D',
                'type' => 'multiple_choice',
                'content' => 'Soal tanpa opsi',
                'correct_answer' => 'A',
            ])
            ->assertSessionHasErrors(['options.A', 'options.B']);

        $this->assertSame(0, Question::count());
    }

    public function test_import_csv_multiple_choice(): void
    {
        $lo = LearningObjective::create(['subject_id' => $this->subject()->id, 'name' => 'TP Import']);

        $csv = "TP_ID,Fase,Tipe,Soal,Opsi_A,Opsi_B,Opsi_C,Opsi_D,Kunci_Jawaban,Pembahasan\n"
            ."{$lo->id},Fase D,multiple_choice,\"Berapa 5+5?\",10,11,12,13,A,\"5+5=10\"\n"
            ."{$lo->id},Fase D,true_false,\"2 adalah bilangan prima terkecil.\",,,, ,benar,\n";

        $file = UploadedFile::fake()->createWithContent('soal.csv', $csv);

        $this->actingAs($this->teacher())
            ->post('/teacher/question-bank/import', ['file' => $file])
            ->assertSessionHas('success');

        $this->assertSame(2, Question::count());
        $mc = Question::where('type', 'multiple_choice')->first();
        $this->assertSame('A', $mc->correct_answer);
        $this->assertSame('10', $mc->options['A']);
    }

    public function test_import_skips_rows_with_invalid_tp_and_reports_error(): void
    {
        $lo = LearningObjective::create(['subject_id' => $this->subject()->id, 'name' => 'TP Valid']);

        $csv = "TP_ID,Fase,Tipe,Soal,Opsi_A,Opsi_B,Opsi_C,Opsi_D,Kunci_Jawaban,Pembahasan\n"
            ."99999,Fase D,multiple_choice,\"Soal TP salah\",a,b,c,d,A,\n"
            ."{$lo->id},Fase D,short_answer,\"Berapa 25-10?\",,,, ,15,\n";

        $file = UploadedFile::fake()->createWithContent('soal.csv', $csv);

        $this->actingAs($this->teacher())
            ->post('/teacher/question-bank/import', ['file' => $file])
            ->assertSessionHas('success')
            ->assertSessionHas('import_errors');

        $this->assertSame(1, Question::count());
        $this->assertSame('short_answer', Question::first()->type);
    }

    public function test_question_bank_filterable_by_subject_and_type(): void
    {
        $mat = Subject::create(['name' => 'Matematika']);
        $ipa = Subject::create(['name' => 'IPA']);

        $loMat = LearningObjective::create(['subject_id' => $mat->id, 'name' => 'TP Mat']);
        $loIpa = LearningObjective::create(['subject_id' => $ipa->id, 'name' => 'TP IPA']);

        Question::create(['learning_objective_id' => $mat->id, 'phase' => 'Fase D', 'type' => 'multiple_choice', 'content' => 'Soal Mat PG', 'correct_answer' => 'A', 'options' => ['A' => 'x', 'B' => 'y']]);
        Question::create(['learning_objective_id' => $loMat->id, 'phase' => 'Fase D', 'type' => 'essay', 'content' => 'Soal Mat Esai', 'correct_answer' => 'jawaban']);
        Question::create(['learning_objective_id' => $ipa->id, 'phase' => 'Fase D', 'type' => 'multiple_choice', 'content' => 'Soal IPA PG', 'correct_answer' => 'A', 'options' => ['A' => 'x', 'B' => 'y']]);

        $response = $this->actingAs($this->teacher())
            ->get('/teacher/question-bank?subject_id='.$mat->id.'&type=multiple_choice')
            ->assertOk();

        $props = $response->viewData('page')['props'];
        $this->assertCount(1, $props['questions']['data']);
        $this->assertSame('Soal Mat PG', $props['questions']['data'][0]['content']);
    }

    public function test_template_download_returns_csv(): void
    {
        $response = $this->actingAs($this->teacher())
            ->get('/teacher/question-bank/template')
            ->assertOk();

        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
    }

    public function test_guest_cannot_access_teacher_modules(): void
    {
        $this->get('/teacher/learning-objectives')->assertRedirect('/login');
        $this->get('/teacher/question-bank')->assertRedirect('/login');
    }
}
