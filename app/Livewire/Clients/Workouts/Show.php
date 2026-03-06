<?php

namespace App\Livewire\Clients\Workouts;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\WorkoutSession;
use Livewire\Component;

class Show extends Component
{
    use FlashesToast;

    public Cliente $cliente;
    public WorkoutSession $workoutSession;

    public function mount(Cliente $cliente, WorkoutSession $workoutSession): void
    {
        $this->authorize('ejercicios-rutinas.view');
        $this->cliente = $cliente;
        $this->workoutSession = $workoutSession->load(['sessionExercises.exercise', 'sessionExercises.sets', 'clientRoutineDay', 'clientRoutine']);
    }

    public function completar(): void
    {
        $this->authorize('ejercicios-rutinas.update');
        $this->workoutSession->update(['estado' => 'completada']);
        $this->workoutSession->load(['sessionExercises.exercise', 'sessionExercises.sets']);
        $this->flashToast('success', 'Sesión marcada como completada.');
    }

    public function render()
    {
        return view('livewire.clients.workouts.show');
    }
}
