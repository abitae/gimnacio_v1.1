<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\System\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ClientDebt extends Model
{
    use BelongsToSucursal;
    use SoftDeletes;

    protected $table = 'client_debts';

    protected $fillable = [
        'cliente_id',
        'sucursal_id',
        'venta_id',
        'origen_tipo',
        'origen_id',
        'tipo_deuda',
        'referencia',
        'plan_fecha_inicio',
        'plan_fecha_fin',
        'monto_total',
        'monto_pagado',
        'saldo_pendiente',
        'fecha_registro',
        'fecha_vencimiento',
        'estado',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'monto_total' => 'decimal:2',
            'monto_pagado' => 'decimal:2',
            'saldo_pendiente' => 'decimal:2',
            'fecha_registro' => 'date',
            'fecha_vencimiento' => 'date',
            'plan_fecha_inicio' => 'date',
            'plan_fecha_fin' => 'date',
            'sucursal_id' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'client_debt_id');
    }

    public function enrollmentInstallments(): HasMany
    {
        return $this->hasMany(EnrollmentInstallment::class, 'client_debt_id');
    }

    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['pendiente', 'parcial', 'vencido']);
    }

    public function normalizedOrigenTipo(): string
    {
        return Str::lower(Str::ascii(trim((string) $this->origen_tipo)));
    }

    public function isMembershipDebt(): bool
    {
        $origin = $this->normalizedOrigenTipo();

        return str_contains($origin, 'membresia') || str_contains($origin, 'matricula');
    }

    public function isPosDebt(): bool
    {
        $origin = $this->normalizedOrigenTipo();

        return $origin === '' || str_contains($origin, 'pos') || str_contains($origin, 'venta');
    }

    public function operationalBucket(): string
    {
        return $this->isMembershipDebt() ? 'membresia' : 'producto';
    }

    public function operationalOriginLabel(): string
    {
        $origin = $this->normalizedOrigenTipo();

        return match (true) {
            str_contains($origin, 'matricula') => 'Matricula',
            str_contains($origin, 'membresia') => 'Membresia',
            str_contains($origin, 'alquiler') => 'Alquiler',
            $this->isPosDebt() => 'POS',
            default => $this->origen_tipo ? Str::title((string) $this->origen_tipo) : 'Cuenta por cobrar',
        };
    }

    public function operationalDetailLabel(): string
    {
        return $this->isMembershipDebt()
            ? 'Cuenta por cobrar de membresia'
            : 'Cuenta por cobrar POS';
    }
}
