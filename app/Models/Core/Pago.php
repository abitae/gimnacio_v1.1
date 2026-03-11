<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'cliente_membresia_id',
        'cliente_matricula_id',
        'monto',
        'moneda',
        'metodo_pago',
        'payment_method_id',
        'numero_operacion',
        'entidad_financiera',
        'fecha_pago',
        'es_pago_parcial',
        'saldo_pendiente',
        'comprobante_tipo',
        'comprobante_numero',
        'registrado_por',
        'caja_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'datetime',
            'es_pago_parcial' => 'boolean',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clienteMembresia(): BelongsTo
    {
        return $this->belongsTo(ClienteMembresia::class);
    }

    public function clienteMatricula(): BelongsTo
    {
        return $this->belongsTo(ClienteMatricula::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }
}
