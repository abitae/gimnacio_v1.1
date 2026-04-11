<?php

namespace App\Livewire\Clients\Routines;

use App\Models\Core\Cliente;
use Livewire\Component;

class Index extends Component
{
    public Cliente $cliente;

    public function mount(Cliente $cliente): void
    {
        $this->authorize('ejercicio_rutina.ver');
        $this->cliente = $cliente->load(['clientRoutines.trainer', 'clientRoutines.routineTemplate']);
    }

    public function pausar(int $id): void
    {
        $this->authorize('ejercicio_rutina.editar');
        $routine = $this->cliente->clientRoutines()->find($id);
        if ($routine && $routine->estado === 'activa') {
            $routine->update(['estado' => 'pausada']);
            $this->cliente->load('clientRoutines');
        }
    }

    public function finalizar(int $id): void
    {
        $this->authorize('ejercicio_rutina.editar');
        $routine = $this->cliente->clientRoutines()->find($id);
        if ($routine) {
            $routine->update(['estado' => 'finalizada', 'fecha_fin' => now()->toDateString()]);
            $this->cliente->load('clientRoutines');
        }
    }

    public function render()
    {
        return view('livewire.clients.routines.index');
    }
}
