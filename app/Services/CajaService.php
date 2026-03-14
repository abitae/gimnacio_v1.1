<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Pago;
use App\Models\Core\RentalPayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CajaService
{
    public function abrirCaja(array $data): Caja
    {
        $cajaAbierta = $this->obtenerCajaAbiertaPorUsuario(Auth::id());
        if ($cajaAbierta) {
            throw new \Exception('Ya tienes una caja abierta. Debes cerrarla antes de abrir una nueva.');
        }

        $validated = $this->validateApertura($data);

        return DB::transaction(function () use ($validated) {
            return Caja::create([
                'usuario_id' => Auth::id(),
                'saldo_inicial' => $validated['saldo_inicial'] ?? 0,
                'fecha_apertura' => now(),
                'estado' => 'abierta',
                'observaciones_apertura' => $validated['observaciones_apertura'] ?? null,
            ]);
        });
    }

    public function cerrarCaja(int $cajaId, array $data): Caja
    {
        $caja = Caja::findOrFail($cajaId);

        if ($caja->estado === 'cerrada') {
            throw new \Exception('La caja ya estÃ¡ cerrada.');
        }

        if ($caja->usuario_id !== Auth::id()) {
            throw new \Exception('Solo el usuario responsable puede cerrar esta caja.');
        }

        $validated = $this->validateCierre($data);

        return DB::transaction(function () use ($caja, $validated) {
            $caja->cerrar($validated['observaciones_cierre'] ?? null);
            return $caja->fresh(['usuario']);
        });
    }

    public function obtenerCajasAbiertas(?int $usuarioId = null): Collection
    {
        $query = Caja::abiertas()
            ->with('usuario')
            ->orderBy('fecha_apertura', 'desc');

        if ($usuarioId) {
            $query->porUsuario($usuarioId);
        }

        return $query->get();
    }

    public function obtenerCajaAbiertaPorUsuario(int $usuarioId): ?Caja
    {
        return Caja::abiertas()
            ->porUsuario($usuarioId)
            ->with('usuario')
            ->orderBy('fecha_apertura', 'desc')
            ->first();
    }

    public function obtenerCajas(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = Caja::with('usuario')
            ->orderBy('fecha_apertura', 'desc');

        if (! empty($filtros['fecha_desde'])) {
            $query->whereDate('fecha_apertura', '>=', $filtros['fecha_desde']);
        }

        if (! empty($filtros['fecha_hasta'])) {
            $query->whereDate('fecha_apertura', '<=', $filtros['fecha_hasta']);
        }

        return $query->paginate($perPage);
    }

    public function registrarIngresoManual(int $cajaId, array $data): CajaMovimiento
    {
        $validated = $this->validateMovimientoManual($data);

        return $this->registrarMovimientoClasificado(
            cajaId: $cajaId,
            tipo: 'entrada',
            categoria: CajaMovimiento::CATEGORIA_MANUAL_INGRESO,
            origenModulo: CajaMovimiento::ORIGEN_MANUAL,
            monto: (float) $validated['monto'],
            concepto: $validated['concepto'],
            referenciaTipo: $validated['referencia_tipo'] ?? null,
            referenciaId: isset($validated['referencia_id']) ? (int) $validated['referencia_id'] : null,
            observaciones: $validated['observaciones'] ?? null,
            allowCrossCaja: true,
        );
    }

    public function registrarSalidaManual(int $cajaId, array $data): CajaMovimiento
    {
        $validated = $this->validateMovimientoManual($data);

        return $this->registrarMovimientoClasificado(
            cajaId: $cajaId,
            tipo: 'salida',
            categoria: CajaMovimiento::CATEGORIA_MANUAL_SALIDA,
            origenModulo: CajaMovimiento::ORIGEN_MANUAL,
            monto: (float) $validated['monto'],
            concepto: $validated['concepto'],
            referenciaTipo: $validated['referencia_tipo'] ?? null,
            referenciaId: isset($validated['referencia_id']) ? (int) $validated['referencia_id'] : null,
            observaciones: $validated['observaciones'] ?? null,
            allowCrossCaja: true,
        );
    }

    public function registrarIngresoAutomatico(
        int $cajaId,
        float $monto,
        string $concepto,
        string $categoria,
        string $origenModulo,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $observaciones = null
    ): CajaMovimiento {
        return $this->registrarMovimientoClasificado(
            cajaId: $cajaId,
            tipo: 'entrada',
            categoria: $categoria,
            origenModulo: $origenModulo,
            monto: $monto,
            concepto: $concepto,
            referenciaTipo: $referenciaTipo,
            referenciaId: $referenciaId,
            observaciones: $observaciones,
            allowCrossCaja: false,
        );
    }

    public function registrarMovimientoClasificado(
        int $cajaId,
        string $tipo,
        string $categoria,
        string $origenModulo,
        float $monto,
        string $concepto,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $observaciones = null,
        bool $allowCrossCaja = false
    ): CajaMovimiento {
        $caja = Caja::findOrFail($cajaId);

        if ($caja->estado === 'cerrada') {
            throw new \Exception('No se pueden registrar movimientos en una caja cerrada.');
        }

        $this->autorizarMovimiento($caja, $allowCrossCaja);

        return DB::transaction(function () use (
            $cajaId,
            $tipo,
            $categoria,
            $origenModulo,
            $monto,
            $concepto,
            $referenciaTipo,
            $referenciaId,
            $observaciones
        ) {
            return CajaMovimiento::create([
                'caja_id' => $cajaId,
                'tipo' => $tipo,
                'categoria' => $categoria,
                'origen_modulo' => $origenModulo,
                'monto' => $monto,
                'concepto' => $concepto,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id' => $referenciaId,
                'usuario_id' => Auth::id(),
                'observaciones' => $observaciones,
                'fecha_movimiento' => now(),
            ]);
        });
    }

    public function registrarIngresoPorPago(
        Pago $pago,
        string $concepto,
        string $categoria,
        string $origenModulo,
        ?string $referenciaTipo = null,
        ?int $referenciaId = null,
        ?string $observaciones = null
    ): CajaMovimiento {
        if (! $pago->caja_id) {
            throw new \InvalidArgumentException('El pago debe estar asociado a una caja abierta.');
        }

        return $this->registrarIngresoAutomatico(
            cajaId: $pago->caja_id,
            monto: (float) $pago->monto,
            concepto: $concepto,
            categoria: $categoria,
            origenModulo: $origenModulo,
            referenciaTipo: $referenciaTipo ?? Pago::class,
            referenciaId: $referenciaId ?? $pago->id,
            observaciones: $observaciones
        );
    }

    public function registrarIngresoAlquiler(
        RentalPayment $payment,
        string $concepto = 'Pago de alquiler',
        ?string $observaciones = null
    ): CajaMovimiento {
        if (! $payment->caja_id) {
            throw new \InvalidArgumentException('El pago de alquiler debe estar asociado a una caja abierta.');
        }

        return $this->registrarIngresoAutomatico(
            cajaId: $payment->caja_id,
            monto: (float) $payment->monto,
            concepto: $concepto,
            categoria: CajaMovimiento::CATEGORIA_ALQUILER,
            origenModulo: CajaMovimiento::ORIGEN_RENTALS,
            referenciaTipo: RentalPayment::class,
            referenciaId: $payment->id,
            observaciones: $observaciones
        );
    }

    public function obtenerResumenCaja(Caja $caja, array $filters = []): array
    {
        $movimientos = $this->obtenerMovimientosNormalizados($caja, $filters);
        $agrupado = collect($movimientos)
            ->where('tipo', 'entrada')
            ->groupBy('categoria')
            ->map(fn ($items) => [
                'label' => $items->first()['tipo_visual'],
                'cantidad' => $items->count(),
                'total' => round((float) $items->sum('monto'), 2),
            ])
            ->sortByDesc('total')
            ->all();

        $totalIngresos = round((float) collect($movimientos)->where('tipo', 'entrada')->sum('monto'), 2);
        $totalSalidas = round((float) collect($movimientos)->where('tipo', 'salida')->sum('monto'), 2);

        return [
            'caja' => $caja,
            'saldo_inicial' => (float) $caja->saldo_inicial,
            'total_ingresos' => $totalIngresos,
            'total_salidas' => $totalSalidas,
            'saldo_actual' => round((float) $caja->saldo_inicial + $totalIngresos - $totalSalidas, 2),
            'saldo_final' => $caja->saldo_final ? (float) $caja->saldo_final : null,
            'cantidad_transacciones' => count($movimientos),
            'desglose_por_tipo' => $agrupado,
            'desglose_por_metodo' => $caja->calcularTotalPorMetodoPago(),
            'movimientos' => $movimientos,
        ];
    }

    public function obtenerMovimientosNormalizados(Caja $caja, array $filters = []): array
    {
        $movimientos = collect($caja->movimientosNormalizados());

        if (! empty($filters['tipo'])) {
            $movimientos = $movimientos->where('tipo', $filters['tipo']);
        }

        if (! empty($filters['categoria'])) {
            $movimientos = $movimientos->where('categoria', $filters['categoria']);
        }

        if (! empty($filters['origen_modulo'])) {
            $movimientos = $movimientos->where('origen_modulo', $filters['origen_modulo']);
        }

        if (! empty($filters['metodo_pago'])) {
            $movimientos = $movimientos->filter(fn ($item) => ($item['metodo_pago'] ?? null) === $filters['metodo_pago']);
        }

        if (! empty($filters['fecha_desde'])) {
            $desde = \Carbon\Carbon::parse($filters['fecha_desde'])->startOfDay();
            $movimientos = $movimientos->filter(fn ($item) => $item['fecha'] >= $desde);
        }

        if (! empty($filters['fecha_hasta'])) {
            $hasta = \Carbon\Carbon::parse($filters['fecha_hasta'])->endOfDay();
            $movimientos = $movimientos->filter(fn ($item) => $item['fecha'] <= $hasta);
        }

        return $movimientos->values()->all();
    }

    public function generarReporteCierre(int $cajaId): array
    {
        $caja = Caja::with('usuario')->findOrFail($cajaId);
        $resumen = $this->obtenerResumenCaja($caja);

        return array_merge($resumen, [
            'usuario' => $caja->usuario,
            'saldo_final_esperado' => $resumen['saldo_actual'],
            'diferencia' => $caja->saldo_final ? ((float) $caja->saldo_final - $resumen['saldo_actual']) : 0,
            'fecha_apertura' => $caja->fecha_apertura,
            'fecha_cierre' => $caja->fecha_cierre,
            'observaciones_apertura' => $caja->observaciones_apertura,
            'observaciones_cierre' => $caja->observaciones_cierre,
            'movimientos_entrada' => array_values(array_filter($resumen['movimientos'], fn ($m) => $m['tipo'] === 'entrada')),
            'movimientos_salida' => array_values(array_filter($resumen['movimientos'], fn ($m) => $m['tipo'] === 'salida')),
        ]);
    }

    public function validarCajaAbierta(?int $usuarioId = null): bool
    {
        if ($usuarioId) {
            return $this->obtenerCajaAbiertaPorUsuario($usuarioId) !== null;
        }

        return $this->obtenerCajasAbiertas()->isNotEmpty();
    }

    public function obtenerOCrearCajaAbierta(?float $saldoInicial = 0): Caja
    {
        $caja = $this->obtenerCajaAbiertaPorUsuario(Auth::id());

        if (! $caja) {
            $caja = $this->abrirCaja([
                'saldo_inicial' => $saldoInicial,
            ]);
        }

        return $caja;
    }

    protected function autorizarMovimiento(Caja $caja, bool $allowCrossCaja): void
    {
        $user = Auth::user();

        if (! $user) {
            throw new \Exception('Debes iniciar sesiÃ³n para registrar movimientos.');
        }

        if ($allowCrossCaja) {
            $puedeCruzar = $user->can('cajas.movimientos-manuales');
            $esResponsable = $caja->usuario_id === $user->id && $user->can('cajas.update');

            if (! $puedeCruzar && ! $esResponsable) {
                throw new \Exception('No tienes permiso para registrar movimientos manuales en esta caja.');
            }

            return;
        }

        if ($caja->usuario_id !== $user->id) {
            throw new \Exception('No puedes registrar movimientos automÃ¡ticos en una caja que no te pertenece.');
        }
    }

    protected function validateApertura(array $data): array
    {
        $validator = Validator::make($data, [
            'saldo_inicial' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'observaciones_apertura' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function validateCierre(array $data): array
    {
        $validator = Validator::make($data, [
            'observaciones_cierre' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function validateMovimientoManual(array $data): array
    {
        $validator = Validator::make($data, [
            'monto' => ['required', 'numeric', 'gt:0'],
            'concepto' => ['required', 'string', 'max:255'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'referencia_tipo' => ['nullable', 'string', 'max:255'],
            'referencia_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    public function categoriaDesdeMatricula(ClienteMatricula $matricula): string
    {
        return $matricula->esClase()
            ? CajaMovimiento::CATEGORIA_CLASE
            : CajaMovimiento::CATEGORIA_MEMBRESIA;
    }
}
