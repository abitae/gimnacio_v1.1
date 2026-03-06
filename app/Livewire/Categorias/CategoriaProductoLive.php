<?php

namespace App\Livewire\Categorias;

use App\Livewire\Concerns\FlashesToast;
use App\Services\CategoriaProductoService;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriaProductoLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';
    public $estadoFilter = '';
    public $perPage = 15;

    public $modalState = ['create' => false, 'delete' => false];
    public $categoriaId = null;

    public $formData = [
        'nombre' => '',
        'descripcion' => '',
        'estado' => 'activa',
    ];

    protected $paginationTheme = 'tailwind';
    protected CategoriaProductoService $service;

    public function boot(CategoriaProductoService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('categorias-productos.view');
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('categorias-productos.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('categorias-productos.update');
        $categoria = $this->service->find($id);
        if (!$categoria) {
            $this->flashToast('error', 'Categoría no encontrada');
            return;
        }

        $this->categoriaId = $categoria->id;
        $this->formData = [
            'nombre' => $categoria->nombre,
            'descripcion' => $categoria->descripcion ?? '',
            'estado' => $categoria->estado,
        ];
        $this->modalState['create'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('categorias-productos.delete');
        $this->categoriaId = $id;
        $this->modalState['delete'] = true;
    }

    public function save()
    {
        $this->authorize($this->categoriaId ? 'categorias-productos.update' : 'categorias-productos.create');
        try {
            if ($this->categoriaId) {
                $this->service->update($this->categoriaId, $this->formData);
                $this->flashToast('success', 'Categoría actualizada exitosamente.');
            } else {
                $this->service->create($this->formData);
                $this->flashToast('success', 'Categoría creada exitosamente.');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('categorias-productos.delete');
        try {
            $this->service->delete($this->categoriaId);
            $this->flashToast('success', 'Categoría eliminada exitosamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal()
    {
        $this->modalState = ['create' => false, 'delete' => false];
        $this->categoriaId = null;
        $this->resetForm();
    }

    protected function resetForm()
    {
        $this->formData = [
            'nombre' => '',
            'descripcion' => '',
            'estado' => 'activa',
        ];
    }

    public function render()
    {
        $filtros = [];
        if ($this->search) {
            $filtros['busqueda'] = $this->search;
        }
        if ($this->estadoFilter) {
            $filtros['estado'] = $this->estadoFilter;
        }

        $categorias = $this->service->obtenerCategorias($this->perPage, $filtros);

        return view('livewire.categorias.categoria-producto-live', [
            'categorias' => $categorias,
        ]);
    }
}
