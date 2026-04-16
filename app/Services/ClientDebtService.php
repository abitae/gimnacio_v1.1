<?php

namespace App\Services;

use App\Models\Core\CajaMovimiento;
use App\Models\Core\Caja;
use App\Models\Core\ClientDebt;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientDebtService
{
    public function procesarPago(int $clientDebtId, array $data): Pago
    {
        $debt = ClientDebt::query()->with(['cliente', 'venta'])->findOrFail($clientDebtId);

        if (! in_array($debt->estado, ['pendiente', 'parcial', 'vencido'], true) || (float) $debt->saldo_pendiente <= 0) {
            throw new \InvalidArgumentException('La deuda ya no tiene saldo pendiente.');
        }

        $montoPago = round((float) ($data['monto_pago'] ?? 0), 2);
        if ($montoPago <= 0) {
            throw new \InvalidArgumentException('El monto del pago debe ser mayor a cero.');
        }

        if ($montoPago > round((float) $debt->saldo_pendiente, 2)) {
            throw new \InvalidArgumentException('El monto del pago no puede ser mayor al saldo pendiente.');
        }

        $cajaService = app(CajaService::class);
        if (! $cajaService->validarCajaAbierta(Auth::id())) {
            throw new \InvalidArgumentException('No hay una caja abierta. Abra una caja antes de registrar pagos.');
        }

        $caja = $cajaService->obtenerOCrearCajaAbierta();
        $this->assertCajaSucursal($caja->id, (int) $debt->sucursal_id);

        return DB::transaction(function () use ($data, $debt, $montoPago, $caja, $cajaService) {
            $saldoNuevo = round((float) $debt->saldo_pendiente - $montoPago, 2);
            $montoPagadoNuevo = round((float) $debt->monto_pagado + $montoPago, 2);
            $estadoNuevo = $saldoNuevo <= 0 ? 'pagado' : 'parcial';

            $paymentMethodId = $data['payment_method_id'] ?? null;
            $metodoPago = $data['metodo_pago'] ?? 'efectivo';
            if ($paymentMethodId) {
                $this->assertPaymentMethodSucursal((int) $paymentMethodId, (int) $debt->sucursal_id);
                $paymentMethod = PaymentMethod::find($paymentMethodId);
                if ($paymentMethod) {
                    $metodoPago = $paymentMethod->nombre;
                }
            }

            $cobro = app(CobroTicketService::class)->resolverComprobantePago([
                'comprobante_tipo' => $data['comprobante_tipo'] ?? null,
                'comprobante_numero' => $data['comprobante_numero'] ?? null,
            ]);

            $pago = Pago::create([
                'cliente_id' => $debt->cliente_id,
                'client_debt_id' => $debt->id,
                'monto' => $montoPago,
                'moneda' => $data['moneda'] ?? 'PEN',
                'metodo_pago' => $metodoPago,
                'payment_method_id' => $paymentMethodId,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'entidad_financiera' => $data['entidad_financiera'] ?? null,
                'fecha_pago' => $data['fecha_pago'] ?? now(),
                'es_pago_parcial' => $saldoNuevo > 0,
                'saldo_pendiente' => $saldoNuevo,
                'comprobante_tipo' => $cobro['tipo'],
                'comprobante_numero' => $cobro['numero'],
                'registrado_por' => Auth::id(),
                'caja_id' => $caja->id,
                'sucursal_id' => $debt->sucursal_id,
            ]);

            $debt->update([
                'monto_pagado' => $montoPagadoNuevo,
                'saldo_pendiente' => max(0, $saldoNuevo),
                'estado' => $estadoNuevo,
            ]);

            $observaciones = 'Método de pago: '.$metodoPago;
            if ($pago->comprobante_tipo || $pago->comprobante_numero) {
                $observaciones .= ', Comprobante: '.strtoupper((string) $pago->comprobante_tipo).' '.$pago->comprobante_numero;
            }

            $cajaService->registrarIngresoPorPago(
                $pago,
                'Cobro de cuenta por cobrar - '.($debt->venta?->numero_venta ?? 'Deuda #'.$debt->id),
                CajaMovimiento::CATEGORIA_POS,
                CajaMovimiento::ORIGEN_VENTAS,
                null,
                null,
                $observaciones
            );

            return $pago->fresh(['cliente', 'caja', 'paymentMethod']);
        });
    }

    public function markOverdueDebts(): int
    {
        return ClientDebt::query()
            ->whereIn('estado', ['pendiente', 'parcial'])
            ->whereDate('fecha_vencimiento', '<', today())
            ->update(['estado' => 'vencido']);
    }

    private function assertCajaSucursal(int $cajaId, int $sucursalId): void
    {
        $caja = Caja::findOrFail($cajaId);

        if ((int) $caja->sucursal_id !== $sucursalId) {
            throw new \InvalidArgumentException('La caja seleccionada no pertenece a la sucursal de la deuda.');
        }
    }

    private function assertPaymentMethodSucursal(int $paymentMethodId, int $sucursalId): void
    {
        $paymentMethod = PaymentMethod::find($paymentMethodId);

        if (! $paymentMethod || (int) $paymentMethod->sucursal_id !== $sucursalId) {
            throw new \InvalidArgumentException('El metodo de pago seleccionado no pertenece a la sucursal de la deuda.');
        }
    }
}
