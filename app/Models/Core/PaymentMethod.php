<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'requiere_numero_operacion',
        'requiere_entidad',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'requiere_numero_operacion' => 'boolean',
            'requiere_entidad' => 'boolean',
        ];
    }

    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'payment_method_id');
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class, 'payment_method_id');
    }

    public function getRequiereNumeroOperacionAttribute($value): bool
    {
        return (bool) $value;
    }

    public function getRequiereEntidadAttribute($value): bool
    {
        return (bool) $value;
    }
}
