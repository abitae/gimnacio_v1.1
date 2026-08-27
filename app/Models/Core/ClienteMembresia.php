<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteMembresia extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (ClienteMembresia $clienteMembresia): void {
            app(\App\Services\ClienteService::class)
                ->syncEstadoDesdeMembresiaActiva((int) $clienteMembresia->cliente_id);
        });
    }

    protected $fillable = [
        'cliente_id',
        'membresia_id',
        'fecha_matricula',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio_lista',
        'descuento_monto',
        'precio_final',
        'asesor_id',
        'canal_venta',
        'fechas_congelacion',
        'motivo_cancelacion',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
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

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }

    public function planTraspasos(): HasMany
    {
        return $this->hasMany(ClientePlanTraspaso::class, 'origen_id')
            ->where('origen_tipo', self::class);
    }

    /**
     * Fecha y hora de inscripción para UI (fecha_matricula es DATE sin hora).
     */
    public function fechaHoraInscripcion(): ?Carbon
    {
        if (! $this->fecha_matricula) {
            return $this->created_at;
        }

        $firstPago = $this->relationLoaded('pagos')
            ? $this->pagos
                ->filter(fn ($pago) => $pago?->fecha_pago)
                ->sortBy(fn ($pago) => $pago->fecha_pago?->timestamp ?? PHP_INT_MAX)
                ->first()
            : $this->pagos()
                ->whereNotNull('fecha_pago')
                ->orderBy('fecha_pago')
                ->orderBy('id')
                ->first();

        if ($firstPago) {
            return $firstPago->fechaHoraPago();
        }

        if ($this->created_at) {
            return Carbon::parse($this->fecha_matricula)->setTimeFrom($this->created_at);
        }

        return Carbon::parse($this->fecha_matricula);
    }
}
