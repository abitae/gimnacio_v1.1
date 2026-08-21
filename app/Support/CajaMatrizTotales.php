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
     * Matriz: filas = tipo de operación, columnas = método de pago.
     *
     * @param  iterable<int, array{tipo?: string, metodo_pago?: string|null, monto?: float|int|string}>  $filas
     * @return array{
     *     tipos: list<string>,
     *     metodos: list<string>,
     *     celdas: array<string, array<string, array{total: float, cantidad: int}>>,
     *     totales_tipo: array<string, float>,
     *     totales_metodo: array<string, float>,
     *     total_general: float
     * }
     */
    public static function fromFilas(iterable $filas): array
    {
        $celdas = [];
        $totalesTipo = [];
        $totalesMetodo = [];
        $totalGeneral = 0.0;

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
            $totalGeneral = round($totalGeneral + $monto, 2);
        }

        arsort($totalesTipo);
        arsort($totalesMetodo);

        return [
            'tipos' => array_keys($totalesTipo),
            'metodos' => array_keys($totalesMetodo),
            'celdas' => $celdas,
            'totales_tipo' => $totalesTipo,
            'totales_metodo' => $totalesMetodo,
            'total_general' => $totalGeneral,
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
     *     total_general: float
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
                'metodo_pago' => filled($movimiento['metodo_pago'] ?? null)
                    ? (string) $movimiento['metodo_pago']
                    : 'Sin método',
                'monto' => (float) ($movimiento['monto'] ?? 0),
            ];
        }

        return self::fromFilas($filas);
    }
}
