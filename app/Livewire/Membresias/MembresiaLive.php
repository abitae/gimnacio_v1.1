<?php

namespace App\Livewire\Membresias;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Membresia;
use App\Services\MembresiaService;
use Livewire\Component;
use Livewire\WithPagination;

class MembresiaLive extends Component
{
    use FlashesToast, WithPagination;

    // Filters and pagination
    public $search = '';
    public $estadoFilter = '';
    public $perPage = 15;

    // Modal state
    public $modalState = [
        'create' => false,
        'delete' => false,
    ];

    // Selected items
    public $membresiaId = null;
    public $selectedMembresiaId = null;
    public $selectedMembresia = null; // Cached selected membresia

    // Form data
    public $formData = [
        'nombre' => '',
        'descripcion' => '',
        'duracion_dias' => 30,
        'precio_base' => 0.00,
        'permite_cuotas' => false,
        'numero_cuotas_default' => null,
        'frecuencia_cuotas_default' => 'mensual',
        'cuota_inicial_monto' => null,
        'cuota_inicial_porcentaje' => null,
        'tipo_acceso' => 'ilimitado',
        'max_visitas_dia' => null,
        'permite_congelacion' => false,
        'max_dias_congelacion' => null,
        'estado' => 'activa',
    ];

    protected $paginationTheme = 'tailwind';

    protected MembresiaService $service;

    public function boot(MembresiaService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('membresias.view');
        $this->resetPage();
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->selectedMembresiaId = null;
        $this->selectedMembresia = null;
    }

    public function updatingEstadoFilter()
    {
        $this->resetPage();
        $this->selectedMembresiaId = null;
        $this->selectedMembresia = null;
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function openCreateModal()
    {
        $this->authorize('membresias.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('membresias.update');
        $membresia = $this->service->find($id);

        if (!$membresia) {
            $this->flashToast('error', 'Membresía no encontrada');
            return;
        }

        $this->membresiaId = $membresia->id;
        $this->mapMembresiaToForm($membresia);
        $this->modalState['create'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('membresias.delete');
        $this->membresiaId = $id;
        $this->modalState['delete'] = true;
    }

    public function closeModal()
    {
        $this->modalState = [
            'create' => false,
            'delete' => false,
        ];
        $this->resetForm();
    }

    public function save()
    {
        $this->authorize($this->membresiaId ? 'membresias.update' : 'membresias.create');
        try {
            $data = $this->mapFormToData();

            if ($this->membresiaId) {
                $this->service->update($this->membresiaId, $data);
                $this->flashToast('success', 'Membresía actualizada correctamente');
            } else {
                $this->service->create($data);
                $this->flashToast('success', 'Membresía creada correctamente');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function updatedFormDataPermiteCuotas($value): void
    {
        if (! $value) {
            $this->formData['numero_cuotas_default'] = null;
            $this->formData['frecuencia_cuotas_default'] = 'mensual';
            $this->formData['cuota_inicial_monto'] = null;
            $this->formData['cuota_inicial_porcentaje'] = null;
        }
    }

    public function delete()
    {
        $this->authorize('membresias.delete');
        try {
            $this->service->delete($this->membresiaId);
            $this->flashToast('success', 'Membresía eliminada correctamente');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function mapMembresiaToForm(Membresia $membresia): void
    {
        $this->formData = [
            'nombre' => $membresia->nombre,
            'descripcion' => $membresia->descripcion ?? '',
            'duracion_dias' => $membresia->duracion_dias,
            'precio_base' => $membresia->precio_base,
            'permite_cuotas' => $membresia->permite_cuotas ?? false,
            'numero_cuotas_default' => $membresia->numero_cuotas_default,
            'frecuencia_cuotas_default' => $membresia->frecuencia_cuotas_default ?? 'mensual',
            'cuota_inicial_monto' => $membresia->cuota_inicial_monto,
            'cuota_inicial_porcentaje' => $membresia->cuota_inicial_porcentaje,
            'tipo_acceso' => $membresia->tipo_acceso ?? 'ilimitado',
            'max_visitas_dia' => $membresia->max_visitas_dia,
            'permite_congelacion' => $membresia->permite_congelacion ?? false,
            'max_dias_congelacion' => $membresia->max_dias_congelacion,
            'estado' => $membresia->estado,
        ];
    }

    protected function mapFormToData(): array
    {
        return [
            'nombre' => $this->formData['nombre'],
            'descripcion' => $this->formData['descripcion'] ?: null,
            'duracion_dias' => $this->formData['duracion_dias'],
            'precio_base' => $this->formData['precio_base'],
            'permite_cuotas' => $this->formData['permite_cuotas'] ?? false,
            'numero_cuotas_default' => $this->formData['numero_cuotas_default'] ?: null,
            'frecuencia_cuotas_default' => ($this->formData['permite_cuotas'] ?? false) ? ($this->formData['frecuencia_cuotas_default'] ?: null) : null,
            'cuota_inicial_monto' => $this->formData['cuota_inicial_monto'] !== '' ? $this->formData['cuota_inicial_monto'] : null,
            'cuota_inicial_porcentaje' => $this->formData['cuota_inicial_porcentaje'] !== '' ? $this->formData['cuota_inicial_porcentaje'] : null,
            'tipo_acceso' => $this->formData['tipo_acceso'] ?: null,
            'max_visitas_dia' => $this->formData['max_visitas_dia'] ?: null,
            'permite_congelacion' => $this->formData['permite_congelacion'] ?? false,
            'max_dias_congelacion' => $this->formData['max_dias_congelacion'] ?: null,
            'estado' => $this->formData['estado'],
        ];
    }

    protected function resetForm(): void
    {
        $this->membresiaId = null;
        $this->formData = [
            'nombre' => '',
            'descripcion' => '',
            'duracion_dias' => 30,
            'precio_base' => 0.00,
            'permite_cuotas' => false,
            'numero_cuotas_default' => null,
            'frecuencia_cuotas_default' => 'mensual',
            'cuota_inicial_monto' => null,
            'cuota_inicial_porcentaje' => null,
            'tipo_acceso' => 'ilimitado',
            'max_visitas_dia' => null,
            'permite_congelacion' => false,
            'max_dias_congelacion' => null,
            'estado' => 'activa',
        ];
    }

    protected function handleValidationErrors(\Illuminate\Validation\ValidationException $e): void
    {
        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->flashToast('error', $message);
            }
        }
    }

    public function selectMembresia($id)
    {
        $this->selectedMembresiaId = $id;
        $this->selectedMembresia = $this->service->find($id);
    }

    public function render()
    {
        if ($this->search || $this->estadoFilter) {
            $membresias = $this->service->search($this->search, $this->estadoFilter, $this->perPage);
        } else {
            $membresias = $this->service->paginate($this->perPage);
        }

        return view('livewire.membresias.membresia-live', [
            'membresias' => $membresias,
            'selectedMembresia' => $this->selectedMembresia,
        ]);
    }
}
