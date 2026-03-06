<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSessionSet extends Model
{
    protected $fillable = [
        'workout_session_exercise_id',
        'set_numero',
        'peso',
        'repeticiones',
        'rpe',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'peso' => 'decimal:2',
            'rpe' => 'decimal:1',
        ];
    }

    public function workoutSessionExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutSessionExercise::class, 'workout_session_exercise_id');
    }
}
