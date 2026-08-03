<?php

namespace App\Services;

use App\Models\Core\ClientDebt;
use App\Models\Core\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CreditSalesQueryService
{
    public function query(?string $search = null, ?string $fechaInicio = null, ?string $fechaFin = null): Builder
    {
        $query = Venta::query()
            ->where('es_credito', true)
            ->with(['cliente', 'employee', 'usuario', 'clientDebt', 'employeeDebt'])
            ->orderByDesc('fecha_venta');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_venta', 'like', '%'.$search.'%')
                    ->orWhere('cliente_venta_nombre', 'like', '%'.$search.'%')
                    ->orWhere('cliente_venta_documento', 'like', '%'.$search.'%')
                    ->orWhere('cliente_venta_telefono', 'like', '%'.$search.'%')
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombres', 'like', '%'.$search.'%')
                        ->orWhere('apellidos', 'like', '%'.$search.'%')
                        ->orWhere('numero_documento', 'like', '%'.$search.'%')
                        ->orWhere('codigo', 'like', '%'.$search.'%')
                        ->orWhere('telefono', 'like', '%'.$search.'%'))
                    ->orWhereHas('employee', fn ($e) => $e->where('nombres', 'like', '%'.$search.'%')
                        ->orWhere('apellidos', 'like', '%'.$search.'%')
                        ->orWhere('documento', 'like', '%'.$search.'%')
                        ->orWhere('telefono', 'like', '%'.$search.'%'));
            });
        }

        if ($fechaInicio) {
            $query->whereDate('fecha_venta', '>=', $fechaInicio);
        }

        if ($fechaFin) {
            $query->whereDate('fecha_venta', '<=', $fechaFin);
        }

        return $query;
    }

    /**
     * @return array{
     *     cantidad_ventas: int,
     *     total_ventas: float,
     *     total_pagado: float,
     *     total_saldo_pendiente: float,
     *     cantidad_con_saldo: int
     * }
     */
    public function totales(Builder $query): array
    {
        $ventas = (clone $query)->get();

        $totalVentas = 0.0;
        $totalPagado = 0.0;
        $totalSaldoPendiente = 0.0;
        $cantidadConSaldo = 0;

        foreach ($ventas as $venta) {
            $fila = $this->mapVenta($venta);
            $totalVentas += $fila['total'];
            $totalPagado += $fila['monto_pagado'];

            if ($fila['es_cobrable_cliente']) {
                $totalSaldoPendiente += $fila['saldo'];
                if ($fila['saldo'] > 0) {
                    $cantidadConSaldo++;
                }
            }
        }

        return [
            'cantidad_ventas' => $ventas->count(),
            'total_ventas' => round($totalVentas, 2),
            'total_pagado' => round($totalPagado, 2),
            'total_saldo_pendiente' => round($totalSaldoPendiente, 2),
            'cantidad_con_saldo' => $cantidadConSaldo,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function filasParaExport(?string $search, ?string $fechaInicio, ?string $fechaFin): array
    {
        return $this->ventasParaExport($search, $fechaInicio, $fechaFin)
            ->map(fn (Venta $venta) => $this->mapVenta($venta))
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Venta>
     */
    public function ventasParaExport(?string $search, ?string $fechaInicio, ?string $fechaFin): Collection
    {
        return $this->query($search, $fechaInicio, $fechaFin)->get();
    }

    /**
     * @return array{
     *     venta_id: int,
     *     client_debt_id: int|null,
     *     codigo: string,
     *     comprador_nombre: string,
     *     comprador_tipo: string,
     *     comprador_detalle: string,
     *     numero_venta: string,
     *     total: float,
     *     monto_pagado: float,
     *     saldo: float,
     *     fecha: \Carbon\Carbon|null,
     *     registrado_por: string,
     *     estado: string,
     *     fecha_vencimiento: string|null,
     *     es_cobrable_cliente: bool,
     *     cliente_id: int|null
     * }
     */
    public function mapVenta(Venta $venta): array
    {
        $debt = $venta->clientDebt;
        $employeeDebt = $venta->employeeDebt;
        $saldo = (float) ($debt?->saldo_pendiente ?? $employeeDebt?->saldo_pendiente ?? max(0, ($venta->total ?? 0) - ($venta->monto_inicial ?? 0)));
        $montoPagado = (float) ($debt?->monto_pagado ?? $employeeDebt?->monto_abonado ?? ($venta->monto_inicial ?? 0));
        $estado = $debt?->estado ?? $employeeDebt?->estado ?? ($saldo > 0 ? 'pendiente' : 'pagado');

        if ($venta->cliente) {
            $compradorNombre = trim($venta->cliente->nombres.' '.$venta->cliente->apellidos);
            $compradorDetalle = trim(($venta->cliente->tipo_documento ?? 'Doc.').' '.($venta->cliente->numero_documento ?? ''));
            $compradorCodigo = $venta->cliente->codigo ?? $venta->cliente->numero_documento ?? '—';
            $compradorTipo = 'Cliente gimnasio';
        } elseif ($venta->employee) {
            $compradorNombre = $venta->employee->nombre_completo;
            $compradorDetalle = trim('Doc. '.($venta->employee->documento ?? ''));
            $compradorCodigo = $venta->employee->documento ?? '—';
            $compradorTipo = 'Empleado';
        } elseif ($venta->cliente_venta_nombre) {
            $compradorNombre = $venta->cliente_venta_nombre;
            $compradorDetalle = trim(collect([$venta->cliente_venta_documento, $venta->cliente_venta_telefono])->filter()->implode(' - '));
            $compradorCodigo = $venta->cliente_venta_documento ?: '—';
            $compradorTipo = 'Cliente POS';
        } else {
            $compradorNombre = '—';
            $compradorDetalle = '';
            $compradorCodigo = '—';
            $compradorTipo = 'Sin cliente';
        }

        $fechaVencimiento = $debt?->fecha_vencimiento
            ?? $employeeDebt?->fecha_vencimiento
            ?? $venta->fecha_vencimiento_deuda;

        $fechaVencimientoStr = $fechaVencimiento instanceof \Carbon\CarbonInterface
            ? $fechaVencimiento->format('Y-m-d')
            : (is_string($fechaVencimiento) && $fechaVencimiento !== '' ? $fechaVencimiento : null);

        $esCobrableCliente = $debt !== null
            && $venta->cliente_id !== null
            && $saldo > 0
            && in_array($estado, ['pendiente', 'parcial', 'vencido'], true);

        return [
            'venta_id' => $venta->id,
            'client_debt_id' => $debt?->id,
            'codigo' => $compradorCodigo,
            'comprador_nombre' => $compradorNombre,
            'comprador_tipo' => $compradorTipo,
            'comprador_detalle' => $compradorDetalle,
            'numero_venta' => $venta->numero_venta,
            'total' => round((float) $venta->total, 2),
            'monto_pagado' => round($montoPagado, 2),
            'saldo' => round($saldo, 2),
            'fecha' => $venta->fecha_venta,
            'registrado_por' => $venta->usuario?->name ?? '-',
            'estado' => $estado,
            'fecha_vencimiento' => $fechaVencimientoStr,
            'es_cobrable_cliente' => $esCobrableCliente,
            'cliente_id' => $venta->cliente_id,
        ];
    }

    /**
     * @return list<int>
     */
    public function debtIdsCobrablesEnPagina(Collection $ventas): array
    {
        return $ventas
            ->map(fn (Venta $venta) => $this->mapVenta($venta))
            ->filter(fn (array $fila) => $fila['es_cobrable_cliente'] && $fila['client_debt_id'])
            ->pluck('client_debt_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
