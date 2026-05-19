<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RentableSpace extends Model
{
    use BelongsToSucursal;
    use HasFactory, SoftDeletes;

    protected $table = 'rentable_spaces';

    protected $fillable = [
        'nombre',
        'tipo',
        'descripcion',
        'capacidad',
        'estado',
        'color_calendario',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'sucursal_id' => 'integer',
        ];
    }

    /** Colores para el calendario (valor => etiqueta) */
    public const COLORES_CALENDARIO = [
        '#3B82F6' => 'Azul',
        '#10B981' => 'Verde',
        '#F59E0B' => 'Ámbar',
        '#EF4444' => 'Rojo',
        '#8B5CF6' => 'Violeta',
        '#EC4899' => 'Rosa',
        '#06B6D4' => 'Cian',
        '#6366F1' => 'Índigo',
        '#84CC16' => 'Lima',
        '#F97316' => 'Naranja',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(RentableSpaceRate::class, 'rentable_space_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class, 'rentable_space_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function precioReferencialPos(): float
    {
        $minRate = $this->rates()->min('precio');
        if ($minRate !== null) {
            return (float) $minRate;
        }

        return (float) config('pos.alquiler_precio_referencial', 20);
    }
}
