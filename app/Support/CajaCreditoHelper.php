<?php

namespace App\Support;

use App\Models\Core\CajaMovimiento;
use App\Models\Core\Venta;
use App\Models\Core\VentaPago;

class CajaCreditoHelper
{
    public static function esMetodoPagoCredito(?string $metodo): bool
    {
        if ($metodo === null || trim($metodo) === '') {
            return false;
        }

        $normalizado = mb_strtolower(trim($metodo));

        if (in_array($normalizado, ['crédito', 'credito', 'credit', 'crédito cliente', 'credito cliente'], true)) {
            return true;
        }

        return str_contains($normalizado, 'crédito')
            || str_contains($normalizado, 'credito');
    }

    /**
     * @param  array<string, mixed>  $movimiento
     */
    public static function movimientoExcluirDeTotalesCaja(array $movimiento): bool
    {
        if (($movimiento['tipo'] ?? null) !== 'entrada') {
            return true;
        }

        if (($movimiento['categoria'] ?? null) === CajaMovimiento::CATEGORIA_APERTURA) {
            return true;
        }

        if (! empty($movimiento['excluir_totales_caja'])) {
            return true;
        }

        return self::esMetodoPagoCredito($movimiento['metodo_pago'] ?? null);
    }

    /**
     * @return array{es_venta_credito: bool, es_anticipo_credito: bool, excluir_totales_caja: bool}
     */
    public static function metadataMovimientoCredito(CajaMovimiento $movimiento, mixed $referencia, ?string $metodoPago): array
    {
        $esVentaCredito = false;
        $esAnticipoCredito = false;
        $excluirTotales = self::esMetodoPagoCredito($metodoPago);

        if ($referencia instanceof Venta && $referencia->es_credito) {
            $esVentaCredito = true;
            $saldo = $referencia->saldoPendienteVenta();
            $monto = (float) $movimiento->monto;

            if ($saldo > 0.009 && abs($monto - (float) $referencia->total) < 0.01) {
                $excluirTotales = true;
            }
        } elseif ($referencia instanceof VentaPago) {
            $venta = $referencia->relationLoaded('venta')
                ? $referencia->venta
                : $referencia->venta()->first();

            if ($venta?->es_credito) {
                $esVentaCredito = true;
                $esAnticipoCredito = $venta->saldoPendienteVenta() > 0.009;
            }
        }

        return [
            'es_venta_credito' => $esVentaCredito,
            'es_anticipo_credito' => $esAnticipoCredito,
            'excluir_totales_caja' => $excluirTotales,
        ];
    }
}
