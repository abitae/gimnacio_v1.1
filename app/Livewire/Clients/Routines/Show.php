<?php

namespace App\Livewire\Clients\Routines;

use App\Models\ClientRoutine;
use App\Models\Core\Cliente;
use Livewire\Component;

class Show extends Component
{
    public Cliente $cliente;
    public ClientRoutine $clientRoutine;

    public function mount(Cliente $cliente, ClientRoutine $clientRoutine): void
    {
        $this->authorize('ejercicios-rutinas.view');
        $this->cliente = $cliente;
        $this->clientRoutine = $clientRoutine->load(['days.exercises.exercise', 'routineTemplate', 'trainer']);
    }

    public function render()
    {
        return view('livewire.clients.routines.show');
    }
}
