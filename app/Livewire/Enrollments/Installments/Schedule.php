<?php

namespace App\Livewire\Enrollments\Installments;

use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\ManagesCuotaPagoModal;
use App\Models\Core\Cliente;
use Illuminate\Http\Request;
use Livewire\Component;

class Schedule extends Component
{
    use FlashesToast;
    use ManagesCuotaPagoModal;

    public Cliente $cliente;

    public ?int $highlightMatriculaId = null;

    public function mount(Cliente $cliente, Request $request): void
    {
        $this->authorize('cliente-matriculas.view');
        $this->cliente = $cliente->load([
            'enrollmentInstallmentPlan.installments.clienteMatricula.membresia',
            'enrollmentInstallmentPlan.installments.clienteMatricula.clase',
        ]);
        $m = $request->query('matricula');
        $this->highlightMatriculaId = $m !== null && $m !== '' ? (int) $m : null;
    }

    protected function cuotaPagoClienteIdScope(): ?int
    {
        return (int) $this->cliente->id;
    }

    protected function afterCuotaPagoRegistrado(?\App\Models\Core\Pago $pago = null): void
    {
        $this->cliente->refresh();
        $this->cliente->load([
            'enrollmentInstallmentPlan.installments.clienteMatricula.membresia',
            'enrollmentInstallmentPlan.installments.clienteMatricula.clase',
        ]);
    }

    public function render()
    {
        $plan = $this->cliente->enrollmentInstallmentPlan;
        $installments = $plan
            ? $plan->installments()
                ->with(['clienteMatricula.membresia', 'clienteMatricula.clase'])
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->get()
            : collect();

        $paymentMethods = $this->cuotaPagoModalAbierto
            ? $this->paymentMethodsForCuotaModal()
            : collect();

        return view('livewire.enrollments.installments.schedule', [
            'plan' => $plan,
            'installments' => $installments,
            'paymentMethods' => $paymentMethods,
        ])->layout('layouts.app', ['title' => 'Cronograma de cuotas']);
    }
}
