<?php

namespace App\Livewire\Exercises;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Exercise;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast, WithPagination;

    public string $search = '';
    public string $grupoMuscular = '';
    public string $tipo = '';
    public string $nivel = '';
    public string $equipamiento = '';
    public string $estadoFilter = '';
    public int $perPage = 15;

    public array $modalState = ['create' => false];
    public ?int $exerciseId = null;
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

    protected $queryString = [
        'search' => ['except' => ''],
        'grupoMuscular' => ['except' => ''],
        'tipo' => ['except' => ''],
        'nivel' => ['except' => ''],
        'equipamiento' => ['except' => ''],
        'estadoFilter' => ['except' => ''],
    ];

    protected $paginationTheme = 'tailwind';

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
        $exercise = Exercise::find($id);
        if (! $exercise) {
            $this->flashToast('error', 'Ejercicio no encontrado.');
            return;
        }
        $this->exerciseId = $exercise->id;
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
        $this->modalState['create'] = true;
    }

    public function closeModal(): void
    {
        $this->modalState['create'] = false;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->exerciseId = null;
        $this->form = [
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
        $this->musculosSecundariosInput = '';
    }

    public function updatedMusculosSecundariosInput(): void
    {
        $parts = array_map('trim', explode(',', $this->musculosSecundariosInput));
        $this->form['musculos_secundarios'] = array_values(array_filter($parts));
    }

    public function save(): void
    {
        $this->authorize($this->exerciseId ? 'ejercicios-rutinas.update' : 'ejercicios-rutinas.create');
        $this->validate();
        $parts = array_map('trim', explode(',', $this->musculosSecundariosInput));
        $this->form['musculos_secundarios'] = array_values(array_filter($parts));
        $data = $this->form;

        if ($this->exerciseId) {
            $exercise = Exercise::findOrFail($this->exerciseId);
            $exercise->update($data);
            $this->flashToast('success', 'Ejercicio actualizado correctamente.');
        } else {
            Exercise::create($data);
            $this->flashToast('success', 'Ejercicio creado correctamente.');
        }
        $this->closeModal();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingGrupoMuscular(): void
    {
        $this->resetPage();
    }

    public function updatingTipo(): void
    {
        $this->resetPage();
    }

    public function updatingNivel(): void
    {
        $this->resetPage();
    }

    public function updatingEquipamiento(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Exercise::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                    ->orWhere('grupo_muscular_principal', 'like', '%' . $this->search . '%')
                    ->orWhere('descripcion_tecnica', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->grupoMuscular !== '') {
            $query->where('grupo_muscular_principal', $this->grupoMuscular);
        }
        if ($this->tipo !== '') {
            $query->where('tipo', $this->tipo);
        }
        if ($this->nivel !== '') {
            $query->where('nivel', $this->nivel);
        }
        if ($this->equipamiento !== '') {
            $query->where('equipamiento', $this->equipamiento);
        }
        if ($this->estadoFilter !== '') {
            $query->where('estado', $this->estadoFilter);
        }

        $exercises = $query->orderBy('nombre')->paginate($this->perPage);

        $gruposMusculares = Exercise::query()->whereNotNull('grupo_muscular_principal')->where('grupo_muscular_principal', '!=', '')->distinct()->pluck('grupo_muscular_principal')->sort()->values();
        $niveles = Exercise::query()->whereNotNull('nivel')->where('nivel', '!=', '')->distinct()->pluck('nivel')->sort()->values();
        $equipamientos = Exercise::query()->whereNotNull('equipamiento')->where('equipamiento', '!=', '')->distinct()->pluck('equipamiento')->sort()->values();

        return view('livewire.exercises.index', [
            'exercises' => $exercises,
            'gruposMusculares' => $gruposMusculares,
            'niveles' => $niveles,
            'equipamientos' => $equipamientos,
        ]);
    }
}
