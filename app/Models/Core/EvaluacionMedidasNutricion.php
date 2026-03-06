<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionMedidasNutricion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_medidas_nutricion';

    protected $fillable = [
        'cliente_id',
        'peso',
        'estatura',
        'imc',
        'porcentaje_grasa',
        'porcentaje_musculo',
        'masa_muscular',
        'masa_grasa',
        'masa_osea',
        'masa_residual',
        'circunferencias',
        'presion_arterial',
        'frecuencia_cardiaca',
        'objetivo',
        'nutricionista_id',
        'fecha_proxima_evaluacion',
        'estado',
        'observaciones',
        'evaluado_por',
    ];

    protected function casts(): array
    {
        return [
            'circunferencias' => 'array',
            'fecha_proxima_evaluacion' => 'date',
            'peso' => 'decimal:2',
            'estatura' => 'decimal:2',
            'imc' => 'decimal:2',
            'porcentaje_grasa' => 'decimal:2',
            'porcentaje_musculo' => 'decimal:2',
            'masa_muscular' => 'decimal:2',
            'masa_grasa' => 'decimal:2',
            'masa_osea' => 'decimal:2',
            'masa_residual' => 'decimal:2',
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

    public function evaluadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluado_por');
    }

    public function citas(): HasMany
    {
        return $this->hasMany(Cita::class, 'evaluacion_medidas_nutricion_id');
    }

    // Métodos de ayuda
    public function calcularIMC(): ?float
    {
        if ($this->peso && $this->estatura && $this->estatura > 0) {
            return round($this->peso / ($this->estatura * $this->estatura), 2);
        }
        return null;
    }

    public function getComposicionCorporalAttribute(): array
    {
        return [
            'masa_muscular' => [
                'porcentaje' => $this->porcentaje_musculo ?? 0,
                'kg' => $this->masa_muscular ?? 0,
            ],
            'masa_grasa' => [
                'porcentaje' => $this->porcentaje_grasa ?? 0,
                'kg' => $this->masa_grasa ?? 0,
            ],
            'masa_osea' => [
                'porcentaje' => 0,
                'kg' => $this->masa_osea ?? 0,
            ],
            'masa_residual' => [
                'porcentaje' => 0,
                'kg' => $this->masa_residual ?? 0,
            ],
        ];
    }

    public function getCircunferenciasAttribute($value): array
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }
}
