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
        'pagos' => [],
        'caja_id' => null,
    ];

    public function mount(EnrollmentInstallment $installment): void
    {
        $this->authorize('matricula_cliente.editar');
        $this->installment = $installment->load(['plan.cliente', 'clienteMatricula.cliente']);
        $this->form['monto'] = (string) $installment->saldo_pendiente;
        $this->form['fecha_pago'] = now()->format('Y-m-d');
        $efectivoId = PaymentMethod::activos()->whereRaw('LOWER(nombre) = ?', ['efectivo'])->value('id')
            ?? PaymentMethod::activos()->orderBy('nombre')->value('id');
        $this->form['pagos'] = [$this->nuevaLinea((string) $installment->saldo_pendiente, $efectivoId)];
        $cajaAbierta = \App\Models\Core\Caja::where('estado', 'abierta')->first();
        $this->form['caja_id'] = $cajaAbierta?->id;
    }

    public function save(EnrollmentInstallmentService $service): void
    {
        $this->validate([
            'form.monto' => 'required|numeric|min:0.01',
            'form.fecha_pago' => 'required|date',
            'form.pagos' => 'required|array|min:1|max:2',
            'form.pagos.*.payment_method_id' => 'required|exists:payment_methods,id',
            'form.pagos.*.monto' => 'required|numeric|min:0.01',
        ]);

        try {
            $service->pagarCuota($this->installment, [
                'monto' => $this->form['monto'],
                'fecha_pago' => $this->form['fecha_pago'],
                'pagos' => $this->form['pagos'],
                'caja_id' => $this->form['caja_id'],
            ]);
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
