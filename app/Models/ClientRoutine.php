<?php

namespace App\Models;

use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientRoutine extends Model
{
    use SoftDeletes;

    public const ESTADOS = [
        'activa' => 'Activa',
        'pausada' => 'Pausada',
        'finalizada' => 'Finalizada',
    ];

    protected $fillable = [
        'cliente_id',
        'routine_template_id',
        'trainer_user_id',
        'fecha_inicio',
        'fecha_fin',
        'objetivo_personal',
        'restricciones',
        'observaciones',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function routineTemplate(): BelongsTo
    {
        return $this->belongsTo(RoutineTemplate::class, 'routine_template_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_user_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(ClientRoutineDay::class, 'client_routine_id')->orderBy('orden');
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class, 'client_routine_id');
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
