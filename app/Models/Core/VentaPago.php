<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaPago extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'venta_pagos';

    protected $fillable = [
        'venta_id',
        'payment_method_id',
        'monto',
        'metodo_pago',
        'numero_operacion',
        'entidad_financiera',
        'pagado_en',
        'usuario_id',
        'caja_id',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'pagado_en' => 'datetime',
            'sucursal_id' => 'integer',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }
}
