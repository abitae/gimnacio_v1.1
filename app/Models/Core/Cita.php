<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cita extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'tipo',
        'fecha_hora',
        'duracion_minutos',
        'nutricionista_id',
        'trainer_user_id',
        'estado',
        'observaciones',
        'evaluacion_medidas_nutricion_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function nutricionista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutricionista_id');
    }

    public function trainerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_user_id');
    }

    public function evaluacionMedidasNutricion(): BelongsTo
    {
        return $this->belongsTo(EvaluacionMedidasNutricion::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function seguimientosNutricion(): HasMany
    {
        return $this->hasMany(SeguimientoNutricion::class);
    }

    // Métodos
    public function estaDisponible(): bool
    {
        return in_array($this->estado, ['programada', 'confirmada']);
    }

    public function puedeCancelar(): bool
    {
        return in_array($this->estado, ['programada', 'confirmada', 'en_curso']);
    }
}
