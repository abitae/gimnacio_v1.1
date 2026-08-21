<?php

namespace App\Support;

use App\Models\Core\CajaMovimiento;

class CajaMatrizTotales
{
    /**
     * @param  array<string, mixed>  $movimiento
     */
    public static function etiquetaTipo(array $movimiento): string
    {
        return match ($movimiento['categoria'] ?? null) {
            CajaMovimiento::CATEGORIA_POS => 'Venta POS',
            CajaMovimiento::CATEGORIA_MEMBRESIA => 'Membresías',
            CajaMovimiento::CATEGORIA_CLASE => 'Clases',
            CajaMovimiento::CATEGORIA_ALQUILER => 'Alquileres',
            CajaMovimiento::CATEGORIA_CUOTA => 'Cuotas',
            CajaMovimiento::CATEGORIA_MANUAL_INGRESO => 'Ingreso manual',
            CajaMovimiento::CATEGORIA_MANUAL_SALIDA => 'Salida manual',
            default => (string) ($movimiento['tipo_visual'] ?? 'Otros'),
        };
    }

    /**
     * @param  array<string, mixed>  $movimiento
     */
    public static function etiquetaMetodo(array $movimiento): string
    {
        return filled($movimiento['metodo_pago'] ?? null)
            ? (string) $movimiento['metodo_pago']
            : 'Sin método';
    }

    /**
     * Indica si el movimiento forma parte de la celda / fila / columna / total filtrado.
     *
     * @param  array<string, mixed>  $movimiento
     */
    public static function coincide(array $movimiento, ?string $tipo = null, ?string $metodo = null): bool
    {
        if (CajaCreditoHelper::movimientoExcluirDeTotalesCaja($movimiento)) {
            return false;
        }

        if ($tipo !== null && self::etiquetaTipo($movimiento) !== $tipo) {
            return false;
        }

        if ($metodo !== null && self::etiquetaMetodo($movimiento) !== $metodo) {
            return false;
        }

        return true;
    }

    /**
     * Matriz: filas = tipo de operación, columnas = método de pago.
     *
     * @param  iterable<int, array{tipo?: string, metodo_pago?: string|null, monto?: float|int|string}>  $filas
     * @return array{
     *     tipos: list<string>,
     *     metodos: list<string>,
     *     celdas: array<string, array<string, array{total: float, cantidad: int}>>,
     *     totales_tipo: array<string, float>,
     *     totales_metodo: array<string, float>,
     *     cantidades_tipo: array<string, int>,
     *     cantidades_metodo: array<string, int>,
     *     total_general: float,
     *     cantidad_general: int
     * }
     */
    public static function fromFilas(iterable $filas): array
    {
        $celdas = [];
        $totalesTipo = [];
        $totalesMetodo = [];
        $cantidadesTipo = [];
        $cantidadesMetodo = [];
        $totalGeneral = 0.0;
        $cantidadGeneral = 0;

        foreach ($filas as $fila) {
            $tipo = filled($fila['tipo'] ?? null) ? (string) $fila['tipo'] : 'Otros';
            $metodo = filled($fila['metodo_pago'] ?? null)
                ? (string) $fila['metodo_pago']
                : 'Sin método';
            $monto = round((float) ($fila['monto'] ?? 0), 2);

            $celdas[$tipo][$metodo] ??= ['total' => 0.0, 'cantidad' => 0];
            $celdas[$tipo][$metodo]['total'] = round($celdas[$tipo][$metodo]['total'] + $monto, 2);
            $celdas[$tipo][$metodo]['cantidad']++;

            $totalesTipo[$tipo] = round(($totalesTipo[$tipo] ?? 0) + $monto, 2);
            $totalesMetodo[$metodo] = round(($totalesMetodo[$metodo] ?? 0) + $monto, 2);
            $cantidadesTipo[$tipo] = ($cantidadesTipo[$tipo] ?? 0) + 1;
            $cantidadesMetodo[$metodo] = ($cantidadesMetodo[$metodo] ?? 0) + 1;
            $totalGeneral = round($totalGeneral + $monto, 2);
            $cantidadGeneral++;
        }

        arsort($totalesTipo);
        arsort($totalesMetodo);

        $tipos = array_keys($totalesTipo);
        $metodos = array_keys($totalesMetodo);

        return [
            'tipos' => $tipos,
            'metodos' => $metodos,
            'celdas' => $celdas,
            'totales_tipo' => $totalesTipo,
            'totales_metodo' => $totalesMetodo,
            'cantidades_tipo' => collect($tipos)
                ->mapWithKeys(fn (string $tipo): array => [$tipo => (int) ($cantidadesTipo[$tipo] ?? 0)])
                ->all(),
            'cantidades_metodo' => collect($metodos)
                ->mapWithKeys(fn (string $metodo): array => [$metodo => (int) ($cantidadesMetodo[$metodo] ?? 0)])
                ->all(),
            'total_general' => $totalGeneral,
            'cantidad_general' => $cantidadGeneral,
        ];
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $movimientos
     * @return array{
     *     tipos: list<string>,
     *     metodos: list<string>,
     *     celdas: array<string, array<string, array{total: float, cantidad: int}>>,
     *     totales_tipo: array<string, float>,
     *     totales_metodo: array<string, float>,
     *     cantidades_tipo: array<string, int>,
     *     cantidades_metodo: array<string, int>,
     *     total_general: float,
     *     cantidad_general: int
     * }
     */
    public static function fromMovimientos(iterable $movimientos): array
    {
        $filas = [];

        foreach ($movimientos as $movimiento) {
            if (CajaCreditoHelper::movimientoExcluirDeTotalesCaja($movimiento)) {
                continue;
            }

            $filas[] = [
                'tipo' => self::etiquetaTipo($movimiento),
                'metodo_pago' => self::etiquetaMetodo($movimiento),
                'monto' => (float) ($movimiento['monto'] ?? 0),
            ];
        }

        return self::fromFilas($filas);
    }
}
