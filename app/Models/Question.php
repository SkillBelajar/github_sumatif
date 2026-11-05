<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Question extends Model
{
    public const TYPES = [
        'multiple_choice' => 'Pilihan Ganda',
        'true_false' => 'Benar-Salah',
        'short_answer' => 'Isian Singkat',
        'essay' => 'Esai',
    ];

    public const TYPE_MC = 'multiple_choice';
    public const TYPE_TF = 'true_false';

    public const PHASES = ['Fase A', 'Fase B', 'Fase C', 'Fase D', 'Fase E', 'Fase F'];

    protected $fillable = [
        'learning_objective_id', 'phase', 'classes_tagged',
        'type', 'content', 'options', 'correct_answer', 'explanation',
    ];

    protected function casts(): array
    {
        return [
            'classes_tagged' => 'array',
            'options' => 'array',
        ];
    }

    public function learningObjective(): BelongsTo
    {
        return $this->belongsTo(LearningObjective::class);
    }
}
