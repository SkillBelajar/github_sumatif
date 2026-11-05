<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class AssessmentController extends Controller
{
    private const VERSION = '0.16';

    public function index()
    {
        return Inertia::render('Student/Access', [
            'version' => self::VERSION,
        ]);
    }

    /** Cek token: kembalikan daftar siswa kelas yang di-assign ke sesi. */
    public function checkToken(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|size:6',
        ]);

        $session = AssessmentSession::where('token', strtoupper($validated['token']))
            ->where('status', 'active')
            ->with(['classrooms.students:id,classroom_id,name', 'questions:id'])
            ->first();

        if (!$session) {
            return back()->withErrors(['token' => 'Token tidak ditemukan atau ujian belum aktif.']);
        }

        $students = $session->classrooms
            ->flatMap->students
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->name])
            ->all();

        if (empty($students)) {
            return back()->withErrors(['token' => 'Belum ada siswa di kelas ujian ini.']);
        }

        return Inertia::render('Student/Access', [
            'version' => self::VERSION,
            'sessionInfo' => $session->only(['id', 'name', 'duration_minutes', 'token'])
                + ['question_count' => $session->questions->count(), 'kktp' => $this->collectKktp($session)],
            'students' => $students,
        ]);
    }

    public function prepare(Request $request, AssessmentSession $assessmentSession)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        abort_unless(
            $assessmentSession->status === 'active'
            && $assessmentSession->classrooms->contains($student->classroom_id),
            403
        );

        return Inertia::render('Student/Prepare', [
            'version' => self::VERSION,
            'session' => [
                'id' => $assessmentSession->id,
                'name' => $assessmentSession->name,
                'duration_minutes' => $assessmentSession->duration_minutes,
                'token' => $assessmentSession->token,
                'question_count' => $assessmentSession->questions()->count(),
                'kktp' => $this->collectKktp($assessmentSession),
            ],
            'student' => ['id' => $student->id, 'name' => $student->name],
        ]);
    }

    /** Mulai / lanjutkan pengerjaan. Idempoten: tab kedua tetap satu result. */
    public function start(Request $request, AssessmentSession $assessmentSession)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        abort_unless(
            $assessmentSession->status === 'active'
            && $assessmentSession->classrooms->contains($student->classroom_id),
            403
        );

        $result = AssessmentResult::firstOrCreate(
            ['assessment_session_id' => $assessmentSession->id, 'student_id' => $student->id],
            ['start_time' => now(), 'status' => 'in_progress']
        );

        $saved = $result->answers()->pluck('student_answer', 'question_id');

        return Inertia::render('Student/Attempt', [
            'version' => self::VERSION,
            'attempt' => $this->payload($assessmentSession, $result, $saved->all()),
        ]);
    }

    /** Autosave satu jawaban. Dipanggil setiap siswa memilih/mengetik. */
    public function saveAnswer(Request $request, AssessmentSession $assessmentSession)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'question_id' => ['required', 'integer', Rule::exists('questions', 'id')],
            'student_answer' => 'nullable|string',
        ]);

        $result = $this->activeResult($assessmentSession, $validated['student_id']);

        abort_unless(
            $assessmentSession->questions()->where('questions.id', $validated['question_id'])->exists(),
            422
        );

        AssessmentAnswer::updateOrCreate(
            ['assessment_result_id' => $result->id, 'question_id' => $validated['question_id']],
            ['student_answer' => $validated['student_answer']]
        );

        return response()->json(['ok' => true]);
    }

    /** Kunci ujian: status completed + nilai benar/salah per soal. */
    public function submit(Request $request, AssessmentSession $assessmentSession)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
        ]);

        $result = $this->activeResult($assessmentSession, $validated['student_id']);

        DB::transaction(function () use ($result, $assessmentSession) {
            $correct = $assessmentSession->questions()->pluck('correct_answer', 'questions.id')->all();

            foreach ($result->answers as $answer) {
                $answer->update([
                    'is_correct' => array_key_exists($answer->question_id, $correct)
                        && trim((string) $answer->student_answer) === trim((string) $correct[$answer->question_id]),
                ]);
            }

            $result->update(['status' => 'completed', 'end_time' => now()]);
        });

        return Inertia::render('Student/Finish', [
            'version' => self::VERSION,
            'session' => ['id' => $assessmentSession->id, 'name' => $assessmentSession->name],
        ]);
    }

    private function activeResult(AssessmentSession $session, int $studentId): AssessmentResult
    {
        $result = AssessmentResult::where([
            'assessment_session_id' => $session->id,
            'student_id' => $studentId,
            'status' => 'in_progress',
        ])->first();

        abort_if(is_null($result), 409, 'Ujian belum dimulai atau sudah dikumpulkan.');
        abort_if($result->start_time->copy()->addMinutes($session->duration_minutes)->isPast(), 410, 'Waktu ujian sudah habis.');

        return $result;
    }

    /** Soal diacak server-side; seed dari result id supaya urutan stabil saat refresh. */
    private function payload(AssessmentSession $session, AssessmentResult $result, array $saved): array
    {
        $questions = $session->questions()
            ->with('learningObjective:id,name')
            ->get()
            ->shuffle()
            ->values()
            ->map(fn ($q) => [
                'id' => $q->id,
                'number' => 0,
                'type' => $q->type,
                'type_label' => \App\Models\Question::TYPES[$q->type] ?? $q->type,
                'content' => $q->content,
                'options' => $q->options,
                'objective' => $q->learningObjective?->name,
            ]);

        $deadline = $result->start_time->copy()->addMinutes($session->duration_minutes);

        return [
            'result_id' => $result->id,
            'session_id' => $session->id,
            'student_id' => $result->student_id,
            'student_name' => $result->student->name,
            'session_name' => $session->name,
            'duration_minutes' => $session->duration_minutes,
            'started_at' => $result->start_time->toIso8601String(),
            // Sumber tunggal waktu: server. Frontend hanya menghitung selisih.
            'server_now' => now()->toIso8601String(),
            'deadline_at' => $deadline->toIso8601String(),
            'remaining_seconds' => max(0, now()->diffInSeconds($deadline, false)),
            'questions' => $questions->map(fn ($q, $i) => $q + ['number' => $i + 1])->all(),
            'answers' => $saved,
        ];
    }

    /** Ambil rubrik KKTP dari TP soal terpilih, unik per level. */
    private function collectKktp(AssessmentSession $session): array
    {
        return $session->questions()
            ->with('learningObjective:id,name,kktp_rubric')
            ->get()
            ->pluck('learningObjective')
            ->filter()
            ->flatMap(fn ($lo) => collect($lo->kktp_rubric ?? [])
                ->map(fn ($r) => [
                    'objective' => $lo->name,
                    'level' => $r['level'] ?? null,
                    'min_score' => $r['min_score'] ?? null,
                    'criteria' => $r['criteria'] ?? null,
                ]))
            ->unique('level')
            ->values()
            ->all();
    }
}
