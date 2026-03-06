<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'venta_id',
        'tipo_item',
        'item_id',
        'nombre_item',
        'cantidad',
        'precio_unitario',
        'descuento',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'precio_unitario' => 'decimal:2',
            'descuento' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    // Relaciones
    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    // Relaciones polimórficas
    public function producto()
    {
        return $this->tipo_item === 'producto' 
            ? $this->belongsTo(Producto::class, 'item_id')
            : null;
    }

    public function servicio()
    {
        return $this->tipo_item === 'servicio' 
            ? $this->belongsTo(ServicioExterno::class, 'item_id')
            : null;
    }

    public function clase()
    {
        return $this->tipo_item === 'clase' 
            ? $this->belongsTo(Clase::class, 'item_id')
            : null;
    }
}
