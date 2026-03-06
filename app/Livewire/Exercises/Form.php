<?php

namespace App\Livewire\Exercises;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Exercise;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?int $exerciseId = null;
    public bool $isCreate = true;

    public array $form = [
        'nombre' => '',
        'grupo_muscular_principal' => '',
        'musculos_secundarios' => [],
        'tipo' => 'fuerza',
        'nivel' => '',
        'equipamiento' => '',
        'descripcion_tecnica' => '',
        'errores_comunes' => '',
        'consejos_seguridad' => '',
        'video_url' => '',
        'estado' => 'activo',
    ];

    public string $musculosSecundariosInput = '';

    protected function rules(): array
    {
        return [
            'form.nombre' => ['required', 'string', 'max:255'],
            'form.grupo_muscular_principal' => ['nullable', 'string', 'max:255'],
            'form.tipo' => ['required', 'in:fuerza,hipertrofia,cardio,movilidad,estiramiento'],
            'form.nivel' => ['nullable', 'string', 'max:100'],
            'form.equipamiento' => ['nullable', 'string', 'max:255'],
            'form.descripcion_tecnica' => ['nullable', 'string'],
            'form.errores_comunes' => ['nullable', 'string'],
            'form.consejos_seguridad' => ['nullable', 'string'],
            'form.video_url' => ['nullable', 'url', 'max:500'],
            'form.estado' => ['required', 'in:activo,inactivo'],
        ];
    }

    public function mount(?Exercise $exercise = null): void
    {
        $this->authorize($exercise ? 'ejercicios-rutinas.update' : 'ejercicios-rutinas.create');
        if ($exercise && $exercise->exists) {
            $this->exerciseId = $exercise->id;
            $this->isCreate = false;
            $this->form = [
                'nombre' => $exercise->nombre,
                'grupo_muscular_principal' => $exercise->grupo_muscular_principal ?? '',
                'musculos_secundarios' => $exercise->musculos_secundarios ?? [],
                'tipo' => $exercise->tipo,
                'nivel' => $exercise->nivel ?? '',
                'equipamiento' => $exercise->equipamiento ?? '',
                'descripcion_tecnica' => $exercise->descripcion_tecnica ?? '',
                'errores_comunes' => $exercise->errores_comunes ?? '',
                'consejos_seguridad' => $exercise->consejos_seguridad ?? '',
                'video_url' => $exercise->video_url ?? '',
                'estado' => $exercise->estado,
            ];
            $this->musculosSecundariosInput = is_array($exercise->musculos_secundarios)
                ? implode(', ', $exercise->musculos_secundarios)
                : '';
        } else {
            $this->musculosSecundariosInput = '';
        }
    }

    public function updatedMusculosSecundariosInput(): void
    {
        $parts = array_map('trim', explode(',', $this->musculosSecundariosInput));
        $this->form['musculos_secundarios'] = array_values(array_filter($parts));
    }

    public function save(): void
    {
        $this->authorize($this->isCreate ? 'ejercicios-rutinas.create' : 'ejercicios-rutinas.update');
        $this->validate();
        $parts = array_map('trim', explode(',', $this->musculosSecundariosInput));
        $this->form['musculos_secundarios'] = array_values(array_filter($parts));

        $data = $this->form;
        if ($this->isCreate) {
            Exercise::create($data);
            $this->flashToast('success', 'Ejercicio creado correctamente.');
            $this->redirect(route('ejercicios.index'), navigate: true);
        } else {
            $exercise = Exercise::findOrFail($this->exerciseId);
            $exercise->update($data);
            $this->flashToast('success', 'Ejercicio actualizado correctamente.');
            $this->redirect(route('ejercicios.show', $exercise), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.exercises.form');
    }
}
