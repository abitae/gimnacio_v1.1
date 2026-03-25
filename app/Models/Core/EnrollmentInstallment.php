<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentInstallment extends Model
{
    protected $table = 'enrollment_installments';

    protected $fillable = [
        'enrollment_installment_plan_id',
        'cliente_matricula_id',
        'numero_cuota',
        'monto',
        'fecha_vencimiento',
        'estado',
        'payment_method_id',
        'numero_operacion',
        'pago_id',
        'fecha_pago',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_vencimiento' => 'date',
            'fecha_pago' => 'date',
        ];
    }

    public const ESTADOS = [
        'pendiente' => 'Pendiente',
        'pagada' => 'Pagada',
        'vencida' => 'Vencida',
        'parcial' => 'Parcial',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(EnrollmentInstallmentPlan::class, 'enrollment_installment_plan_id');
    }

    public function clienteMatricula(): BelongsTo
    {
        return $this->belongsTo(ClienteMatricula::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(Pago::class);
    }

    public function estaVencida(): bool
    {
        return $this->estado === 'vencida' || ($this->estado === 'pendiente' && $this->fecha_vencimiento->isPast());
    }
}
