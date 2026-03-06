<?php

namespace App\Livewire\Clients\Workouts;

use App\Livewire\Concerns\FlashesToast;
use App\Models\ClientRoutine;
use App\Models\ClientRoutineDay;
use App\Models\Core\Cliente;
use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use App\Models\WorkoutSessionSet;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public Cliente $cliente;
    public ClientRoutine $clientRoutine;
    public ?int $client_routine_day_id = null;
    public string $notas = '';
    /** @var array<int, array{exercise_id: int, client_routine_day_exercise_id: int|null, sets: array<int, array{peso: mixed, repeticiones: mixed, rpe: mixed, notas: string}>}> */
    public array $exercises = [];
    public bool $saved = false;

    public function mount(Cliente $cliente, ClientRoutine $clientRoutine): void
    {
        $this->authorize('ejercicios-rutinas.create');
        $this->cliente = $cliente;
        $this->clientRoutine = $clientRoutine->load(['days.exercises.exercise']);
    }

    public function updatedClientRoutineDayId(): void
    {
        $this->hydrateExercises();
    }

    protected function hydrateExercises(): void
    {
        $this->exercises = [];
        if (! $this->client_routine_day_id) {
            return;
        }
        $day = ClientRoutineDay::where('client_routine_id', $this->clientRoutine->id)->find($this->client_routine_day_id);
        if (! $day) {
            return;
        }
        foreach ($day->exercises as $i => $ex) {
            $this->exercises[] = [
                'exercise_id' => $ex->exercise_id,
                'client_routine_day_exercise_id' => $ex->id,
                'exercise_nombre' => $ex->exercise?->nombre ?? '',
                'sets' => [
                    ['peso' => null, 'repeticiones' => null, 'rpe' => null, 'notas' => ''],
                ],
            ];
        }
    }

    public function addSet(int $exerciseIndex): void
    {
        if (! isset($this->exercises[$exerciseIndex])) {
            return;
        }
        $this->exercises[$exerciseIndex]['sets'][] = ['peso' => null, 'repeticiones' => null, 'rpe' => null, 'notas' => ''];
    }

    public function removeSet(int $exerciseIndex, int $setIndex): void
    {
        if (! isset($this->exercises[$exerciseIndex]['sets'][$setIndex])) {
            return;
        }
        array_splice($this->exercises[$exerciseIndex]['sets'], $setIndex, 1);
        if (empty($this->exercises[$exerciseIndex]['sets'])) {
            $this->exercises[$exerciseIndex]['sets'][] = ['peso' => null, 'repeticiones' => null, 'rpe' => null, 'notas' => ''];
        }
    }

    public function guardar(): void
    {
        $this->authorize('ejercicios-rutinas.create');
        $this->validate([
            'client_routine_day_id' => ['required', 'exists:client_routine_days,id'],
        ], [
            'client_routine_day_id.required' => 'Selecciona el día de la rutina.',
        ]);
        $day = ClientRoutineDay::where('client_routine_id', $this->clientRoutine->id)->find($this->client_routine_day_id);
        if (! $day) {
            $this->flashToast('error', 'Día no válido.');
            return;
        }
        $session = WorkoutSession::create([
            'client_routine_id' => $this->clientRoutine->id,
            'client_routine_day_id' => $this->client_routine_day_id,
            'fecha_hora' => now(),
            'estado' => 'iniciada',
            'notas' => $this->notas ?: null,
            'registrado_por' => auth()->id(),
        ]);
        $orden = 0;
        foreach ($this->exercises as $ex) {
            $sessionEx = WorkoutSessionExercise::create([
                'workout_session_id' => $session->id,
                'exercise_id' => $ex['exercise_id'],
                'client_routine_day_exercise_id' => $ex['client_routine_day_exercise_id'] ?? null,
                'orden' => $orden++,
            ]);
            foreach ($ex['sets'] as $num => $set) {
                if ($set['peso'] !== null || $set['repeticiones'] !== null || $set['rpe'] !== null) {
                    WorkoutSessionSet::create([
                        'workout_session_exercise_id' => $sessionEx->id,
                        'set_numero' => $num + 1,
                        'peso' => $set['peso'],
                        'repeticiones' => $set['repeticiones'],
                        'rpe' => $set['rpe'],
                        'notas' => $set['notas'] ?: null,
                    ]);
                }
            }
        }
        $this->saved = true;
        $this->flashToast('success', 'Sesión registrada. Puedes completarla desde el detalle.');
        $this->redirect(route('clientes.sesiones.show', [$this->cliente, $session]), navigate: true);
    }

    public function completarSesion(): void
    {
        $this->guardar();
    }

    public function render()
    {
        if ($this->client_routine_day_id && empty($this->exercises)) {
            $this->hydrateExercises();
        }
        return view('livewire.clients.workouts.form');
    }
}
