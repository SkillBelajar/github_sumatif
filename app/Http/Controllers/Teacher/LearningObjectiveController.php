<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LearningObjective;
use App\Models\Subject;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LearningObjectiveController extends Controller
{
    public function index()
    {
        return Inertia::render('Teacher/LearningObjectives/Index', [
            'objectives' => LearningObjective::with('subject:id,name')
                ->latest()
                ->get()
                ->map(fn ($lo) => [
                    'id' => $lo->id,
                    'subject_id' => $lo->subject_id,
                    'subject_name' => $lo->subject->name,
                    'name' => $lo->name,
                    'kktp_rubric' => $lo->kktp_rubric,
                    'questions_count' => $lo->questions()->count(),
                ]),
            'subjects' => Subject::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string',
            'kktp_rubric' => 'nullable|array',
            'kktp_rubric.*.level' => 'required_with:kktp_rubric|string|max:100',
            'kktp_rubric.*.criteria' => 'nullable|string',
            'kktp_rubric.*.min_score' => 'nullable|numeric|min:0|max:100',
        ]);

        LearningObjective::create($validated);

        return redirect()->route('teacher.learning-objectives.index')
            ->with('success', 'Tujuan Pembelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, LearningObjective $learningObjective)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'name' => 'required|string',
            'kktp_rubric' => 'nullable|array',
            'kktp_rubric.*.level' => 'required_with:kktp_rubric|string|max:100',
            'kktp_rubric.*.criteria' => 'nullable|string',
            'kktp_rubric.*.min_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $learningObjective->update($validated);

        return redirect()->route('teacher.learning-objectives.index')
            ->with('success', 'Tujuan Pembelajaran berhasil diperbarui.');
    }

    public function destroy(LearningObjective $learningObjective)
    {
        $learningObjective->delete();

        return redirect()->route('teacher.learning-objectives.index')
            ->with('success', 'Tujuan Pembelajaran berhasil dihapus.');
    }
}
