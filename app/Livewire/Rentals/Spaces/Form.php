<?php

namespace App\Livewire\Rentals\Spaces;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\RentableSpace;
use App\Services\RentableSpaceService;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?RentableSpace $space = null;

    public array $form = [
        'nombre' => '',
        'descripcion' => '',
        'capacidad' => '',
        'precio' => '',
        'estado' => 'activo',
        'color_calendario' => '#3B82F6',
    ];

    protected RentableSpaceService $service;

    public function boot(RentableSpaceService $service): void
    {
        $this->service = $service;
    }

    public function mount(?RentableSpace $space = null): void
    {
        $this->authorize($space ? 'alquiler.editar' : 'alquiler.crear');
        $this->space = $space;
        if ($space) {
            $this->form = [
                'nombre' => $space->nombre,
                'descripcion' => $space->descripcion ?? '',
                'capacidad' => (string) $space->capacidad,
                'precio' => $space->precio !== null ? (string) $space->precio : '',
                'estado' => $space->estado,
                'color_calendario' => $space->color_calendario ?? '#3B82F6',
            ];
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.nombre' => 'required|string|max:120',
            'form.capacidad' => 'nullable|integer|min:0',
            'form.precio' => 'nullable|numeric|min:0',
            'form.estado' => 'required|in:activo,inactivo',
        ]);

        try {
            $data = [
                'nombre' => $this->form['nombre'],
                'descripcion' => $this->form['descripcion'] ?: null,
                'capacidad' => $this->form['capacidad'] !== '' ? (int) $this->form['capacidad'] : null,
                'precio' => $this->form['precio'] !== '' ? (float) $this->form['precio'] : null,
                'estado' => $this->form['estado'],
                'color_calendario' => $this->form['color_calendario'] ?: null,
            ];
            if ($this->space) {
                $this->space = $this->service->update($this->space->id, $data);
                $this->flashToast('success', 'Espacio actualizado.');
            } else {
                $this->space = $this->service->create($data);
                $this->flashToast('success', 'Espacio creado.');
            }
            $this->redirectRoute('rentals.spaces.index', navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.rentals.spaces.form')
            ->layout('layouts.app', ['title' => $this->space ? 'Editar espacio' : 'Nuevo espacio']);
    }
}
