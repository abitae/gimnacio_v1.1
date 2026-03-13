<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\Pago;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class CajaService
{
    /**
     * Abrir una nueva caja
     * Solo se permite una caja abierta por usuario
     */
    public function abrirCaja(array $data): Caja
    {
        // Validar que el usuario no tenga una caja abierta
        $cajaAbierta = $this->obtenerCajaAbiertaPorUsuario(Auth::user()->id);
        if ($cajaAbierta) {
            throw new \Exception('Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.');
        }

        $validated = $this->validateApertura($data);

        return DB::transaction(function () use ($validated) {
            return Caja::create([
                'usuario_id' => Auth::user()->id,
                'saldo_inicial' => $validated['saldo_inicial'] ?? 0,
                'fecha_apertura' => now(),
                'estado' => 'abierta',
                'observaciones_apertura' => $validated['observaciones_apertura'] ?? null,
            ]);
        });
    }

    /**
     * Cerrar una caja
     */
    public function cerrarCaja(int $cajaId, array $data): Caja
    {
        $caja = Caja::findOrFail($cajaId);

        if ($caja->estado === 'cerrada') {
            throw new \Exception('La caja ya está cerrada.');
        }

        if ($caja->usuario_id !== Auth::user()->id) {
            throw new \Exception('Solo el usuario responsable puede cerrar esta caja.');
        }

        $validated = $this->validateCierre($data);

        return DB::transaction(function () use ($caja, $validated) {
            $caja->cerrar($validated['observaciones_cierre'] ?? null);
            return $caja->fresh(['usuario', 'pagos']);
        });
    }

    /**
     * Obtener todas las cajas abiertas
     */
    public function obtenerCajasAbiertas(?int $usuarioId = null): Collection
    {
        $query = Caja::abiertas()
            ->with(['usuario', 'pagos', 'movimientos.usuario', 'movimientos.referencia'])
            ->orderBy('fecha_apertura', 'desc');

        if ($usuarioId) {
            $query->porUsuario($usuarioId);
        }

        return $query->get();
    }

    /**
     * Obtener la caja abierta de un usuario específico
     */
    public function obtenerCajaAbiertaPorUsuario(int $usuarioId): ?Caja
    {
        return Caja::abiertas()
            ->porUsuario($usuarioId)
            ->with(['usuario', 'pagos', 'movimientos.usuario', 'movimientos.referencia'])
            ->orderBy('fecha_apertura', 'desc')
            ->first();
    }

    /**
     * Obtener cajas con paginación y filtros
     * Solo muestra las cajas del usuario autenticado
     */
    public function obtenerCajas(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = Caja::with(['usuario'])
            ->porUsuario(Auth::user()->id)
            ->orderBy('fecha_apertura', 'desc');

        // Filtro por fecha desde
        if (isset($filtros['fecha_desde'])) {
            $query->whereDate('fecha_apertura', '>=', $filtros['fecha_desde']);
        }

        // Filtro por fecha hasta
        if (isset($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_apertura', '<=', $filtros['fecha_hasta']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Registrar un movimiento de caja
     */
    public function registrarMovimiento(
        int $cajaId,
        string $tipo,
        float $monto,
        string $concepto,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $observaciones = null
    ): \App\Models\Core\CajaMovimiento {
        $caja = Caja::findOrFail($cajaId);

        if ($caja->estado === 'cerrada') {
            throw new \Exception('No se pueden registrar movimientos en una caja cerrada.');
        }

        // Validar que la caja pertenezca al usuario autenticado
        if ($caja->usuario_id !== Auth::user()->id) {
            throw new \Exception('No puedes registrar movimientos en una caja que no te pertenece.');
        }

        return DB::transaction(function () use ($cajaId, $tipo, $monto, $concepto, $referenciaTipo, $referenciaId, $observaciones) {
            return \App\Models\Core\CajaMovimiento::create([
                'caja_id' => $cajaId,
                'tipo' => $tipo,
                'monto' => $monto,
                'concepto' => $concepto,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id' => $referenciaId,
                'usuario_id' => Auth::user()->id,
                'observaciones' => $observaciones,
                'fecha_movimiento' => now(),
            ]);
        });
    }

    public function registrarIngresoPorPago(
        Pago $pago,
        string $concepto,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $observaciones = null
    ): \App\Models\Core\CajaMovimiento {
        if (! $pago->caja_id) {
            throw new \InvalidArgumentException('El pago debe estar asociado a una caja abierta.');
        }

        return $this->registrarMovimiento(
            $pago->caja_id,
            'entrada',
            (float) $pago->monto,
            $concepto,
            $referenciaTipo ?? Pago::class,
            $referenciaId ?? $pago->id,
            $observaciones
        );
    }

    /**
     * Generar reporte detallado de cierre
     */
    public function generarReporteCierre(int $cajaId): array
    {
        $caja = Caja::with(['usuario', 'pagos.cliente', 'pagos.clienteMembresia', 'movimientos.usuario', 'movimientos.referencia'])
            ->findOrFail($cajaId);

        $totalIngresos = $caja->calcularTotalIngresos();
        $totalSalidas = $caja->calcularTotalSalidas();
        $cantidadTransacciones = $caja->obtenerCantidadTransacciones();
        $desglosePorMetodo = $caja->calcularTotalPorMetodoPago();
        $saldoFinalEsperado = $caja->saldo_inicial + $totalIngresos - $totalSalidas;
        $diferencia = $caja->saldo_final ? ($caja->saldo_final - $saldoFinalEsperado) : 0;

        // Obtener movimientos de entrada y salida
        $movimientosEntrada = $caja->movimientos()
            ->where('tipo', 'entrada')
            ->where('fecha_movimiento', '>=', $caja->fecha_apertura)
            ->where(function ($query) use ($caja) {
                if ($caja->fecha_cierre) {
                    $query->where('fecha_movimiento', '<=', $caja->fecha_cierre);
                }
            })
            ->with(['usuario', 'referencia'])
            ->get();

        $movimientosSalida = $caja->movimientos()
            ->where('tipo', 'salida')
            ->where('fecha_movimiento', '>=', $caja->fecha_apertura)
            ->where(function ($query) use ($caja) {
                if ($caja->fecha_cierre) {
                    $query->where('fecha_movimiento', '<=', $caja->fecha_cierre);
                }
            })
            ->with(['usuario', 'referencia'])
            ->get();

        return [
            'caja' => $caja,
            'usuario' => $caja->usuario,
            'saldo_inicial' => $caja->saldo_inicial,
            'saldo_final' => $caja->saldo_final,
            'total_ingresos' => $totalIngresos,
            'total_salidas' => $totalSalidas,
            'cantidad_transacciones' => $cantidadTransacciones,
            'desglose_por_metodo' => $desglosePorMetodo,
            'saldo_final_esperado' => $saldoFinalEsperado,
            'diferencia' => $diferencia,
            'fecha_apertura' => $caja->fecha_apertura,
            'fecha_cierre' => $caja->fecha_cierre,
            'observaciones_apertura' => $caja->observaciones_apertura,
            'observaciones_cierre' => $caja->observaciones_cierre,
            'pagos' => $caja->pagos()->with(['cliente', 'clienteMembresia'])->get(),
            'movimientos_entrada' => $movimientosEntrada,
            'movimientos_salida' => $movimientosSalida,
        ];
    }

    /**
     * Validar que exista al menos una caja abierta
     */
    public function validarCajaAbierta(?int $usuarioId = null): bool
    {
        if ($usuarioId) {
            return $this->obtenerCajaAbiertaPorUsuario($usuarioId) !== null;
        }

        return $this->obtenerCajasAbiertas()->isNotEmpty();
    }

    /**
     * Obtener o crear caja abierta para el usuario actual
     */
    public function obtenerOCrearCajaAbierta(?float $saldoInicial = 0): Caja
    {
        $caja = $this->obtenerCajaAbiertaPorUsuario(Auth::user()->id);

        if (!$caja) {
            $caja = $this->abrirCaja([
                'saldo_inicial' => $saldoInicial,
            ]);
        }

        return $caja;
    }

    /**
     * Validar datos de apertura
     */
    protected function validateApertura(array $data): array
    {
        $rules = [
            'saldo_inicial' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'observaciones_apertura' => ['nullable', 'string', 'max:1000'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Validar datos de cierre
     */
    protected function validateCierre(array $data): array
    {
        $rules = [
            'observaciones_cierre' => ['nullable', 'string', 'max:1000'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
