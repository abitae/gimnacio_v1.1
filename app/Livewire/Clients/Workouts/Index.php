<?php

namespace App\Livewire\Clients\Workouts;

use App\Models\ClientRoutine;
use App\Models\Core\Cliente;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public Cliente $cliente;
    public ClientRoutine $clientRoutine;

    protected $paginationTheme = 'tailwind';

    public function mount(Cliente $cliente, ClientRoutine $clientRoutine): void
    {
        $this->authorize('ejercicios-rutinas.view');
        $this->cliente = $cliente;
        $this->clientRoutine = $clientRoutine;
    }

    public function render()
    {
        $sessions = $this->clientRoutine->workoutSessions()->orderByDesc('fecha_hora')->paginate(15);
        return view('livewire.clients.workouts.index', ['sessions' => $sessions]);
    }
}
