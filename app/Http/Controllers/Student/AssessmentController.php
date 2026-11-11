<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\AssessmentAnswer;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Student;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class AssessmentController extends Controller
{
    private const VERSION = '1.0 (Final)';

    /** Render ulang halaman akses ujian dengan pesan error. */
    private function accessWithError(string $message)
    {
        return back()->withErrors(['token' => $message]);
    }

    public function index(Request $request)
    {
        // Token dikirim dari landing page via query string: /ujian?token=ABC123
        // Halaman Access akan mengisi otomatis lalu mengecek lewat POST /ujian/check.
        return Inertia::render('Student/Access', [
            'version' => self::VERSION,
            'prefill' => strtoupper((string) $request->input('token')),
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
            return $this->accessWithError('Token tidak ditemukan atau ujian belum aktif.');
        }

        $students = $session->classrooms
            ->flatMap->students
            ->unique('id')
            ->sortBy('name')
            ->values()
            ->map(fn (Student $s) => ['id' => $s->id, 'name' => $s->name])
            ->all();

        if (empty($students)) {
            return $this->accessWithError('Belum ada siswa di kelas ujian ini.');
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

        // Cari percobaan yang sedang berjalan. Kalau tidak ada:
        // - belum pernah ujian -> mulai percobaan pertama
        // - sudah pernah selesai -> ujian ulang bebas, buat percobaan baru
        $result = AssessmentResult::where([
            'assessment_session_id' => $assessmentSession->id,
            'student_id' => $student->id,
            'status' => 'in_progress',
        ])->first();

        // PENYELAMATAN: percobaan tersangkut yang waktunya sudah habis
        // (tutup tab / matikan perangkat) -> kunci otomatis dengan nilai,
        // lalu siswa dapat percobaan baru. Tidak ada lagi error stuck.
        if (!is_null($result) && $result->start_time->copy()->addMinutes($assessmentSession->duration_minutes)->isPast()) {
            $this->finishExpired($result);
            $result = null; // paksa buat percobaan baru di bawah
        }

        if (is_null($result)) {
            $latest = AssessmentResult::where([
                'assessment_session_id' => $assessmentSession->id,
                'student_id' => $student->id,
            ])->orderByDesc('id')->first();

            $result = AssessmentResult::create([
                'assessment_session_id' => $assessmentSession->id,
                'student_id' => $student->id,
                'attempt' => ($latest->attempt ?? 0) + 1,
                'is_latest' => true,
                'start_time' => now(),
                'status' => 'in_progress',
            ]);

            // Percobaan lama tidak lagi jadi terbaru.
            AssessmentResult::where('assessment_session_id', $assessmentSession->id)
                ->where('student_id', $student->id)
                ->where('id', '!=', $result->id)
                ->update(['is_latest' => false]);
        }

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

    /** Kunci ujian + auto-grading per TP. */
    public function submit(Request $request, AssessmentSession $assessmentSession)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
        ]);

        // submit menilai percobaan yang sedang berjalan apa adanya, sekalipun
        // waktunya sudah lewat (mis. siswa menutup tab lalu buka lagi lalu submit).
        // Tidak membuat percobaan baru di sini — cukup kunci & nilai.
        $result = AssessmentResult::where([
            'assessment_session_id' => $assessmentSession->id,
            'student_id' => $validated['student_id'],
            'status' => 'in_progress',
        ])->first();

        if (is_null($result)) {
            abort(409, 'Tidak ada percobaan ujian yang sedang berjalan untuk dikumpulkan.');
        }

        DB::transaction(function () use ($result, $assessmentSession) {
            $result->load('answers.question.learningObjective:id,name');

            // Tipe yang bisa dinilai otomatis. Esai/isian -> null (review guru).
            $gradable = [Question::TYPE_MC, Question::TYPE_TF];

            foreach ($result->answers as $answer) {
                $question = $answer->question;
                $isCorrect = null;

                if ($question && in_array($question->type, $gradable, true)) {
                    $isCorrect = trim((string) $answer->student_answer) === trim((string) $question->correct_answer);
                }

                $answer->update(['is_correct' => $isCorrect]);
            }

            $result->update([
                'status' => 'completed',
                'end_time' => now(),
                // Hitung setelah update jawaban — collection $result->answers sudah stale.
                'scores_per_tp' => $this->scoresPerTp($result),
            ]);
        });

        return redirect()->route('student.hasil', $result->id);
    }

    /**
     * Rekap nilai per TP: (benar / jumlah soal TP) * 100, tuntas bila >= ambang.
     * Soal tanpa TP tetap dihitung sebagai satu kelompok agar total tidak hilang.
     */
    private function scoresPerTp(AssessmentResult $result): array
    {
        $threshold = (int) config('asesmen.pass_threshold', 70);

        // Query terpisah: collection $result->answers bisa stale setelah update batch.
        $answers = $result->answers()
            ->with('question.learningObjective:id,name')
            ->get();

        return $answers
            ->groupBy(fn ($a) => $a->question?->learning_objective_id)
            ->map(function ($group, $tpId) use ($threshold) {
                $total = $group->count();
                $correct = $group->where('is_correct', true)->count();
                $score = $total > 0 ? (int) round($correct / $total * 100) : 0;
                $tp = $group->first()->question?->learningObjective;

                return [
                    'tp_id' => $tpId,
                    'tp_name' => $tp?->name ?? 'Tanpa Tujuan Pembelajaran',
                    'score' => $score,
                    'is_passed' => $score >= $threshold,
                    'correct' => $correct,
                    'total' => $total,
                ];
            })
            ->values()
            ->all();
    }

    /** Umpan balik instan: soal + jawaban siswa + kunci + pembahasan. */
    public function feedback(AssessmentResult $assessmentResult)
    {
        abort_unless($assessmentResult->status === 'completed', 404);

        $assessmentResult->load(['answers.question.learningObjective:id,name', 'student:id,name']);
        $assessmentSession = $assessmentResult->session;

        return Inertia::render('Student/Feedback', [
            'version' => self::VERSION,
            'session' => ['id' => $assessmentSession->id, 'name' => $assessmentSession->name],
            'result' => [
                'id' => $assessmentResult->id,
                'student_id' => $assessmentResult->student_id,
                'student_name' => $assessmentResult->student?->name,
                'status' => $assessmentResult->status,
                'end_time' => $assessmentResult->end_time?->toIso8601String(),
                'scores_per_tp' => $assessmentResult->scores_per_tp ?? [],
            ],
            'review' => $assessmentResult->answers
                ->sortBy(fn ($a) => $a->question?->id)
                ->map(fn ($a, $i) => [
                    'number' => $i + 1,
                    'question_id' => $a->question_id,
                    'content' => $a->question?->content,
                    'type' => $a->question?->type,
                    'type_label' => Question::TYPES[$a->question?->type] ?? $a->question?->type,
                    'objective' => $a->question?->learningObjective?->name,
                    'options' => $a->question?->options,
                    'student_answer' => $a->student_answer,
                    'correct_answer' => $a->question?->correct_answer,
                    'is_correct' => $a->is_correct,
                    'needs_review' => is_null($a->is_correct),
                    'explanation' => $a->question?->explanation,
                ])
                ->values()
                ->all(),
        ]);
    }

    private function activeResult(AssessmentSession $session, int $studentId, bool $createNew = false): AssessmentResult
    {
        $result = AssessmentResult::where([
            'assessment_session_id' => $session->id,
            'student_id' => $studentId,
            'status' => 'in_progress',
        ])->first();

        if (is_null($result)) {
            // Ujian ulang bebas: kalau percobaan sebelumnya sudah selesai, buat percobaan baru.
            if ($createNew && $session->results()->where('student_id', $studentId)->where('status', 'completed')->exists()) {
                $latest = $this->latestResult($session, $studentId);
                $nextAttempt = ($latest->attempt ?? 0) + 1;

                $result = AssessmentResult::create([
                    'assessment_session_id' => $session->id,
                    'student_id' => $studentId,
                    'attempt' => $nextAttempt,
                    'is_latest' => true,
                    'start_time' => now(),
                    'status' => 'in_progress',
                ]);

                // Percobaan lama tidak lagi jadi terbaru.
                AssessmentResult::where('assessment_session_id', $session->id)
                    ->where('student_id', $studentId)
                    ->where('id', '!=', $result->id)
                    ->update(['is_latest' => false]);
            } else {
                abort(409, 'Ujian belum dimulai atau sudah dikumpulkan.');
            }
        }

        abort_if($result->start_time->copy()->addMinutes($session->duration_minutes)->isPast(), 410, 'Waktu ujian sudah habis.');

        return $result;
    }

    /**
     * Koreksi otomatis percobaan yang kedaluwarsa: pakai logika grading yang
     * sama dengan submit() supaya format skor identik. Jawaban tak terjawab
     * dianggap salah (bukan error), lalu siswa dapat percobaan baru.
     */
    private function finishExpired(AssessmentResult $result): void
    {
        $result->load('answers.question.learningObjective:id,name');
        $gradable = [Question::TYPE_MC, Question::TYPE_TF];

        foreach ($result->answers as $answer) {
            $question = $answer->question;
            $isCorrect = null;
            if ($question && in_array($question->type, $gradable, true)) {
                $isCorrect = trim((string) $answer->student_answer) === trim((string) $question->correct_answer);
            }
            $answer->update(['is_correct' => $isCorrect]);
        }

        $result->update([
            'status' => 'completed',
            'end_time' => now(),
            'scores_per_tp' => $this->scoresPerTp($result),
        ]);
    }

    /** Percobaan terbaru siswa pada suatu sesi. */
    private function latestResult(AssessmentSession $session, int $studentId): ?AssessmentResult
    {
        return AssessmentResult::where([
            'assessment_session_id' => $session->id,
            'student_id' => $studentId,
        ])->orderByDesc('id')->first();
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
                // Pertahankan key huruf (A/B/C/D) agar sama dengan correct_answer.
                // shuffle() Collection membuang key -> shuffle manual pada key.
                'options' => $q->options
                    ? collect(array_keys($q->options))->shuffle()
                        ->mapWithKeys(fn ($k) => [$k => $q->options[$k]])->all()
                    : null,
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
