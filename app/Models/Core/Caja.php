<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'saldo_inicial',
        'saldo_final',
        'fecha_apertura',
        'fecha_cierre',
        'estado',
        'observaciones_apertura',
        'observaciones_cierre',
    ];

    protected function casts(): array
    {
        return [
            'saldo_inicial' => 'decimal:2',
            'saldo_final' => 'decimal:2',
            'fecha_apertura' => 'datetime',
            'fecha_cierre' => 'datetime',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(CajaMovimiento::class);
    }

    public function scopeAbiertas(Builder $query): Builder
    {
        return $query->where('estado', 'abierta');
    }

    public function scopeCerradas(Builder $query): Builder
    {
        return $query->where('estado', 'cerrada');
    }

    public function scopePorUsuario(Builder $query, int $usuarioId): Builder
    {
        return $query->where('usuario_id', $usuarioId);
    }

    public function calcularTotalIngresos(): float
    {
        return (float) $this->movimientosPeriodo()
            ->where('tipo', 'entrada')
            ->sum('monto');
    }

    public function calcularTotalPorMetodoPago(): array
    {
        $desglose = [];

        foreach ($this->movimientosNormalizados() as $movimiento) {
            if ($movimiento['tipo'] !== 'entrada' || empty($movimiento['metodo_pago'])) {
                continue;
            }

            $metodo = $movimiento['metodo_pago'];
            $desglose[$metodo] ??= ['cantidad' => 0, 'total' => 0.0];
            $desglose[$metodo]['cantidad']++;
            $desglose[$metodo]['total'] += (float) $movimiento['monto'];
        }

        return $desglose;
    }

    public function obtenerCantidadTransacciones(): int
    {
        return count($this->movimientosNormalizados());
    }

    public function calcularTotalSalidas(): float
    {
        return (float) $this->movimientosPeriodo()
            ->where('tipo', 'salida')
            ->sum('monto');
    }

    public function cerrar(?string $observaciones = null): bool
    {
        if ($this->estado === 'cerrada') {
            throw new \Exception('La caja ya estÃ¡ cerrada.');
        }

        $this->saldo_final = $this->saldo_inicial + $this->calcularTotalIngresos() - $this->calcularTotalSalidas();
        $this->fecha_cierre = now();
        $this->estado = 'cerrada';

        if ($observaciones) {
            $this->observaciones_cierre = $observaciones;
        }

        return $this->save();
    }

    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }

    public function movimientosPeriodo(): HasMany
    {
        return $this->movimientos()
            ->where('fecha_movimiento', '>=', $this->fecha_apertura)
            ->when($this->fecha_cierre, fn ($query) => $query->where('fecha_movimiento', '<=', $this->fecha_cierre));
    }

    public function movimientosNormalizados(): array
    {
        $movimientos = $this->movimientosPeriodo()
            ->with(['usuario', 'referencia'])
            ->orderByDesc('fecha_movimiento')
            ->get();

        return $movimientos->map(function (CajaMovimiento $movimiento) {
            $referencia = $movimiento->referencia;
            $metodoPago = null;

            if ($referencia instanceof Pago) {
                $metodoPago = $referencia->metodo_pago;
            } elseif ($referencia instanceof Venta) {
                $metodoPago = $referencia->metodo_pago;
            } elseif ($referencia instanceof RentalPayment) {
                $metodoPago = $referencia->paymentMethod?->nombre;
            }

            return [
                'id' => $movimiento->id,
                'fecha' => $movimiento->fecha_movimiento,
                'tipo' => $movimiento->tipo,
                'categoria' => $movimiento->categoria,
                'tipo_visual' => $movimiento->tipo_visual,
                'origen_modulo' => $movimiento->origen_modulo,
                'concepto' => $movimiento->concepto,
                'monto' => (float) $movimiento->monto,
                'metodo_pago' => $metodoPago,
                'usuario' => $movimiento->usuario?->name,
                'referencia_tipo' => $movimiento->referencia_tipo,
                'referencia_id' => $movimiento->referencia_id,
                'referencia_label' => match (true) {
                    $referencia instanceof Venta => $referencia->numero_venta,
                    $referencia instanceof Pago => 'Pago #' . $referencia->id,
                    $referencia instanceof RentalPayment => 'Alquiler #' . $referencia->rental_id,
                    default => null,
                },
            ];
        })->values()->all();
    }
}
