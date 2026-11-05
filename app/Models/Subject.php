<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    protected $fillable = ['user_id', 'name', 'code'];

    public function learningObjectives(): HasMany
    {
        return $this->hasMany(LearningObjective::class);
    }
}
