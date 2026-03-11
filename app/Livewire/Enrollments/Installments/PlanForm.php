<?php

namespace App\Livewire\Enrollments\Installments;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\ClienteMatricula;
use App\Services\EnrollmentInstallmentService;
use Livewire\Component;

class PlanForm extends Component
{
    use FlashesToast;

    public ClienteMatricula $clienteMatricula;

    public array $form = [
        'monto_total' => '',
        'numero_cuotas' => '',
        'frecuencia' => 'mensual',
        'fecha_inicio' => '',
        'observaciones' => '',
    ];

    public function mount(ClienteMatricula $clienteMatricula): void
    {
        $this->authorize('cliente-matriculas.create');
        if ($clienteMatricula->installmentPlan) {
            $this->flashToast('info', 'Esta matrícula ya tiene un plan de cuotas.');
            $this->redirectRoute('cliente-matriculas.cuotas', ['clienteMatricula' => $clienteMatricula], navigate: true);
            return;
        }
        $this->clienteMatricula = $clienteMatricula;
        $this->form['monto_total'] = (string) $clienteMatricula->precio_final;
        $this->form['fecha_inicio'] = $clienteMatricula->fecha_matricula?->format('Y-m-d') ?? now()->format('Y-m-d');
    }

    public function save(EnrollmentInstallmentService $service): void
    {
        $this->validate([
            'form.monto_total' => 'required|numeric|min:0.01',
            'form.numero_cuotas' => 'required|integer|min:2|max:60',
            'form.frecuencia' => 'required|in:semanal,quincenal,mensual,personalizado',
            'form.fecha_inicio' => 'required|date',
        ]);

        try {
            $service->createPlan($this->clienteMatricula, $this->form);
            $this->flashToast('success', 'Plan de cuotas creado.');
            $this->redirectRoute('cliente-matriculas.cuotas', ['clienteMatricula' => $this->clienteMatricula], navigate: true);
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
