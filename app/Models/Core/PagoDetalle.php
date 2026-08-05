<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoDetalle extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'pago_detalles';

    protected $fillable = [
        'pago_id',
        'payment_method_id',
        'monto',
        'metodo_pago',
        'numero_operacion',
        'entidad_financiera',
        'caja_id',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'sucursal_id' => 'integer',
        ];
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }
}
