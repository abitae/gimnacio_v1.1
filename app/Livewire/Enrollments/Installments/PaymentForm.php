<?php

namespace App\Livewire\Enrollments\Installments;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\PaymentMethod;
use App\Services\EnrollmentInstallmentService;
use Livewire\Component;

class PaymentForm extends Component
{
    use FlashesToast;

    public EnrollmentInstallment $installment;

    public float $saldoPendiente = 0.0;

    public array $form = [
        'monto' => '',
        'descuento_monto' => '',
        'fecha_pago' => '',
        'pagos' => [],
        'caja_id' => null,
    ];

    public function mount(EnrollmentInstallment $installment): void
    {
        $this->authorize('matricula_cliente.editar');
        $this->installment = $installment->load(['plan.cliente', 'clienteMatricula.cliente']);
        $this->saldoPendiente = round((float) $installment->saldo_pendiente, 2);
        $this->form['monto'] = (string) $this->saldoPendiente;
        $this->form['descuento_monto'] = '0';
        $this->form['fecha_pago'] = now()->format('Y-m-d');
        $efectivoId = PaymentMethod::activos()->whereRaw('LOWER(nombre) = ?', ['efectivo'])->value('id')
            ?? PaymentMethod::activos()->orderBy('nombre')->value('id');
        $this->form['pagos'] = [$this->nuevaLinea((string) $this->saldoPendiente, $efectivoId)];
        $cajaAbierta = \App\Models\Core\Caja::where('estado', 'abierta')->first();
        $this->form['caja_id'] = $cajaAbierta?->id;
    }

    public function save(EnrollmentInstallmentService $service): void
    {
        $monto = round((float) ($this->form['monto'] ?? 0), 2);
        $descuento = round((float) ($this->form['descuento_monto'] ?? 0), 2);

        $rules = [
            'form.monto' => 'required|numeric|min:0',
            'form.descuento_monto' => 'required|numeric|min:0',
            'form.fecha_pago' => 'required|date',
        ];

        if ($monto > 0) {
            $rules['form.pagos'] = 'required|array|min:1|max:2';
            $rules['form.pagos.*.payment_method_id'] = 'required|exists:payment_methods,id';
            $rules['form.pagos.*.monto'] = 'required|numeric|min:0.01';
        }

        $this->validate($rules);

        if ($descuento > 0 && ! $this->puedeAplicarDescuento) {
            $this->authorize('matricula_cliente.aplicar_descuento');
        }

        if (round($monto + $descuento, 2) <= 0) {
            $this->addError('form.monto', __('El monto aplicado (efectivo + descuento) debe ser mayor a cero.'));

            return;
        }

        if (round($monto + $descuento, 2) > round($this->saldoPendiente, 2)) {
            $this->addError('form.monto', __('El monto aplicado no puede superar el saldo pendiente de la cuota.'));

            return;
        }

        try {
            $payload = [
                'monto' => $monto,
                'descuento_monto' => $descuento,
                'fecha_pago' => $this->form['fecha_pago'],
                'caja_id' => $this->form['caja_id'],
            ];

            if ($monto > 0) {
                $payload['pagos'] = $this->form['pagos'];
            }

            $service->pagarCuota($this->installment, $payload);
            $this->flashToast('success', 'Cuota registrada.');
            $this->redirectRoute('clientes.cuotas', [
                'cliente' => $this->installment->plan->cliente_id,
                'matricula' => $this->installment->cliente_matricula_id,
            ], navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();

        return view('livewire.enrollments.installments.payment-form', [
            'paymentMethods' => $paymentMethods,
        ])->layout('layouts.app', ['title' => 'Pagar cuota']);
    }

    public function agregarFormaPago(): void
    {
        if (count($this->form['pagos'] ?? []) < 2) {
            $this->form['pagos'][] = $this->nuevaLinea();
        }
    }

    public function quitarFormaPago(int $index): void
    {
        if ($index === 0 || count($this->form['pagos'] ?? []) <= 1) {
            return;
        }
        unset($this->form['pagos'][$index]);
        $this->form['pagos'] = array_values($this->form['pagos']);
    }

    public function updatedFormMonto($value): void
    {
        if (count($this->form['pagos'] ?? []) === 1) {
            $this->form['pagos'][0]['monto'] = $value;
        }
    }

    public function getPuedeAplicarDescuentoProperty(): bool
    {
        return (bool) auth()->user()?->can('matricula_cliente.aplicar_descuento');
    }

    public function updatedFormDescuentoMonto($value): void
    {
        $descuento = $this->puedeAplicarDescuento ? max(0, round((float) $value, 2)) : 0.0;
        $descuento = min($descuento, $this->saldoPendiente);
        $monto = max(0, round($this->saldoPendiente - $descuento, 2));

        $this->form['descuento_monto'] = (string) $descuento;
        $this->form['monto'] = (string) $monto;

        if (count($this->form['pagos'] ?? []) === 1) {
            $this->form['pagos'][0]['monto'] = $monto > 0 ? (string) $monto : '0';
        }
    }

    public function getTotalAsignadoProperty(): float
    {
        return round((float) collect($this->form['pagos'] ?? [])->sum(fn ($linea) => (float) ($linea['monto'] ?? 0)), 2);
    }

    public function getDiferenciaProperty(): float
    {
        return round((float) ($this->form['monto'] ?? 0) - $this->totalAsignado, 2);
    }

    protected function nuevaLinea(string|float $monto = '', ?int $paymentMethodId = null): array
    {
        return [
            'payment_method_id' => $paymentMethodId,
            'monto' => $monto,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
    }
}
