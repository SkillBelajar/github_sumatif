<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LearningObjective;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        $objectives = LearningObjective::with('subject:id,name')
            ->latest()
            ->get()
            ->map(fn ($lo) => [
                'id' => $lo->id,
                'name' => \Illuminate\Support\Str::limit($lo->name, 80),
                'subject_name' => $lo->subject->name,
            ]);

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

        $questions = $query->latest()->paginate(20)->through(fn ($q) => [
            'id' => $q->id,
            'type' => $q->type,
            'type_label' => Question::TYPES[$q->type] ?? $q->type,
            'content' => \Illuminate\Support\Str::limit($q->content, 120),
            'phase' => $q->phase,
            'classes_tagged' => $q->classes_tagged,
            'options' => $q->options,
            'correct_answer' => $q->correct_answer,
            'subject_name' => $q->learningObjective->subject->name,
            'objective_name' => \Illuminate\Support\Str::limit($q->learningObjective->name, 80),
        ]);

        return Inertia::render('Teacher/QuestionBank/Index', [
            'questions' => $questions,
            'objectives' => $objectives,
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
            'types' => Question::TYPES,
            'phases' => Question::PHASES,
            'filters' => [
                'subject_id' => $request->query('subject_id'),
                'learning_objective_id' => $request->query('learning_objective_id'),
                'type' => $request->query('type'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateQuestion($request);

        Question::create($validated);

        return redirect()->back()->with('success', 'Soal berhasil ditambahkan.');
    }

    public function update(Request $request, Question $question)
    {
        $validated = $this->validateQuestion($request);

        $question->update($validated);

        return redirect()->back()->with('success', 'Soal berhasil diperbarui.');
    }

    public function destroy(Question $question)
    {
        $question->delete();

        return redirect()->back()->with('success', 'Soal berhasil dihapus.');
    }

    private function validateQuestion(Request $request): array
    {
        return $request->validate([
            'learning_objective_id' => 'required|exists:learning_objectives,id',
            'phase' => 'required|string|max:20',
            'classes_tagged' => 'nullable|array',
            'classes_tagged.*' => 'string|max:50',
            'type' => 'required|in:multiple_choice,true_false,short_answer,essay',
            'content' => 'required|string',
            'options' => 'nullable|array',
            'options.A' => 'required_if:type,multiple_choice|nullable|string',
            'options.B' => 'required_if:type,multiple_choice|nullable|string',
            'options.C' => 'nullable|string',
            'options.D' => 'nullable|string',
            // Esai/isian tak wajib punya kunci — dinilai guru manual.
            'correct_answer' => 'required_if:type,multiple_choice,true_false|nullable|string',
            'explanation' => 'nullable|string',
        ]);
    }
}
