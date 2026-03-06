<?php

namespace App\Livewire\Routines\Templates;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Exercise;
use App\Models\RoutineTemplate;
use App\Models\RoutineTemplateDay;
use App\Models\RoutineTemplateDayExercise;
use Livewire\Component;

class Builder extends Component
{
    use FlashesToast;

    public RoutineTemplate $template;

    /** @var array<int, array{id: int|null, nombre: string, orden: int, exercises: array}> */
    public array $days = [];

    /** Para agregar ejercicio a un día: [dayIndex => ['exercise_id' => int, 'series' => int, 'repeticiones' => string, ...]] */
    public array $newExercise = [];

    public function mount(RoutineTemplate $template): void
    {
        $this->authorize('ejercicios-rutinas.update');
        $this->template = $template->load(['days.exercises.exercise']);
        $this->hydrateDays();
    }

    protected function hydrateDays(): void
    {
        $this->days = [];
        foreach ($this->template->days as $day) {
            $exercises = [];
            foreach ($day->exercises as $ex) {
                $exercises[] = [
                    'id' => $ex->id,
                    'exercise_id' => $ex->exercise_id,
                    'exercise_nombre' => $ex->exercise?->nombre ?? '',
                    'series' => $ex->series,
                    'repeticiones' => $ex->repeticiones ?? '',
                    'descanso_segundos' => $ex->descanso_segundos,
                    'tempo' => $ex->tempo ?? '',
                    'intensidad_rpe' => $ex->intensidad_rpe ?? '',
                    'metodo' => $ex->metodo ?? 'normal',
                    'notas' => $ex->notas ?? '',
                    'orden' => $ex->orden,
                ];
            }
            $this->days[] = [
                'id' => $day->id,
                'nombre' => $day->nombre,
                'orden' => $day->orden,
                'exercises' => $exercises,
            ];
            $this->newExercise[$day->id] = [
                'exercise_id' => null,
                'series' => 3,
                'repeticiones' => '8-12',
                'descanso_segundos' => 90,
                'tempo' => '',
                'intensidad_rpe' => '',
                'metodo' => 'normal',
                'notas' => '',
            ];
        }
    }

    public function addDay(): void
    {
        $maxOrden = 0;
        foreach ($this->days as $d) {
            if (isset($d['orden']) && $d['orden'] >= $maxOrden) {
                $maxOrden = $d['orden'] + 1;
            }
        }
        $day = new RoutineTemplateDay;
        $day->routine_template_id = $this->template->id;
        $day->nombre = 'Día ' . (count($this->days) + 1);
        $day->orden = $maxOrden;
        $day->save();
        $this->template->load(['days.exercises']);
        $this->hydrateDays();
        $this->newExercise[$day->id] = [
            'exercise_id' => null,
            'series' => 3,
            'repeticiones' => '8-12',
            'descanso_segundos' => 90,
            'tempo' => '',
            'intensidad_rpe' => '',
            'metodo' => 'normal',
            'notas' => '',
        ];
        $this->flashToast('success', 'Día agregado.');
    }

    public function saveDayName(int $dayIndex): void
    {
        $day = $this->days[$dayIndex] ?? null;
        if (! $day || ! isset($day['id'])) {
            return;
        }
        $nombre = $day['nombre'] ?? '';
        $this->updateDayName($day['id'], $nombre);
    }

    public function updateDayName(int $dayId, string $nombre): void
    {
        $day = RoutineTemplateDay::where('routine_template_id', $this->template->id)->find($dayId);
        if ($day) {
            $day->update(['nombre' => $nombre]);
            foreach ($this->days as $i => $d) {
                if (($d['id'] ?? null) === $dayId) {
                    $this->days[$i]['nombre'] = $nombre;
                    break;
                }
            }
        }
    }

    public function removeDay(int $dayId): void
    {
        $day = RoutineTemplateDay::where('routine_template_id', $this->template->id)->find($dayId);
        if ($day) {
            $day->delete();
            $this->template->load(['days.exercises']);
            $this->hydrateDays();
            $this->flashToast('success', 'Día eliminado.');
        }
    }

    public function addExerciseToDay(int $dayId): void
    {
        $key = array_search($dayId, array_column($this->days, 'id'));
        if ($key === false) {
            return;
        }
        $data = $this->newExercise[$dayId] ?? [];
        $exerciseId = $data['exercise_id'] ?? null;
        if (! $exerciseId) {
            $this->flashToast('error', 'Selecciona un ejercicio.');
            return;
        }
        $day = RoutineTemplateDay::where('routine_template_id', $this->template->id)->find($dayId);
        if (! $day) {
            return;
        }
        $maxOrden = $day->exercises()->max('orden') ?? 0;
        RoutineTemplateDayExercise::create([
            'routine_template_day_id' => $dayId,
            'exercise_id' => $exerciseId,
            'series' => (int) ($data['series'] ?? 3),
            'repeticiones' => $data['repeticiones'] ?? '8-12',
            'descanso_segundos' => ! empty($data['descanso_segundos']) ? (int) $data['descanso_segundos'] : null,
            'tempo' => $data['tempo'] ?? null,
            'intensidad_rpe' => $data['intensidad_rpe'] ?? null,
            'metodo' => $data['metodo'] ?? 'normal',
            'notas' => $data['notas'] ?? null,
            'orden' => $maxOrden + 1,
        ]);
        $this->template->load(['days.exercises.exercise']);
        $this->hydrateDays();
        $this->newExercise[$dayId] = [
            'exercise_id' => null,
            'series' => 3,
            'repeticiones' => '8-12',
            'descanso_segundos' => 90,
            'tempo' => '',
            'intensidad_rpe' => '',
            'metodo' => 'normal',
            'notas' => '',
        ];
        $this->flashToast('success', 'Ejercicio agregado al día.');
    }

    public function saveExerciseField(int $dayIndex, int $exIndex, string $field): void
    {
        $exData = $this->days[$dayIndex]['exercises'][$exIndex] ?? null;
        if (! $exData || ! isset($exData['id'])) {
            return;
        }
        $value = $exData[$field] ?? null;
        $this->updateDayExercise($exData['id'], $field, $value);
    }

    public function updateDayExercise(int $dayExerciseId, string $field, mixed $value): void
    {
        $ex = RoutineTemplateDayExercise::find($dayExerciseId);
        if ($ex && in_array($field, ['series', 'repeticiones', 'descanso_segundos', 'tempo', 'intensidad_rpe', 'metodo', 'notas', 'orden'], true)) {
            $ex->update([$field => $value]);
        }
    }

    public function removeExerciseFromDay(int $dayExerciseId): void
    {
        $ex = RoutineTemplateDayExercise::find($dayExerciseId);
        if ($ex) {
            $ex->delete();
            $this->template->load(['days.exercises']);
            $this->hydrateDays();
            $this->flashToast('success', 'Ejercicio quitado del día.');
        }
    }

    public function moveExerciseUp(int $dayId, int $orden): void
    {
        $this->swapOrder($dayId, $orden, $orden - 1);
    }

    public function moveExerciseDown(int $dayId, int $orden): void
    {
        $this->swapOrder($dayId, $orden, $orden + 1);
    }

    protected function swapOrder(int $dayId, int $currentOrden, int $newOrden): void
    {
        $exercises = RoutineTemplateDayExercise::where('routine_template_day_id', $dayId)->orderBy('orden')->get();
        $byOrden = $exercises->keyBy('orden');
        $current = $byOrden->get($currentOrden);
        $other = $byOrden->get($newOrden);
        if ($current && $other) {
            $current->update(['orden' => $newOrden]);
            $other->update(['orden' => $currentOrden]);
            $this->template->load(['days.exercises']);
            $this->hydrateDays();
        }
    }

    public function render()
    {
        $exercisesForSelect = Exercise::where('estado', 'activo')->orderBy('nombre')->get();
        return view('livewire.routines.templates.builder', ['exercisesForSelect' => $exercisesForSelect]);
    }
}
