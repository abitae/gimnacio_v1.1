<?php

namespace App\Livewire\Enrollments\Installments;

use App\Models\Core\ClienteMatricula;
use Livewire\Component;

class Schedule extends Component
{
    public ClienteMatricula $clienteMatricula;

    public function mount(ClienteMatricula $clienteMatricula): void
    {
        $this->authorize('cliente-matriculas.view');
        $this->clienteMatricula = $clienteMatricula->load(['cliente', 'membresia', 'clase', 'installmentPlan.installments']);
    }

    public function render()
    {
        $plan = $this->clienteMatricula->installmentPlan;
        $installments = $plan ? $plan->installments()->orderBy('numero_cuota')->get() : collect();

        return view('livewire.enrollments.installments.schedule', [
            'plan' => $plan,
            'installments' => $installments,
        ])->layout('layouts.app', ['title' => 'Cronograma de cuotas']);
    }
}
