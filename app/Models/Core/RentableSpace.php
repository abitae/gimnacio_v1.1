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
        'precio',
        'estado',
        'color_calendario',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'capacidad' => 'integer',
            'precio' => 'decimal:2',
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

    public function precioPos(): float
    {
        if ($this->precio !== null && (float) $this->precio > 0) {
            return (float) $this->precio;
        }

        $minRate = $this->relationLoaded('rates')
            ? $this->rates->min('precio')
            : $this->rates()->min('precio');

        if ($minRate !== null && (float) $minRate > 0) {
            return (float) $minRate;
        }

        throw new \InvalidArgumentException(
            "El espacio \"{$this->nombre}\" no tiene precio configurado. Asigne un precio al espacio en Alquileres > Espacios."
        );
    }

    /** @deprecated Use precioPos() */
    public function precioReferencialPos(): float
    {
        return $this->precioPos();
    }

    public function tienePrecioPos(): bool
    {
        if ($this->precio !== null && (float) $this->precio > 0) {
            return true;
        }

        if ($this->relationLoaded('rates')) {
            return $this->rates->contains(fn ($rate) => (float) $rate->precio > 0);
        }

        return $this->rates()->where('precio', '>', 0)->exists();
    }
}
