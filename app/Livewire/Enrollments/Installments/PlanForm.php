<?php

namespace App\Livewire\Enrollments\Installments;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Services\EnrollmentInstallmentService;
use Illuminate\Http\Request;
use Livewire\Component;

class PlanForm extends Component
{
    use FlashesToast;

    public Cliente $cliente;

    public ClienteMatricula $clienteMatricula;

    public array $form = [
        'monto_total' => '',
        'numero_cuotas' => '',
        'frecuencia' => 'mensual',
        'fecha_inicio' => '',
        'observaciones' => '',
    ];

    public function mount(Cliente $cliente, Request $request): void
    {
        $this->authorize('cliente-matriculas.create');
        $mid = (int) ($request->query('matricula') ?? 0);
        $this->cliente = $cliente;
        $this->clienteMatricula = ClienteMatricula::query()
            ->where('cliente_id', $cliente->id)
            ->findOrFail($mid);

        if ($this->clienteMatricula->enrollmentInstallments()->exists()) {
            $this->flashToast('info', 'Esta matrícula ya tiene cuotas en el plan del cliente.');
            $this->redirectRoute('clientes.cuotas', [
                'cliente' => $this->cliente->id,
                'matricula' => $this->clienteMatricula->id,
            ], navigate: true);

            return;
        }

        $this->form['monto_total'] = (string) $this->clienteMatricula->precio_final;
        $this->form['fecha_inicio'] = $this->clienteMatricula->fecha_matricula?->format('Y-m-d') ?? now()->format('Y-m-d');
    }

    public function save(EnrollmentInstallmentService $service): void
    {
        $this->validate([
            'form.monto_total' => 'required|numeric|min:0.01',
            'form.numero_cuotas' => 'required|integer|min:2|max:60',
            'form.frecuencia' => 'required|in:semanal,quincenal,mensual,anual,personalizado',
            'form.fecha_inicio' => 'required|date',
        ]);

        try {
            $service->createPlan($this->clienteMatricula, $this->form);
            $this->flashToast('success', 'Cuotas registradas en el plan del cliente.');
            $this->redirectRoute('clientes.cuotas', [
                'cliente' => $this->cliente->id,
                'matricula' => $this->clienteMatricula->id,
            ], navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.enrollments.installments.plan-form')
            ->layout('layouts.app', ['title' => 'Crear plan de cuotas']);
    }
}
