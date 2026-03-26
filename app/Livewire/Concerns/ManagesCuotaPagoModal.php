<?php

namespace App\Livewire\Concerns;

use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use App\Services\EnrollmentInstallmentService;

/**
 * Modal de registro de pago de una cuota (sin navegar a /cuotas/{id}/pagar).
 */
trait ManagesCuotaPagoModal
{
    public bool $cuotaPagoModalAbierto = false;

    public ?int $pagoCuotaInstallmentId = null;

    public array $pagoCuotaForm = [
        'monto' => '',
        'fecha_pago' => '',
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
    ];

    /**
     * Si no es null, solo se permiten cuotas cuyo plan pertenezca a este cliente_id.
     */
    abstract protected function cuotaPagoClienteIdScope(): ?int;

    abstract protected function afterCuotaPagoRegistrado(?Pago $pago = null): void;

    public function openRegistrarPagoCuota(int $installmentId): void
    {
        $this->authorize('cliente-matriculas.update');

        $inst = EnrollmentInstallment::query()
            ->with('plan.cliente')
            ->find($installmentId);

        if (! $inst || ! $inst->plan) {
            $this->flashToast('error', __('Cuota no encontrada.'));

            return;
        }

        $scope = $this->cuotaPagoClienteIdScope();
        if ($scope !== null && (int) $inst->plan->cliente_id !== (int) $scope) {
            $this->flashToast('error', __('La cuota no pertenece a este cliente.'));

            return;
        }

        if (! in_array($inst->estado, ['pendiente', 'vencida', 'parcial'], true)) {
            $this->flashToast('error', __('Esta cuota ya no admite pago.'));

            return;
        }

        $this->pagoCuotaInstallmentId = $installmentId;
        $this->pagoCuotaForm = [
            'monto' => (string) $inst->monto,
            'fecha_pago' => now()->format('Y-m-d'),
            'payment_method_id' => null,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
        $this->cuotaPagoModalAbierto = true;
    }

    public function closeCuotaPagoModal(): void
    {
        $this->cuotaPagoModalAbierto = false;
        $this->pagoCuotaInstallmentId = null;
    }

    public function guardarPagoCuota(): void
    {
        $this->authorize('cliente-matriculas.update');
        $this->validate([
            'pagoCuotaForm.monto' => 'required|numeric|min:0.01',
            'pagoCuotaForm.fecha_pago' => 'required|date',
            'pagoCuotaForm.payment_method_id' => 'nullable|exists:payment_methods,id',
        ], [], [
            'pagoCuotaForm.monto' => 'monto',
        ]);

        $inst = EnrollmentInstallment::query()
            ->with('plan.cliente')
            ->find($this->pagoCuotaInstallmentId);

        if (! $inst) {
            $this->flashToast('error', __('Cuota no encontrada.'));

            return;
        }

        try {
            $pago = app(EnrollmentInstallmentService::class)->pagarCuota($inst, [
                'monto' => (float) $this->pagoCuotaForm['monto'],
                'fecha_pago' => $this->pagoCuotaForm['fecha_pago'],
                'payment_method_id' => $this->pagoCuotaForm['payment_method_id'] ?: null,
                'numero_operacion' => $this->pagoCuotaForm['numero_operacion'] ?: null,
                'entidad_financiera' => $this->pagoCuotaForm['entidad_financiera'] ?: null,
            ]);
            $this->flashToast('success', __('Pago de cuota registrado.'));
            $this->closeCuotaPagoModal();
            $this->afterCuotaPagoRegistrado($pago);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function paymentMethodsForCuotaModal(): \Illuminate\Support\Collection
    {
        return PaymentMethod::activos()->orderBy('nombre')->get();
    }
}
