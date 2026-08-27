<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnrollmentInstallment extends Model
{
    use BelongsToSucursal;

    protected $table = 'enrollment_installments';

    protected $fillable = [
        'sucursal_id',
        'enrollment_installment_plan_id',
        'client_debt_id',
        'cliente_matricula_id',
        'numero_cuota',
        'monto',
        'monto_pagado',
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
            'monto_pagado' => 'decimal:2',
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

    public function clientDebt(): BelongsTo
    {
        return $this->belongsTo(ClientDebt::class, 'client_debt_id');
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

    public function pagos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Pago::class, 'enrollment_installment_id');
    }

    public function getMontoPagadoActualAttribute(): float
    {
        $monto = round((float) $this->monto, 2);
        $pagado = round((float) ($this->monto_pagado ?? 0), 2);

        if ($this->estado === 'pagada' && $pagado <= 0) {
            return $monto;
        }

        return round(min($monto, max(0, $pagado)), 2);
    }

    public function getSaldoPendienteAttribute(): float
    {
        return round(max(0, (float) $this->monto - $this->monto_pagado_actual), 2);
    }

    public function estaVencida(): bool
    {
        if ($this->estado === 'vencida') {
            return true;
        }

        if (in_array($this->estado, ['pendiente', 'parcial'], true) && $this->fecha_vencimiento?->isPast()) {
            return true;
        }

        return false;
    }

    public function fechaHoraUltimoPago(): ?Carbon
    {
        $latestPayment = $this->pagos
            ->filter(fn ($payment) => $payment?->fecha_pago)
            ->sortByDesc(fn ($payment) => $payment->fecha_pago?->timestamp ?? 0)
            ->first();

        if ($latestPayment) {
            return $latestPayment->fechaHoraPago();
        }

        if ($this->pago?->fecha_pago) {
            return $this->pago->fechaHoraPago();
        }

        return $this->fecha_pago ? Carbon::parse($this->fecha_pago) : null;
    }

    public function getDescuentoTotalAttribute(): float
    {
        $pagos = $this->relationLoaded('pagos')
            ? $this->pagos
            : $this->pagos()->get();

        return round((float) $pagos->sum('descuento_monto'), 2);
    }
}
