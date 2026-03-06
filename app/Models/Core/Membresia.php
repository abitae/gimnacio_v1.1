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
        'tipo_acceso',
        'max_visitas_dia',
        'permite_congelacion',
        'max_dias_congelacion',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'permite_congelacion' => 'boolean',
        ];
    }

    // Relaciones
    public function clienteMembresias(): HasMany
    {
        return $this->hasMany(ClienteMembresia::class);
    }
}
