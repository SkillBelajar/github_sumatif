<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssessmentSession;
use App\Models\Classroom;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AssessmentSessionController extends Controller
{
    public function index(Request $request)
    {
        $sessions = AssessmentSession::where('user_id', $request->user()->id)
            ->withCount(['classrooms', 'questions'])
            ->with('classrooms:id,name')
            ->latest()
            ->get()
            ->map(fn (AssessmentSession $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'duration_minutes' => $s->duration_minutes,
                'token' => $s->token,
                'status' => $s->status,
                'classrooms_count' => $s->classrooms_count,
                'questions_count' => $s->questions_count,
                'classroom_names' => $s->classrooms->pluck('name'),
            ]);

        return Inertia::render('Teacher/AssessmentSessions/Index', [
            'sessions' => $sessions,
            'classrooms' => Classroom::where('user_id', $request->user()->id)
                ->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'token' => ['nullable', 'string', 'min:5', 'max:16', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('assessment_sessions', 'token')],
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => 'string|max:100',
        ]);

        $session = AssessmentSession::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'duration_minutes' => $validated['duration_minutes'],
            'token' => isset($validated['token']) ? strtoupper($validated['token']) : AssessmentSession::generateToken(),
            'status' => 'draft',
        ]);

        $session->classrooms()->sync($this->resolveClassrooms($request->user()->id, $validated['classroom_ids'] ?? []));

        return redirect()->route('teacher.assessment-sessions.index')
            ->with('success', 'Sesi asesmen dibuat. Lanjut rakit soal dari daftar.');
    }

    public function update(Request $request, AssessmentSession $assessmentSession)
    {
        $this->authorizeOwner($request, $assessmentSession);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'duration_minutes' => 'required|integer|min:1|max:600',
            'token' => ['nullable', 'string', 'min:5', 'max:16', 'regex:/^[A-Za-z0-9]+$/', Rule::unique('assessment_sessions', 'token')->ignore($assessmentSession->id)],
            'classroom_ids' => 'nullable|array',
            'classroom_ids.*' => ['integer', Rule::exists('classrooms', 'id')->where('user_id', $request->user()->id)],
        ]);

        $assessmentSession->update([
            'name' => $validated['name'],
            'duration_minutes' => $validated['duration_minutes'],
            'token' => isset($validated['token']) ? strtoupper($validated['token']) : $assessmentSession->token,
        ]);

        $assessmentSession->classrooms()->sync(
            $this->resolveClassrooms($request->user()->id, $validated['classroom_ids'] ?? [])
        );

        return redirect()->route('teacher.assessment-sessions.index')
            ->with('success', 'Sesi asesmen diperbarui.');
    }

    public function destroy(Request $request, AssessmentSession $assessmentSession)
    {
        $this->authorizeOwner($request, $assessmentSession);

        $assessmentSession->delete();

        return redirect()->route('teacher.assessment-sessions.index')
            ->with('success', 'Sesi asesmen dihapus.');
    }

    public function questions(Request $request, AssessmentSession $assessmentSession)
    {
        $this->authorizeOwner($request, $assessmentSession);

        $session = [
            'id' => $assessmentSession->id,
            'name' => $assessmentSession->name,
            'duration_minutes' => $assessmentSession->duration_minutes,
            'token' => $assessmentSession->token,
            'status' => $assessmentSession->status,
            'selected_question_ids' => $assessmentSession->questions()->pluck('questions.id'),
        ];

        $query = Question::with('learningObjective.subject:id,name');

        if ($request->filled('subject_id')) {
            $query->whereHas('learningObjective', fn ($q) => $q->where('subject_id', $request->subject_id));
        }

        if ($request->filled('learning_objective_id')) {
            $query->where('learning_objective_id', $request->learning_objective_id);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $perPage = $request->filled("per_page") && $request->per_page === "all"
            ? $query->count() ?: 1
            : (int) ($request->per_page ?: 20);

        $questions = $query->latest()->paginate($perPage)->withQueryString()->through(fn (Question $q) => [
            'id' => $q->id,
            'type' => $q->type,
            'type_label' => Question::TYPES[$q->type] ?? $q->type,
            'content' => Str::limit($q->content, 140),
            'phase' => $q->phase,
            'subject_name' => $q->learningObjective->subject->name,
            'objective_name' => Str::limit($q->learningObjective->name, 80),
        ]);

        return Inertia::render('Teacher/AssessmentSessions/Questions', [
            'session' => $session,
            'questions' => $questions,
            'objectives' => \App\Models\LearningObjective::with('subject:id,name')
                ->latest()->get()
                ->map(fn ($lo) => [
                    'id' => $lo->id,
                    'name' => Str::limit($lo->name, 80),
                    'subject_id' => $lo->subject_id,
                    'subject_name' => $lo->subject->name,
                ]),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
            'types' => Question::TYPES,
            'filters' => [
                'subject_id' => $request->query('subject_id'),
                'learning_objective_id' => $request->query('learning_objective_id'),
                'type' => $request->query('type'),
                'per_page' => $request->query('per_page', '20'),
            ],
        ]);
    }

    public function syncQuestions(Request $request, AssessmentSession $assessmentSession)
    {
        $this->authorizeOwner($request, $assessmentSession);

        $validated = $request->validate([
            'question_ids' => 'required|array|min:1',
            'question_ids.*' => 'integer|exists:questions,id',
        ]);

        $assessmentSession->questions()->sync($validated['question_ids']);

        return redirect()->back()->with('success', 'Daftar soal sesi disimpan.');
    }

    public function activate(Request $request, AssessmentSession $assessmentSession)
    {
        $this->authorizeOwner($request, $assessmentSession);

        if ($assessmentSession->questions()->count() === 0) {
            return redirect()->back()->with('error', 'Sesi belum punya soal. Rakit soal dulu sebelum mengaktifkan.');
        }

        $assessmentSession->update(['status' => 'active']);

        return redirect()->back()->with('success', 'Ujian diaktifkan. Token siap dibagikan ke siswa.');
    }

    private function authorizeOwner(Request $request, AssessmentSession $session): void
    {
        abort_unless($session->user_id === $request->user()->id, 403);
    }

    /**
     * Vue kirim nama kelas (string). Buat kelas baru kalau belum ada, kembalikan ID.
     */
    private function resolveClassrooms(int $userId, array $names): array
    {
        return collect($names)
            ->filter()
            ->map(fn ($name) => Classroom::firstOrCreate(
                ['user_id' => $userId, 'name' => trim($name)],
            )->id)
            ->all();
    }
}
