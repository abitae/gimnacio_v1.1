<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnrollmentInstallmentPlan extends Model
{
    protected $table = 'enrollment_installment_plans';

    protected $fillable = [
        'cliente_id',
        'cliente_matricula_id',
        'monto_total',
        'numero_cuotas',
        'monto_cuota',
        'frecuencia',
        'fecha_inicio',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'monto_cuota' => 'decimal:2',
            'fecha_inicio' => 'date',
        ];
    }

    public const FRECUENCIAS = [
        'semanal' => 'Semanal (7 días)',
        'quincenal' => 'Quincenal (15 días)',
        'mensual' => 'Mensual (30 días)',
        'anual' => 'Anual (360 días)',
        'personalizado' => 'Personalizado (30 días entre cuotas)',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clienteMatricula(): BelongsTo
    {
        return $this->belongsTo(ClienteMatricula::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(EnrollmentInstallment::class, 'enrollment_installment_plan_id');
    }

    public function getMontoPagadoAttribute(): float
    {
        return (float) $this->installments()->whereIn('estado', ['pagada', 'parcial'])->sum('monto');
    }

    public function getSaldoPendienteAttribute(): float
    {
        return (float) $this->monto_total - $this->monto_pagado;
    }
}
