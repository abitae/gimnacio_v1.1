<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pago extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'cliente_membresia_id',
        'cliente_matricula_id',
        'enrollment_installment_id',
        'client_debt_id',
        'monto',
        'descuento_monto',
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
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_pago' => 'datetime',
            'descuento_monto' => 'decimal:2',
            'es_pago_parcial' => 'boolean',
            'sucursal_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $pago): void {
            if ($pago->fecha_pago === null) {
                $pago->fecha_pago = now();

                return;
            }

            $pago->fecha_pago = static::normalizeFechaPago($pago->fecha_pago);
        });
    }

    /**
     * Si la fecha viene solo con día (00:00), asigna la hora del registro.
     */
    public static function normalizeFechaPago(mixed $fecha): Carbon
    {
        $parsed = $fecha instanceof Carbon ? $fecha->copy() : Carbon::parse($fecha);

        if ($parsed->format('H:i:s') === '00:00:00') {
            return $parsed->setTimeFrom(now());
        }

        return $parsed;
    }

    /**
     * Fecha/hora para comprobantes: usa created_at si fecha_pago no tiene hora.
     */
    public function fechaHoraPago(): Carbon
    {
        if ($this->fecha_pago === null) {
            return $this->created_at ?? now();
        }

        if ($this->fecha_pago->format('H:i:s') === '00:00:00' && $this->created_at !== null) {
            return $this->fecha_pago->copy()->setTimeFrom($this->created_at);
        }

        return $this->fecha_pago;
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

    public function enrollmentInstallment(): BelongsTo
    {
        return $this->belongsTo(EnrollmentInstallment::class);
    }

    public function clientDebt(): BelongsTo
    {
        return $this->belongsTo(ClientDebt::class);
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

    public function detalles(): HasMany
    {
        return $this->hasMany(PagoDetalle::class)->orderBy('id');
    }

    public function metodosPagoResumen(): string
    {
        $detalles = $this->relationLoaded('detalles') ? $this->detalles : $this->detalles()->with('paymentMethod')->get();

        if ($detalles->isEmpty()) {
            return $this->paymentMethod?->nombre ?? $this->metodo_pago ?? 'Sin método';
        }

        return $detalles
            ->map(fn (PagoDetalle $detalle) => ($detalle->paymentMethod?->nombre ?? $detalle->metodo_pago)
                .' S/ '.number_format((float) $detalle->monto, 2))
            ->implode(' + ');
    }

    public function etiquetaOrigen(): string
    {
        if ($this->enrollment_installment_id) {
            return 'Cuotas';
        }
        if ($this->client_debt_id) {
            return 'Deudas';
        }

        $tipoMatricula = $this->clienteMatricula?->tipo;
        if ($tipoMatricula === 'clase') {
            return 'Clases';
        }
        if ($tipoMatricula === 'membresia' || $this->cliente_membresia_id) {
            return 'Membresías';
        }

        return 'Otros cobros';
    }
}
