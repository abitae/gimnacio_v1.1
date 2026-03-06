<?php

namespace App\Livewire\Routines\Templates;

use App\Livewire\Concerns\FlashesToast;
use App\Models\RoutineTemplate;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?int $templateId = null;
    public bool $isCreate = true;

    public array $form = [
        'nombre' => '',
        'objetivo' => '',
        'nivel' => '',
        'duracion_semanas' => null,
        'frecuencia_dias_semana' => null,
        'descripcion' => '',
        'tags' => [],
        'estado' => 'borrador',
    ];

    public string $tagsInput = '';

    protected function rules(): array
    {
        return [
            'form.nombre' => ['required', 'string', 'max:255'],
            'form.objetivo' => ['nullable', 'string', 'max:255'],
            'form.nivel' => ['nullable', 'string', 'max:100'],
            'form.duracion_semanas' => ['nullable', 'integer', 'min:1', 'max:52'],
            'form.frecuencia_dias_semana' => ['nullable', 'integer', 'min:1', 'max:7'],
            'form.descripcion' => ['nullable', 'string'],
            'form.estado' => ['required', 'in:borrador,activa'],
        ];
    }

    public function mount(?RoutineTemplate $template = null): void
    {
        $this->authorize($template && $template->exists ? 'ejercicios-rutinas.update' : 'ejercicios-rutinas.create');
        if ($template && $template->exists) {
            $this->templateId = $template->id;
            $this->isCreate = false;
            $this->form = [
                'nombre' => $template->nombre,
                'objetivo' => $template->objetivo ?? '',
                'nivel' => $template->nivel ?? '',
                'duracion_semanas' => $template->duracion_semanas,
                'frecuencia_dias_semana' => $template->frecuencia_dias_semana,
                'descripcion' => $template->descripcion ?? '',
                'tags' => $template->tags ?? [],
                'estado' => $template->estado,
            ];
            $this->tagsInput = is_array($template->tags) ? implode(', ', $template->tags) : '';
        }
    }

    public function save(): void
    {
        $this->authorize($this->isCreate ? 'ejercicios-rutinas.create' : 'ejercicios-rutinas.update');
        $this->validate();
        $parts = array_map('trim', explode(',', $this->tagsInput));
        $this->form['tags'] = array_values(array_filter($parts));

        $data = $this->form;
        $data['created_by'] = auth()->id();
        if ($this->isCreate) {
            $template = RoutineTemplate::create($data);
            $this->flashToast('success', 'Rutina base creada. Configura los días en el builder.');
            $this->redirect(route('rutinas-base.builder', $template), navigate: true);
        } else {
            $template = RoutineTemplate::findOrFail($this->templateId);
            $template->update($data);
            $this->flashToast('success', 'Rutina base actualizada.');
            $this->redirect(route('rutinas-base.show', $template), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.routines.templates.form');
    }
}
