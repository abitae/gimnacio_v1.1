<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoutineTemplateDayExercise extends Model
{
    public const METODOS = [
        'normal' => 'Normal',
        'superserie' => 'Superserie',
        'circuito' => 'Circuito',
        'drop_set' => 'Drop set',
    ];

    protected $fillable = [
        'routine_template_day_id',
        'exercise_id',
        'series',
        'repeticiones',
        'descanso_segundos',
        'tempo',
        'intensidad_rpe',
        'metodo',
        'notas',
        'orden',
    ];

    public function routineTemplateDay(): BelongsTo
    {
        return $this->belongsTo(RoutineTemplateDay::class, 'routine_template_day_id');
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function getMetodoLabelAttribute(): string
    {
        return self::METODOS[$this->metodo] ?? $this->metodo;
    }
}
