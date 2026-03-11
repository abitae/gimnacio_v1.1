<?php

namespace App\Livewire\Settings\PaymentMethods;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\PaymentMethod;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast, WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public int $perPage = 15;

    public array $modalState = ['create' => false, 'delete' => false];

    public ?int $paymentMethodId = null;

    public array $formData = [
        'nombre' => '',
        'descripcion' => '',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
        'estado' => 'activo',
    ];

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('payment-methods.view');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('payment-methods.create');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorize('payment-methods.update');
        $method = PaymentMethod::find($id);
        if (! $method) {
            $this->flashToast('error', 'Método de pago no encontrado.');
            return;
        }

        $this->paymentMethodId = $method->id;
        $this->formData = [
            'nombre' => $method->nombre,
            'descripcion' => $method->descripcion ?? '',
            'requiere_numero_operacion' => $method->requiere_numero_operacion,
            'requiere_entidad' => $method->requiere_entidad,
            'estado' => $method->estado,
        ];
        $this->modalState['create'] = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->authorize('payment-methods.delete');
        $this->paymentMethodId = $id;
        $this->modalState['delete'] = true;
    }

    public function save(): void
    {
        $this->authorize($this->paymentMethodId ? 'payment-methods.update' : 'payment-methods.create');
        $rules = [
            'formData.nombre' => 'required|string|max:80',
            'formData.descripcion' => 'nullable|string',
            'formData.requiere_numero_operacion' => 'boolean',
            'formData.requiere_entidad' => 'boolean',
            'formData.estado' => 'required|in:activo,inactivo',
        ];
        if ($this->paymentMethodId) {
            $rules['formData.nombre'] = 'required|string|max:80|unique:payment_methods,nombre,' . $this->paymentMethodId;
        } else {
            $rules['formData.nombre'] = 'required|string|max:80|unique:payment_methods,nombre';
        }
        $this->validate($rules);

        try {
            if ($this->paymentMethodId) {
                $method = PaymentMethod::findOrFail($this->paymentMethodId);
                $method->update($this->formData);
                $this->flashToast('success', 'Método de pago actualizado correctamente.');
            } else {
                PaymentMethod::create($this->formData);
                $this->flashToast('success', 'Método de pago creado correctamente.');
            }
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete(): void
    {
        $this->authorize('payment-methods.delete');
        try {
            $method = PaymentMethod::findOrFail($this->paymentMethodId);
            $method->delete();
            $this->flashToast('success', 'Método de pago eliminado correctamente.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function toggleEstado(int $id): void
    {
        $this->authorize('payment-methods.update');
        $method = PaymentMethod::find($id);
        if (! $method) {
            $this->flashToast('error', 'Método de pago no encontrado.');
            return;
        }
        $method->estado = $method->estado === 'activo' ? 'inactivo' : 'activo';
        $method->save();
        $this->flashToast('success', 'Estado actualizado.');
    }

    public function closeModal(): void
    {
        $this->modalState = ['create' => false, 'delete' => false];
        $this->paymentMethodId = null;
        $this->resetForm();
    }

    protected function resetForm(): void
    {
        $this->formData = [
            'nombre' => '',
            'descripcion' => '',
            'requiere_numero_operacion' => false,
            'requiere_entidad' => false,
            'estado' => 'activo',
        ];
    }

    public function render()
    {
        $query = PaymentMethod::query()
            ->when($this->search, fn ($q) => $q->where('nombre', 'like', '%' . $this->search . '%')
                ->orWhere('descripcion', 'like', '%' . $this->search . '%'))
            ->when($this->estadoFilter, fn ($q) => $q->where('estado', $this->estadoFilter))
            ->orderBy('nombre');

        $paymentMethods = $query->paginate($this->perPage);

        return view('livewire.settings.payment-methods.index', [
            'paymentMethods' => $paymentMethods,
        ])->layout('layouts.app', ['title' => 'Métodos de pago']);
    }
}
