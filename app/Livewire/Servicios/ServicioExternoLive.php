<?php

namespace App\Livewire\Servicios;

use App\Livewire\Concerns\FlashesToast;
use App\Services\ServicioExternoService;
use Livewire\Component;
use Livewire\WithPagination;

class ServicioExternoLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';
    public $categoriaFilter = '';
    public $estadoFilter = '';
    public $perPage = 15;

    public $modalState = ['create' => false, 'delete' => false];
    public $servicioId = null;

    public $formData = [
        'codigo' => '',
        'nombre' => '',
        'descripcion' => '',
        'categoria_id' => null,
        'precio' => '0.00',
        'duracion_minutos' => null,
        'estado' => 'activo',
    ];

    protected $paginationTheme = 'tailwind';
    protected ServicioExternoService $service;

    public function boot(ServicioExternoService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('servicios.view');
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('servicios.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('servicios.update');
        $servicio = $this->service->find($id);
        if (!$servicio) {
            $this->flashToast('error', 'Servicio no encontrado');
            return;
        }

        $this->servicioId = $servicio->id;
        $this->formData = [
            'codigo' => $servicio->codigo,
            'nombre' => $servicio->nombre,
            'descripcion' => $servicio->descripcion ?? '',
            'categoria_id' => $servicio->categoria_id,
            'precio' => $servicio->precio,
            'duracion_minutos' => $servicio->duracion_minutos,
            'estado' => $servicio->estado,
        ];
        $this->modalState['create'] = true;
    }

    public function save()
    {
        $this->authorize($this->servicioId ? 'servicios.update' : 'servicios.create');
        try {
            if ($this->servicioId) {
                $this->service->update($this->servicioId, $this->formData);
                $this->flashToast('success', 'Servicio actualizado exitosamente.');
            } else {
                $this->service->create($this->formData);
                $this->flashToast('success', 'Servicio creado exitosamente.');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('servicios.delete');
        try {
            $this->service->delete($this->servicioId);
            $this->flashToast('success', 'Servicio eliminado exitosamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->modalState = ['create' => false, 'delete' => false];
        $this->servicioId = null;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->formData = [
            'codigo' => '',
            'nombre' => '',
            'descripcion' => '',
            'categoria_id' => null,
            'precio' => '0.00',
            'duracion_minutos' => null,
            'estado' => 'activo',
        ];
    }

    public function render()
    {
        $filtros = [];
        if ($this->search) {
            $filtros['busqueda'] = $this->search;
        }
        if ($this->categoriaFilter) {
            $filtros['categoria_id'] = $this->categoriaFilter;
        }
        if ($this->estadoFilter) {
            $filtros['estado'] = $this->estadoFilter;
        }

        $servicios = $this->service->obtenerServicios($this->perPage, $filtros);
        $categorias = \App\Models\Core\CategoriaServicio::where('estado', 'activa')->orderBy('nombre')->get();

        return view('livewire.servicios.servicio-externo-live', [
            'servicios' => $servicios,
            'categorias' => $categorias,
        ]);
    }
}
