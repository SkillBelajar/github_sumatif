<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class AssessmentSession extends Model
{
    protected const STATUSES = ['draft', 'active', 'closed'];

    protected $fillable = ['user_id', 'name', 'duration_minutes', 'token', 'status'];

    protected function casts(): array
    {
        return ['duration_minutes' => 'integer'];
    }

    public static function generateToken(): string
    {
        do {
            $token = strtoupper(Str::random(6));
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function classrooms(): BelongsToMany
    {
        return $this->belongsToMany(Classroom::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(AssessmentResult::class, 'assessment_session_id');
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class);
    }
}
