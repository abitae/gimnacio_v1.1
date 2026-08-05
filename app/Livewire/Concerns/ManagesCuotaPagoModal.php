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
        'pagos' => [],
    ];

    /**
     * Si no es null, solo se permiten cuotas cuyo plan pertenezca a este cliente_id.
     */
    abstract protected function cuotaPagoClienteIdScope(): ?int;

    abstract protected function afterCuotaPagoRegistrado(?Pago $pago = null): void;

    public function openRegistrarPagoCuota(int $installmentId): void
    {
        $this->authorize('matricula_cliente.editar');

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

        if (! app(EnrollmentInstallmentService::class)->isFirstPayableInstallment($inst)) {
            $this->flashToast('error', __('Primero debes pagar la cuota pendiente más inmediata de esta matrícula.'));

            return;
        }

        $this->pagoCuotaInstallmentId = $installmentId;
        $monto = (string) $inst->saldo_pendiente;
        $this->pagoCuotaForm = [
            'monto' => $monto,
            'fecha_pago' => now()->format('Y-m-d'),
            'pagos' => [$this->nuevaLineaPagoCuota($monto, $this->metodoEfectivoIdCuota())],
        ];
        $this->cuotaPagoModalAbierto = true;
    }

    public function closeCuotaPagoModal(): void
    {
        $this->cuotaPagoModalAbierto = false;
        $this->pagoCuotaInstallmentId = null;
    }

    public function agregarFormaPagoCuota(): void
    {
        if (count($this->pagoCuotaForm['pagos'] ?? []) >= 2) {
            return;
        }

        $this->pagoCuotaForm['pagos'][] = $this->nuevaLineaPagoCuota('');
    }

    public function quitarFormaPagoCuota(int $index): void
    {
        if ($index === 0 || count($this->pagoCuotaForm['pagos'] ?? []) <= 1) {
            return;
        }

        unset($this->pagoCuotaForm['pagos'][$index]);
        $this->pagoCuotaForm['pagos'] = array_values($this->pagoCuotaForm['pagos']);
    }

    public function updatedPagoCuotaFormMonto($value): void
    {
        if (count($this->pagoCuotaForm['pagos'] ?? []) === 1) {
            $this->pagoCuotaForm['pagos'][0]['monto'] = $value;
        }
    }

    public function getPagoCuotaTotalAsignadoProperty(): float
    {
        return round((float) collect($this->pagoCuotaForm['pagos'] ?? [])->sum(fn ($linea) => (float) ($linea['monto'] ?? 0)), 2);
    }

    public function getPagoCuotaDiferenciaProperty(): float
    {
        return round((float) ($this->pagoCuotaForm['monto'] ?? 0) - $this->pagoCuotaTotalAsignado, 2);
    }

    public function guardarPagoCuota(): void
    {
        $this->authorize('matricula_cliente.editar');
        $this->validate([
            'pagoCuotaForm.monto' => 'required|numeric|min:0.01',
            'pagoCuotaForm.fecha_pago' => 'required|date',
            'pagoCuotaForm.pagos' => 'required|array|min:1|max:2',
            'pagoCuotaForm.pagos.*.payment_method_id' => 'required|exists:payment_methods,id',
            'pagoCuotaForm.pagos.*.monto' => 'required|numeric|min:0.01',
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
                'pagos' => $this->pagoCuotaForm['pagos'],
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

    protected function metodoEfectivoIdCuota(): ?int
    {
        return PaymentMethod::activos()
            ->whereRaw('LOWER(nombre) = ?', ['efectivo'])
            ->value('id') ?? PaymentMethod::activos()->orderBy('nombre')->value('id');
    }

    protected function nuevaLineaPagoCuota(string|float $monto = '', ?int $paymentMethodId = null): array
    {
        return [
            'payment_method_id' => $paymentMethodId,
            'monto' => $monto,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
    }
}
