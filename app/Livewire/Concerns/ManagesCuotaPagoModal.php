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

    public float $pagoCuotaSaldoPendiente = 0.0;

    public array $pagoCuotaForm = [
        'monto' => '',
        'descuento_monto' => '',
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

        $this->pagoCuotaInstallmentId = $installmentId;
        $this->pagoCuotaSaldoPendiente = round((float) $inst->saldo_pendiente, 2);
        $monto = (string) $this->pagoCuotaSaldoPendiente;
        $this->pagoCuotaForm = [
            'monto' => $monto,
            'descuento_monto' => '0',
            'fecha_pago' => now()->format('Y-m-d'),
            'pagos' => [$this->nuevaLineaPagoCuota($monto, $this->metodoEfectivoIdCuota())],
        ];
        $this->cuotaPagoModalAbierto = true;
    }

    public function closeCuotaPagoModal(): void
    {
        $this->cuotaPagoModalAbierto = false;
        $this->pagoCuotaInstallmentId = null;
        $this->pagoCuotaSaldoPendiente = 0.0;
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

    public function updatedPagoCuotaFormDescuentoMonto($value): void
    {
        $descuento = max(0, round((float) $value, 2));
        $descuento = min($descuento, $this->pagoCuotaSaldoPendiente);
        $monto = max(0, round($this->pagoCuotaSaldoPendiente - $descuento, 2));

        $this->pagoCuotaForm['descuento_monto'] = (string) $descuento;
        $this->pagoCuotaForm['monto'] = (string) $monto;

        if (count($this->pagoCuotaForm['pagos'] ?? []) === 1) {
            $this->pagoCuotaForm['pagos'][0]['monto'] = $monto > 0 ? (string) $monto : '0';
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

        $monto = round((float) ($this->pagoCuotaForm['monto'] ?? 0), 2);
        $descuento = round((float) ($this->pagoCuotaForm['descuento_monto'] ?? 0), 2);

        $rules = [
            'pagoCuotaForm.monto' => 'required|numeric|min:0',
            'pagoCuotaForm.descuento_monto' => 'required|numeric|min:0',
            'pagoCuotaForm.fecha_pago' => 'required|date',
        ];

        if ($monto > 0) {
            $rules['pagoCuotaForm.pagos'] = 'required|array|min:1|max:2';
            $rules['pagoCuotaForm.pagos.*.payment_method_id'] = 'required|exists:payment_methods,id';
            $rules['pagoCuotaForm.pagos.*.monto'] = 'required|numeric|min:0.01';
        }

        $this->validate($rules, [], [
            'pagoCuotaForm.monto' => 'monto',
            'pagoCuotaForm.descuento_monto' => 'descuento',
        ]);

        if (round($monto + $descuento, 2) <= 0) {
            $this->addError('pagoCuotaForm.monto', __('El monto aplicado (efectivo + descuento) debe ser mayor a cero.'));

            return;
        }

        if (round($monto + $descuento, 2) > round($this->pagoCuotaSaldoPendiente, 2)) {
            $this->addError('pagoCuotaForm.monto', __('El monto aplicado no puede superar el saldo pendiente de la cuota.'));

            return;
        }

        $inst = EnrollmentInstallment::query()
            ->with('plan.cliente')
            ->find($this->pagoCuotaInstallmentId);

        if (! $inst) {
            $this->flashToast('error', __('Cuota no encontrada.'));

            return;
        }

        try {
            $payload = [
                'monto' => $monto,
                'descuento_monto' => $descuento,
                'fecha_pago' => $this->pagoCuotaForm['fecha_pago'],
            ];

            if ($monto > 0) {
                $payload['pagos'] = $this->pagoCuotaForm['pagos'];
            }

            $pago = app(EnrollmentInstallmentService::class)->pagarCuota($inst, $payload);
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
