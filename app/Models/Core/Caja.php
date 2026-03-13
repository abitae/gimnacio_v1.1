<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Caja extends Model
{
    use HasFactory;

    protected const REFERENCIAS_COBRO_CON_PAGO = [
        ClienteMatricula::class,
        ClienteMembresia::class,
        EnrollmentInstallment::class,
    ];

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

    // Relaciones
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

    // Scopes
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

    // Métodos de negocio
    /**
     * Calcular el total de ingresos de la caja
     * Incluye pagos de membresías y movimientos de entrada (ventas POS, etc.)
     */
    public function calcularTotalIngresos(): float
    {
        $totalPagos = (float) $this->pagos()
            ->where('fecha_pago', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_pago', '<=', $this->fecha_cierre);
                }
            })
            ->sum('monto');

        $totalMovimientosEntrada = (float) $this->movimientos()
            ->where('tipo', 'entrada')
            ->where(function ($query) {
                $query->whereNull('referencia_tipo')
                    ->orWhereNotIn('referencia_tipo', self::REFERENCIAS_COBRO_CON_PAGO);
            })
            ->where('fecha_movimiento', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_movimiento', '<=', $this->fecha_cierre);
                }
            })
            ->sum('monto');

        return $totalPagos + $totalMovimientosEntrada;
    }

    /**
     * Calcular el total por método de pago
     * Incluye pagos de membresías y ventas POS (obteniendo método de pago de la venta referenciada)
     */
    public function calcularTotalPorMetodoPago(): array
    {
        $desglose = [];

        // Agrupar pagos por método de pago
        $pagos = $this->pagos()
            ->where('fecha_pago', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_pago', '<=', $this->fecha_cierre);
                }
            })
            ->selectRaw('metodo_pago, COUNT(*) as cantidad, SUM(monto) as total')
            ->groupBy('metodo_pago')
            ->get();

        foreach ($pagos as $pago) {
            $desglose[$pago->metodo_pago] = [
                'cantidad' => (int) $pago->cantidad,
                'total' => (float) $pago->total,
            ];
        }

        // Agrupar movimientos de entrada que sean ventas por método de pago
        $movimientosVentas = $this->movimientos()
            ->where('tipo', 'entrada')
            ->where('referencia_tipo', 'App\Models\Core\Venta')
            ->where('fecha_movimiento', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_movimiento', '<=', $this->fecha_cierre);
                }
            })
            ->with('referencia')
            ->get();

        foreach ($movimientosVentas as $movimiento) {
            $venta = $movimiento->referencia;
            if ($venta && isset($venta->metodo_pago)) {
                $metodo = $venta->metodo_pago;
                if (!isset($desglose[$metodo])) {
                    $desglose[$metodo] = [
                        'cantidad' => 0,
                        'total' => 0,
                    ];
                }
                $desglose[$metodo]['cantidad']++;
                $desglose[$metodo]['total'] += (float) $movimiento->monto;
            }
        }

        return $desglose;
    }

    /**
     * Obtener cantidad total de transacciones
     * Incluye pagos de membresías y movimientos de entrada
     */
    public function obtenerCantidadTransacciones(): int
    {
        $cantidadPagos = $this->pagos()
            ->where('fecha_pago', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_pago', '<=', $this->fecha_cierre);
                }
            })
            ->count();

        $cantidadMovimientos = $this->movimientos()
            ->where('tipo', 'entrada')
            ->where(function ($query) {
                $query->whereNull('referencia_tipo')
                    ->orWhereNotIn('referencia_tipo', self::REFERENCIAS_COBRO_CON_PAGO);
            })
            ->where('fecha_movimiento', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_movimiento', '<=', $this->fecha_cierre);
                }
            })
            ->count();

        return $cantidadPagos + $cantidadMovimientos;
    }

    /**
     * Calcular el total de salidas de la caja
     */
    public function calcularTotalSalidas(): float
    {
        return (float) $this->movimientos()
            ->where('tipo', 'salida')
            ->where('fecha_movimiento', '>=', $this->fecha_apertura)
            ->where(function ($query) {
                if ($this->fecha_cierre) {
                    $query->where('fecha_movimiento', '<=', $this->fecha_cierre);
                }
            })
            ->sum('monto');
    }

    /**
     * Cerrar la caja
     */
    public function cerrar(?string $observaciones = null): bool
    {
        if ($this->estado === 'cerrada') {
            throw new \Exception('La caja ya está cerrada.');
        }

        $totalIngresos = $this->calcularTotalIngresos();
        $totalSalidas = $this->calcularTotalSalidas();
        $this->saldo_final = $this->saldo_inicial + $totalIngresos - $totalSalidas;
        $this->fecha_cierre = now();
        $this->estado = 'cerrada';
        
        if ($observaciones) {
            $this->observaciones_cierre = $observaciones;
        }

        return $this->save();
    }

    /**
     * Verificar si la caja está abierta
     */
    public function estaAbierta(): bool
    {
        return $this->estado === 'abierta';
    }
}
