<?php

namespace App\Livewire\Reports;

use App\Models\Core\Cliente;
use App\Models\WorkoutSessionSet;
use Livewire\Component;

class ProgressByExercise extends Component
{
    public string $tipo_documento = 'DNI';
    public string $numero_documento = '';
    public ?Cliente $cliente = null;

    public function mount(): void
    {
        $this->authorize('ejercicios-rutinas.view');
    }

    public function buscarCliente(): void
    {
        $this->validate([
            'tipo_documento' => ['required', 'in:DNI,CE'],
            'numero_documento' => ['required', 'string', 'max:20'],
        ]);
        $this->cliente = Cliente::where('tipo_documento', $this->tipo_documento)
            ->where('numero_documento', $this->numero_documento)
            ->first();
        if (! $this->cliente) {
            session()->flash('error', 'No se encontró un cliente con ese documento.');
        }
    }

    public function render()
    {
        $progress = [];
        if ($this->cliente) {
            $routineIds = $this->cliente->clientRoutines()->pluck('id');
            $sessionIds = \App\Models\WorkoutSession::whereIn('client_routine_id', $routineIds)->pluck('id');
            $exerciseIds = WorkoutSessionSet::query()
                ->join('workout_session_exercises', 'workout_session_sets.workout_session_exercise_id', '=', 'workout_session_exercises.id')
                ->whereIn('workout_session_exercises.workout_session_id', $sessionIds)
                ->whereNotNull('workout_session_sets.peso')
                ->distinct()
                ->pluck('workout_session_exercises.exercise_id');
            $exercises = \App\Models\Exercise::whereIn('id', $exerciseIds)->get()->keyBy('id');
            foreach ($exerciseIds as $eid) {
                $lastSet = WorkoutSessionSet::query()
                    ->join('workout_session_exercises', 'workout_session_sets.workout_session_exercise_id', '=', 'workout_session_exercises.id')
                    ->join('workout_sessions', 'workout_session_exercises.workout_session_id', '=', 'workout_sessions.id')
                    ->whereIn('workout_sessions.client_routine_id', $routineIds)
                    ->where('workout_session_exercises.exercise_id', $eid)
                    ->whereNotNull('workout_session_sets.peso')
                    ->orderByDesc('workout_sessions.fecha_hora')
                    ->select('workout_session_sets.peso', 'workout_session_sets.repeticiones', 'workout_sessions.fecha_hora')
                    ->first();
                $maxPeso = WorkoutSessionSet::query()
                    ->join('workout_session_exercises', 'workout_session_sets.workout_session_exercise_id', '=', 'workout_session_exercises.id')
                    ->join('workout_sessions', 'workout_session_exercises.workout_session_id', '=', 'workout_sessions.id')
                    ->whereIn('workout_sessions.client_routine_id', $routineIds)
                    ->where('workout_session_exercises.exercise_id', $eid)
                    ->whereNotNull('workout_session_sets.peso')
                    ->max('workout_session_sets.peso');
                $prevSet = WorkoutSessionSet::query()
                    ->join('workout_session_exercises', 'workout_session_sets.workout_session_exercise_id', '=', 'workout_session_exercises.id')
                    ->join('workout_sessions', 'workout_session_exercises.workout_session_id', '=', 'workout_sessions.id')
                    ->whereIn('workout_sessions.client_routine_id', $routineIds)
                    ->where('workout_session_exercises.exercise_id', $eid)
                    ->whereNotNull('workout_session_sets.peso')
                    ->orderByDesc('workout_sessions.fecha_hora')
                    ->offset(1)
                    ->limit(1)
                    ->select('workout_session_sets.peso')
                    ->first();
                $tendencia = '=';
                if ($lastSet && $prevSet && (float) $lastSet->peso > (float) $prevSet->peso) {
                    $tendencia = '↑';
                } elseif ($lastSet && $prevSet && (float) $lastSet->peso < (float) $prevSet->peso) {
                    $tendencia = '↓';
                }
                $progress[] = [
                    'exercise_nombre' => $exercises->get($eid)?->nombre ?? '—',
                    'ultimo_peso' => $lastSet ? (float) $lastSet->peso : null,
                    'mejor_peso' => $maxPeso !== null ? (float) $maxPeso : null,
                    'tendencia' => $tendencia,
                ];
            }
        }
        return view('livewire.reports.progress-by-exercise', ['progress' => $progress]);
    }
}
