<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;

class MatriculaDebtReconciler
{
    public function __construct(
        private readonly SellerUserResolver $sellerUserResolver,
    ) {}

    /**
     * @return array{status:string,warning:?string,updated_matricula:bool,updated_pago:bool}
     */
    public function reconcile(ClienteMatricula $matricula, DeudaClienteRowData $row, bool $execute): array
    {
        $saldoObjetivo = round((float) $row->debe, 2);
        $precioObjetivo = round((float) $row->costo, 2);
        $montoPagadoObjetivo = round(max(0, $precioObjetivo - $saldoObjetivo), 2);
        $esParcial = $saldoObjetivo > 0 && $montoPagadoObjetivo > 0;

        if ($this->isAlreadySynced($matricula, $precioObjetivo, $montoPagadoObjetivo, $saldoObjetivo, $esParcial)) {
            return [
                'status' => 'already_synced',
                'warning' => null,
                'updated_matricula' => false,
                'updated_pago' => false,
            ];
        }

        if (! $execute) {
            return [
                'status' => 'would_update',
                'warning' => null,
                'updated_matricula' => true,
                'updated_pago' => true,
            ];
        }

        $precioActual = (float) $matricula->precio_final;

        $matricula->update([
            'precio_lista' => $precioObjetivo,
            'precio_final' => $precioObjetivo,
            'modalidad_pago' => 'contado',
            'requiere_plan_cuotas' => false,
            'cuota_inicial_monto' => 0,
        ]);

        $deletedTechnicalPayments = $this->deleteTechnicalImportPayments($matricula, $precioActual, $precioObjetivo);

        $asesor = $this->sellerUserResolver->resolveOrFallback($row->vendedor);
        $cashMethod = $this->resolveCashMethod();

        Pago::create([
            'cliente_id' => $matricula->cliente_id,
            'cliente_matricula_id' => $matricula->id,
            'monto' => $montoPagadoObjetivo,
            'moneda' => 'PEN',
            'metodo_pago' => 'efectivo',
            'payment_method_id' => $cashMethod?->id,
            'fecha_pago' => ($row->fechaInicio ?? $matricula->fecha_inicio ?? now())->toDateTimeString(),
            'es_pago_parcial' => $esParcial,
            'saldo_pendiente' => $saldoObjetivo,
            'registrado_por' => $asesor->id,
        ]);

        return [
            'status' => 'updated',
            'warning' => $deletedTechnicalPayments === 0 ? 'No se encontro pago tecnico previo; se agrego el pago reconciliado igualmente.' : null,
            'updated_matricula' => true,
            'updated_pago' => true,
        ];
    }

    private function isAlreadySynced(
        ClienteMatricula $matricula,
        float $precioObjetivo,
        float $montoPagadoObjetivo,
        float $saldoObjetivo,
        bool $esParcial,
    ): bool {
        $latestPago = $matricula->pagos()->latest('created_at')->first();
        if (! $latestPago) {
            return false;
        }

        $hasTechnicalPayments = $matricula->pagos()
            ->where('metodo_pago', 'efectivo')
            ->where('saldo_pendiente', 0)
            ->where(function ($query) use ($matricula, $precioObjetivo): void {
                $query->where('monto', $matricula->precio_final)
                    ->orWhere('monto', $precioObjetivo);
            })
            ->exists();

        return (float) $matricula->precio_lista === $precioObjetivo
            && (float) $matricula->precio_final === $precioObjetivo
            && $matricula->modalidad_pago === 'contado'
            && ! $matricula->requiere_plan_cuotas
            && (float) $latestPago->monto === $montoPagadoObjetivo
            && (float) $latestPago->saldo_pendiente === $saldoObjetivo
            && (bool) $latestPago->es_pago_parcial === $esParcial
            && $matricula->pagos()->count() === 1
            && ! $hasTechnicalPayments;
    }

    private function deleteTechnicalImportPayments(ClienteMatricula $matricula, float $precioActual, float $precioObjetivo): int
    {
        return $matricula->pagos()
            ->where('metodo_pago', 'efectivo')
            ->where('saldo_pendiente', 0)
            ->where(function ($query) use ($precioActual, $precioObjetivo): void {
                $query->where('monto', $precioActual)
                    ->orWhere('monto', $precioObjetivo);
            })
            ->delete();
    }

    private function resolveCashMethod(): PaymentMethod
    {
        return PaymentMethod::withTrashed()->firstOrCreate(
            ['nombre' => 'Efectivo'],
            [
                'descripcion' => 'Pago en efectivo',
                'requiere_numero_operacion' => false,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ]
        );
    }
}
