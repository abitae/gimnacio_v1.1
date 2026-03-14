<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DiscountCoupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tipo_descuento',
        'valor_descuento',
        'fecha_inicio',
        'fecha_vencimiento',
        'cantidad_max_usos',
        'cantidad_usada',
        'aplica_a',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'valor_descuento' => 'decimal:2',
            'fecha_inicio' => 'date',
            'fecha_vencimiento' => 'date',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'discount_coupon_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo')
            ->whereDate('fecha_inicio', '<=', now()->toDateString())
            ->whereDate('fecha_vencimiento', '>=', now()->toDateString());
    }

    public function aplicaA(string $contexto): bool
    {
        if ($this->aplica_a === 'todos') {
            return true;
        }
        return $this->aplica_a === $contexto;
    }

    public function puedeUsarse(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }
        if (now()->toDateString() < $this->fecha_inicio->toDateString() || now()->toDateString() > $this->fecha_vencimiento->toDateString()) {
            return false;
        }
        if ($this->cantidad_max_usos !== null && $this->cantidad_usada >= $this->cantidad_max_usos) {
            return false;
        }
        return true;
    }

    public function calcularDescuento(float $subtotal): float
    {
        return min((float) $this->valor_descuento, $subtotal);
    }
}
