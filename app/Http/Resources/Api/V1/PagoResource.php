<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Core\Pago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pago
 */
class PagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'monto' => round((float) $this->monto, 2),
            'moneda' => $this->moneda ?? 'PEN',
            'metodo_pago' => $this->paymentMethod?->nombre ?? $this->metodo_pago,
            'fecha_pago' => $this->fechaHoraPago()->toIso8601String(),
            'concepto' => $this->conceptoPortal(),
            'es_pago_parcial' => (bool) $this->es_pago_parcial,
        ];
    }

    protected function conceptoPortal(): string
    {
        if ($this->enrollment_installment_id) {
            $numero = $this->enrollmentInstallment?->numero_cuota;
            $nombre = $this->clienteMatricula?->nombre
                ?? $this->enrollmentInstallment?->clienteMatricula?->nombre;

            return 'Cuota'.($numero ? ' '.$numero : '').($nombre ? ' - '.$nombre : '');
        }

        if ($this->cliente_matricula_id) {
            return $this->clienteMatricula?->nombre ?? 'Matrícula';
        }

        if ($this->client_debt_id) {
            return $this->clientDebt?->referencia ?? 'Cuenta por cobrar';
        }

        if ($this->cliente_membresia_id) {
            return $this->clienteMembresia?->membresia?->nombre ?? 'Membresía';
        }

        return 'Pago';
    }
}
