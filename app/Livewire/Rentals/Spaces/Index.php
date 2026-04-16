<?php

namespace App\Livewire\Rentals\Spaces;

use App\Livewire\Concerns\FlashesToast;
use App\Services\RentableSpaceService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast, WithPagination;

    public int $perPage = 15;

    public array $modalState = ['create' => false];

    public ?int $spaceId = null;

    public array $formData = [
        'nombre' => '',
        'descripcion' => '',
        'capacidad' => '',
        'estado' => 'activo',
        'color_calendario' => '#3B82F6',
    ];

    protected $paginationTheme = 'tailwind';

    protected RentableSpaceService $service;

    public function boot(RentableSpaceService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->authorize('alquiler.ver');
    }

    public function openCreateModal(): void
    {
        $this->authorize('alquiler.crear');
        $this->resetForm();
        $this->spaceId = null;
        $this->modalState['create'] = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorize('alquiler.editar');
        $space = $this->service->find($id);
        if (! $space) {
            $this->flashToast('error', 'Espacio no encontrado.');

            return;
        }
        $this->spaceId = $space->id;
        $this->formData = [
            'nombre' => $space->nombre,
            'descripcion' => $space->descripcion ?? '',
            'capacidad' => (string) $space->capacidad,
            'estado' => $space->estado,
            'color_calendario' => $space->color_calendario ?? '#3B82F6',
        ];
        $this->modalState['create'] = true;
    }

    public function save(): void
    {
        $this->authorize($this->spaceId ? 'alquiler.editar' : 'alquiler.crear');
        $this->validate([
            'formData.nombre' => 'required|string|max:120',
            'formData.capacidad' => 'nullable|integer|min:0',
            'formData.estado' => 'required|in:activo,inactivo',
        ]);

        try {
            $data = [
                'nombre' => $this->formData['nombre'],
                'descripcion' => $this->formData['descripcion'] ?: null,
                'capacidad' => $this->formData['capacidad'] !== '' ? (int) $this->formData['capacidad'] : null,
                'estado' => $this->formData['estado'],
                'color_calendario' => $this->formData['color_calendario'] ?: null,
            ];
            if ($this->spaceId) {
                $this->service->update($this->spaceId, $data);
                $this->flashToast('success', 'Espacio actualizado.');
            } else {
                $this->service->create($data);
                $this->flashToast('success', 'Espacio creado.');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeModal(): void
    {
        $this->modalState = ['create' => false];
        $this->spaceId = null;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->formData = [
            'nombre' => '',
            'descripcion' => '',
            'capacidad' => '',
            'estado' => 'activo',
            'color_calendario' => '#3B82F6',
        ];
    }

    public function toggleEstado(int $spaceId): void
    {
        $this->authorize('alquiler.editar');
        $this->service->toggleEstado($spaceId);
        $this->flashToast('success', 'Estado actualizado.');
    }

    public function render()
    {
        $spaces = $this->service->paginate($this->perPage);

        return view('livewire.rentals.spaces.index', ['spaces' => $spaces])
            ->layout('layouts.app', ['title' => 'Espacios para alquiler']);
    }
}
