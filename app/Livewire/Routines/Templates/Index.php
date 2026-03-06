<?php

namespace App\Livewire\Routines\Templates;

use App\Livewire\Concerns\FlashesToast;
use App\Models\RoutineTemplate;
use App\Services\RoutineTemplateService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast, WithPagination;

    public string $search = '';
    public string $estadoFilter = '';
    public int $perPage = 15;

    public array $modalState = ['create' => false];
    public ?int $templateId = null;
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

    protected $queryString = [
        'search' => ['except' => ''],
        'estadoFilter' => ['except' => ''],
    ];

    protected $paginationTheme = 'tailwind';

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

    public function mount(): void
    {
        $this->authorize('ejercicios-rutinas.view');
        if (request()->has('editar')) {
            $id = (int) request('editar');
            if ($id > 0) {
                $this->openEditModal($id);
            }
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('ejercicios-rutinas.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id): void
    {
        $this->authorize('ejercicios-rutinas.update');
        $template = RoutineTemplate::find($id);
        if (! $template) {
            $this->flashToast('error', 'Rutina no encontrada.');
            return;
        }
        $this->templateId = $template->id;
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
        $this->modalState['create'] = true;
    }

    public function closeModal(): void
    {
        $this->modalState['create'] = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->templateId = null;
        $this->form = [
            'nombre' => '',
            'objetivo' => '',
            'nivel' => '',
            'duracion_semanas' => null,
            'frecuencia_dias_semana' => null,
            'descripcion' => '',
            'tags' => [],
            'estado' => 'borrador',
        ];
        $this->tagsInput = '';
    }

    public function save(): void
    {
        $this->authorize($this->templateId ? 'ejercicios-rutinas.update' : 'ejercicios-rutinas.create');
        $this->validate();
        $parts = array_map('trim', explode(',', $this->tagsInput));
        $this->form['tags'] = array_values(array_filter($parts));
        $data = $this->form;
        $data['created_by'] = auth()->id();

        if ($this->templateId) {
            $template = RoutineTemplate::findOrFail($this->templateId);
            $template->update($data);
            $this->flashToast('success', 'Rutina base actualizada.');
            $this->closeModal();
        } else {
            $template = RoutineTemplate::create($data);
            $this->flashToast('success', 'Rutina base creada. Configura los días en el builder.');
            $this->closeModal();
            $this->redirect(route('rutinas-base.builder', $template), navigate: true);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function cloneTemplate(int $id, RoutineTemplateService $service): void
    {
        $this->authorize('ejercicios-rutinas.create');
        $template = RoutineTemplate::find($id);
        if (! $template) {
            $this->flashToast('error', 'Rutina no encontrada.');
            return;
        }
        $newTemplate = $service->clone($template, auth()->user());
        $this->flashToast('success', 'Rutina clonada. Puedes editarla ahora.');
        $this->redirect(route('rutinas-base.builder', $newTemplate), navigate: true);
    }

    public function render()
    {
        $query = RoutineTemplate::query();
        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('objetivo', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->estadoFilter !== '') {
            $query->where('estado', $this->estadoFilter);
        }
        $templates = $query->orderBy('nombre')->paginate($this->perPage);
        return view('livewire.routines.templates.index', ['templates' => $templates]);
    }
}
