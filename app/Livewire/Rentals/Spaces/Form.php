<?php

namespace App\Livewire\Rentals\Spaces;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\RentableSpace;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?RentableSpace $space = null;

    public array $form = [
        'nombre' => '',
        'descripcion' => '',
        'capacidad' => '',
        'estado' => 'activo',
        'color_calendario' => '#3B82F6',
    ];

    public function mount(?RentableSpace $space = null): void
    {
        $this->authorize($space ? 'rentals.update' : 'rentals.create');
        $this->space = $space;
        if ($space) {
            $this->form = [
                'nombre' => $space->nombre,
                'descripcion' => $space->descripcion ?? '',
                'capacidad' => (string) $space->capacidad,
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
            'form.estado' => 'required|in:activo,inactivo',
        ]);

        try {
            $data = [
                'nombre' => $this->form['nombre'],
                'descripcion' => $this->form['descripcion'] ?: null,
                'capacidad' => $this->form['capacidad'] !== '' ? (int) $this->form['capacidad'] : null,
                'estado' => $this->form['estado'],
                'color_calendario' => $this->form['color_calendario'] ?: null,
            ];
            if ($this->space) {
                $this->space->update($data);
                $this->flashToast('success', 'Espacio actualizado.');
            } else {
                RentableSpace::create($data);
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
