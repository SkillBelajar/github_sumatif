<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'assessment_result_id', 'question_id', 'student_answer', 'is_correct',
    ];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_result_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
