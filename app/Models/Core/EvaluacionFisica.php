<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvaluacionFisica extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'peso',
        'estatura',
        'imc',
        'porcentaje_grasa',
        'porcentaje_musculo',
        'perimetros_corporales',
        'presion_arterial',
        'frecuencia_cardiaca',
        'observaciones',
        'evaluado_por',
    ];

    protected function casts(): array
    {
        return [
            'perimetros_corporales' => 'array',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function evaluadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }
}
