<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteMatricula extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'cliente_matriculas';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'membresia_id',
        'clase_id',
        'fecha_matricula',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio_lista',
        'descuento_monto',
        'precio_final',
        'modalidad_pago',
        'requiere_plan_cuotas',
        'cuota_inicial_monto',
        'asesor_id',
        'canal_venta',
        'fechas_congelacion',
        'motivo_cancelacion',
        'sesiones_totales',
        'sesiones_usadas',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'precio_lista' => 'decimal:2',
            'descuento_monto' => 'decimal:2',
            'precio_final' => 'decimal:2',
            'cuota_inicial_monto' => 'decimal:2',
            'requiere_plan_cuotas' => 'boolean',
            'fechas_congelacion' => 'array',
            'sucursal_id' => 'integer',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }

    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cliente_matricula_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'cliente_matricula_id');
    }

    /**
     * Plan único de cuotas del cliente (mismo cliente_id).
     */
    public function installmentPlan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EnrollmentInstallmentPlan::class, 'cliente_id', 'cliente_id');
    }

    public function enrollmentInstallments(): HasMany
    {
        return $this->hasMany(EnrollmentInstallment::class, 'cliente_matricula_id');
    }

    public function planTraspasos(): HasMany
    {
        return $this->hasMany(ClientePlanTraspaso::class, 'origen_id')
            ->where('origen_tipo', self::class);
    }

    // Métodos de ayuda
    public function esMembresia(): bool
    {
        return $this->tipo === 'membresia';
    }

    public function esClase(): bool
    {
        return $this->tipo === 'clase';
    }

    public function usaPlanCuotas(): bool
    {
        return $this->modalidad_pago === 'cuotas' && $this->requiere_plan_cuotas;
    }

    public function getMontoFinanciadoAttribute(): float
    {
        return max(0, (float) $this->precio_final - (float) ($this->cuota_inicial_monto ?? 0));
    }

    public function getSaldoPendienteActualAttribute(): float
    {
        if ($this->usaPlanCuotas()) {
            $hasInstallments = $this->relationLoaded('enrollmentInstallments')
                ? $this->enrollmentInstallments->isNotEmpty()
                : $this->enrollmentInstallments()->exists();

            if ($hasInstallments) {
                $sum = $this->relationLoaded('enrollmentInstallments')
                    ? (float) $this->enrollmentInstallments->whereIn('estado', ['pendiente', 'vencida', 'parcial'])->sum(fn (EnrollmentInstallment $installment) => $installment->saldo_pendiente)
                    : (float) $this->enrollmentInstallments()->whereIn('estado', ['pendiente', 'vencida', 'parcial'])->get()->sum(fn (EnrollmentInstallment $installment) => $installment->saldo_pendiente);

                return round(max(0, $sum), 2);
            }

            return round($this->monto_financiado, 2);
        }

        $ultimoPago = $this->relationLoaded('pagos')
            ? $this->pagos->sortByDesc('created_at')->first()
            : $this->pagos()->latest('created_at')->first();

        return $ultimoPago ? (float) $ultimoPago->saldo_pendiente : (float) $this->precio_final;
    }

    public function getMontoPagadoActualAttribute(): float
    {
        return round(max(0, (float) $this->precio_final - $this->saldo_pendiente_actual), 2);
    }

    public function getNombreAttribute(): string
    {
        if ($this->esMembresia() && $this->membresia) {
            return $this->membresia->nombre;
        }
        if ($this->esClase() && $this->clase) {
            return $this->clase->nombre;
        }

        return 'N/A';
    }
}
