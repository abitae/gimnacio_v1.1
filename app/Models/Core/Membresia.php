<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membresia extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'descripcion',
        'duracion_dias',
        'precio_base',
        'permite_cuotas',
        'numero_cuotas_default',
        'frecuencia_cuotas_default',
        'cuota_inicial_monto',
        'cuota_inicial_porcentaje',
        'tipo_acceso',
        'max_visitas_dia',
        'permite_congelacion',
        'max_dias_congelacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'precio_base' => 'decimal:2',
            'cuota_inicial_monto' => 'decimal:2',
            'cuota_inicial_porcentaje' => 'decimal:2',
            'permite_cuotas' => 'boolean',
            'permite_congelacion' => 'boolean',
        ];
    }

    // Relaciones
    public function clienteMembresias(): HasMany
    {
        return $this->hasMany(ClienteMembresia::class);
    }
}
