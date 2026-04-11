<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Clase extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo',
        'precio_sesion',
        'precio_paquete',
        'sesiones_paquete',
        'instructor_id',
        'estado',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'precio_sesion' => 'decimal:2',
            'precio_paquete' => 'decimal:2',
            'sesiones_paquete' => 'integer',
            'sucursal_id' => 'integer',
        ];
    }

    // Relaciones
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // Métodos de negocio
    public function obtenerPrecio(): float
    {
        return $this->tipo === 'paquete'
            ? (float) $this->precio_paquete
            : (float) $this->precio_sesion;
    }
}
