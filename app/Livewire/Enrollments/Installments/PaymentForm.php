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

    public array $form = [
        'monto' => '',
        'fecha_pago' => '',
        'payment_method_id' => '',
        'numero_operacion' => '',
        'entidad_financiera' => '',
        'caja_id' => null,
    ];

    public function mount(EnrollmentInstallment $installment): void
    {
        $this->authorize('cliente-matriculas.update');
        $this->installment = $installment->load(['plan.clienteMatricula.cliente']);
        $this->form['monto'] = (string) $installment->monto;
        $this->form['fecha_pago'] = now()->format('Y-m-d');
        $cajaAbierta = \App\Models\Core\Caja::where('estado', 'abierta')->first();
        $this->form['caja_id'] = $cajaAbierta?->id;
    }

    public function save(EnrollmentInstallmentService $service): void
    {
        $this->validate([
            'form.monto' => 'required|numeric|min:0.01',
            'form.fecha_pago' => 'required|date',
            'form.payment_method_id' => 'nullable|exists:payment_methods,id',
        ]);

        try {
            $service->pagarCuota($this->installment, [
                'monto' => $this->form['monto'],
                'fecha_pago' => $this->form['fecha_pago'],
                'payment_method_id' => $this->form['payment_method_id'] ?: null,
                'numero_operacion' => $this->form['numero_operacion'] ?: null,
                'entidad_financiera' => $this->form['entidad_financiera'] ?: null,
                'caja_id' => $this->form['caja_id'],
            ]);
            $this->flashToast('success', 'Cuota registrada.');
            $this->redirectRoute('cliente-matriculas.cuotas', ['clienteMatricula' => $this->installment->plan->clienteMatricula], navigate: true);
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
}
