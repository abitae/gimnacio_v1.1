<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkoutSession extends Model
{
    use SoftDeletes;

    public const ESTADOS = [
        'iniciada' => 'Iniciada',
        'completada' => 'Completada',
    ];

    protected $fillable = [
        'client_routine_id',
        'client_routine_day_id',
        'fecha_hora',
        'estado',
        'notas',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function clientRoutine(): BelongsTo
    {
        return $this->belongsTo(ClientRoutine::class, 'client_routine_id');
    }

    public function clientRoutineDay(): BelongsTo
    {
        return $this->belongsTo(ClientRoutineDay::class, 'client_routine_day_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function sessionExercises(): HasMany
    {
        return $this->hasMany(WorkoutSessionExercise::class, 'workout_session_id')->orderBy('orden');
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
