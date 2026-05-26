<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use BelongsToSucursal;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'requiere_numero_operacion',
        'requiere_entidad',
        'estado',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'requiere_numero_operacion' => 'boolean',
            'requiere_entidad' => 'boolean',
            'sucursal_id' => 'integer',
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

    public function ventaPagos(): HasMany
    {
        return $this->hasMany(VentaPago::class, 'payment_method_id');
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
