<?php

namespace App\Livewire\Exercises;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Show extends Component
{
    use FlashesToast;

    public Exercise $exercise;
    public string $activeTab = 'detalle';

    public ?int $relatedExerciseId = null;
    public string $relationType = 'variation';

    public function mount(Exercise $exercise): void
    {
        $this->authorize('ejercicios-rutinas.view');
        $this->exercise = $exercise->load(['variations', 'substitutions']);
    }

    public function addRelation(string $type): void
    {
        $this->authorize('ejercicios-rutinas.update');
        if (! in_array($type, ['variation', 'substitution'], true)) {
            return;
        }
        $this->validate([
            'relatedExerciseId' => ['required', 'exists:exercises,id'],
        ], [
            'relatedExerciseId.required' => 'Selecciona un ejercicio.',
            'relatedExerciseId.exists' => 'El ejercicio seleccionado no es válido.',
        ]);
        if ($this->relatedExerciseId == $this->exercise->id) {
            $this->flashToast('error', 'No puedes relacionar un ejercicio consigo mismo.');
            return;
        }
        $exists = DB::table('exercise_relations')
            ->where('exercise_id', $this->exercise->id)
            ->where('related_exercise_id', $this->relatedExerciseId)
            ->where('relation_type', $type)
            ->exists();
        if ($exists) {
            $this->flashToast('error', 'Esa relación ya existe.');
            return;
        }
        DB::table('exercise_relations')->insert([
            'exercise_id' => $this->exercise->id,
            'related_exercise_id' => $this->relatedExerciseId,
            'relation_type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->exercise->load(['variations', 'substitutions']);
        $this->relatedExerciseId = null;
        $this->flashToast('success', 'Relación agregada.');
    }

    public function removeRelation(int $relatedId, string $type): void
    {
        $this->authorize('ejercicios-rutinas.update');
        DB::table('exercise_relations')
            ->where('exercise_id', $this->exercise->id)
            ->where('related_exercise_id', $relatedId)
            ->where('relation_type', $type)
            ->delete();
        $this->exercise->load(['variations', 'substitutions']);
        $this->flashToast('success', 'Relación eliminada.');
    }

    public function render()
    {
        return view('livewire.exercises.show');
    }
}
