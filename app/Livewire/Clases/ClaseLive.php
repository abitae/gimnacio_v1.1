<?php

namespace App\Livewire\Clases;

use App\Livewire\Concerns\FlashesToast;
use App\Services\ClaseService;
use Livewire\Component;
use Livewire\WithPagination;

class ClaseLive extends Component
{
    use FlashesToast, WithPagination;

    // Filters and pagination
    public $search = '';
    public $tipoFilter = '';
    public $instructorFilter = '';
    public $estadoFilter = '';
    public $perPage = 15;

    // Modal state
    public $modalState = [
        'create' => false,
        'delete' => false,
    ];

    // Selected items
    public $claseId = null;
    public $selectedClaseId = null;
    public $selectedClase = null; // Cached selected clase

    public $formData = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
        'tipo' => 'sesion',
        'precio_sesion' => '0.00',
        'precio_paquete' => '0.00',
        'sesiones_paquete' => null,
        'instructor_id' => null,
        'estado' => 'activo',
    ];

    protected $paginationTheme = 'tailwind';
    protected ClaseService $service;

    public function boot(ClaseService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('clases.view');
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedClaseId = null;
        $this->selectedClase = null;
    }

    public function updatingTipoFilter()
    {
        $this->resetPage();
        $this->selectedClaseId = null;
        $this->selectedClase = null;
    }

    public function updatingInstructorFilter()
    {
        $this->resetPage();
        $this->selectedClaseId = null;
        $this->selectedClase = null;
    }

    public function updatingEstadoFilter()
    {
        $this->resetPage();
        $this->selectedClaseId = null;
        $this->selectedClase = null;
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('clases.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('clases.update');
        $clase = $this->service->find($id);
        if (!$clase) {
            $this->flashToast('error', 'Clase no encontrada');
            return;
        }

        $this->claseId = $clase->id;
        $this->mapClaseToForm($clase);
        $this->modalState['create'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('clases.delete');
        $this->claseId = $id;
        $this->modalState['delete'] = true;
    }

    public function save()
    {
        $this->authorize($this->claseId ? 'clases.update' : 'clases.create');
        try {
            $data = $this->mapFormToData();

            if ($this->claseId) {
                $this->service->update($this->claseId, $data);
                $this->flashToast('success', 'Clase actualizada correctamente');
            } else {
                $this->service->create($data);
                $this->flashToast('success', 'Clase creada correctamente');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function mapFormToData(): array
    {
        return [
            'codigo' => $this->formData['codigo'],
            'nombre' => $this->formData['nombre'],
            'descripcion' => $this->formData['descripcion'] ?: null,
            'tipo' => $this->formData['tipo'],
            'precio_sesion' => $this->formData['precio_sesion'] ?: null,
            'precio_paquete' => $this->formData['precio_paquete'] ?: null,
            'sesiones_paquete' => $this->formData['sesiones_paquete'] ?: null,
            'instructor_id' => $this->formData['instructor_id'] ?: null,
            'estado' => $this->formData['estado'],
        ];
    }

    public function delete()
    {
        $this->authorize('clases.delete');
        try {
            $this->service->delete($this->claseId);
            $this->flashToast('success', 'Clase eliminada exitosamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->modalState = [
            'create' => false,
            'delete' => false,
        ];
        $this->claseId = null;
        $this->resetForm();
    }

    protected function handleValidationErrors(\Illuminate\Validation\ValidationException $e): void
    {
        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->flashToast('error', $message);
            }
        }
    }

    public function selectClase($id)
    {
        $this->selectedClaseId = $id;
        $this->selectedClase = $this->service->find($id);
    }

    protected function mapClaseToForm(\App\Models\Core\Clase $clase): void
    {
        $this->formData = [
            'codigo' => $clase->codigo,
            'nombre' => $clase->nombre,
            'descripcion' => $clase->descripcion ?? '',
            'tipo' => $clase->tipo,
            'precio_sesion' => $clase->precio_sesion ?? '0.00',
            'precio_paquete' => $clase->precio_paquete ?? '0.00',
            'sesiones_paquete' => $clase->sesiones_paquete,
            'instructor_id' => $clase->instructor_id,
            'estado' => $clase->estado,
        ];
    }

    protected function resetForm(): void
    {
        $this->claseId = null;
        $this->formData = [
            'codigo' => '',
            'nombre' => '',
            'descripcion' => '',
            'tipo' => 'sesion',
            'precio_sesion' => '0.00',
            'precio_paquete' => '0.00',
            'sesiones_paquete' => null,
            'instructor_id' => null,
            'estado' => 'activo',
        ];
    }

    public function render()
    {
        $filtros = [];
        if ($this->search) {
            $filtros['busqueda'] = $this->search;
        }
        if ($this->tipoFilter) {
            $filtros['tipo'] = $this->tipoFilter;
        }
        if ($this->instructorFilter) {
            $filtros['instructor_id'] = $this->instructorFilter;
        }
        if ($this->estadoFilter) {
            $filtros['estado'] = $this->estadoFilter;
        }

        $clases = $this->service->obtenerClases($this->perPage, $filtros);
        $instructores = \App\Models\User::orderBy('name')->get();

        return view('livewire.clases.clase-live', [
            'clases' => $clases,
            'instructores' => $instructores,
            'selectedClase' => $this->selectedClase,
        ]);
    }
}
