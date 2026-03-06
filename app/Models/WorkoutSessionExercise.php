<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSessionExercise extends Model
{
    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'client_routine_day_exercise_id',
        'orden',
    ];

    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class, 'workout_session_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function clientRoutineDayExercise(): BelongsTo
    {
        return $this->belongsTo(ClientRoutineDayExercise::class, 'client_routine_day_exercise_id');
    }

    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutSessionSet::class, 'workout_session_exercise_id')->orderBy('set_numero');
    }
}
