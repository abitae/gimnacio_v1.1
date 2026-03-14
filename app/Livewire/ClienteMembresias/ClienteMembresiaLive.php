<?php

namespace App\Livewire\ClienteMembresias;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Services\ClienteMembresiaService;
use App\Services\ClienteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteMembresiaLive extends Component
{
    use FlashesToast, WithPagination;

    // Cliente search
    public $clienteSearch = '';
    public $clientes;
    public $selectedClienteId = null;
    public $selectedCliente = null;

    // Membresías filters
    public $estadoFilter = '';
    public $perPage = 15;

    // Modal state
    public $modalState = [
        'create' => false,
        'delete' => false,
    ];

    // Selected items
    public $clienteMembresiaId = null;

    // Form data
    public $formData = [
        'membresia_id' => '',
        'fecha_matricula' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
        'estado' => 'activa',
        'precio_lista' => 0.00,
        'descuento_monto' => 0.00,
        'precio_final' => 0.00,
        'asesor_id' => null,
        'canal_venta' => 'presencial',
        'fechas_congelacion' => [],
        'motivo_cancelacion' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected ClienteMembresiaService $service;
    protected ClienteService $clienteService;

    public function boot(ClienteMembresiaService $service, ClienteService $clienteService)
    {
        $this->service = $service;
        $this->clienteService = $clienteService;
    }

    public function mount()
    {
        $this->formData['asesor_id'] = auth()->id();
        $this->formData['fecha_matricula'] = now()->format('Y-m-d');
        $this->clientes = collect([]);
    }

    public $isSearching = false;

    public function updatingClienteSearch()
    {
        $this->isSearching = true;
        $this->searchClientes();
    }

    public function updatingEstadoFilter()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function searchClientes()
    {
        $searchTerm = trim($this->clienteSearch);
        
        if (strlen($searchTerm) >= 2) {
            $this->clientes = $this->clienteService->quickSearch($searchTerm, 10);
        } else {
            $this->clientes = collect([]);
        }
        
        $this->isSearching = false;
    }

    public function selectCliente($clienteId)
    {
        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);
        $this->clienteSearch = $this->selectedCliente->nombres . ' ' . $this->selectedCliente->apellidos;
        $this->clientes = collect([]);
        $this->resetPage();
    }

    public function clearClienteSelection()
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->resetPage();
    }

    public function openCreateModal()
    {
        if (!$this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero');
            return;
        }

        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $clienteMembresia = $this->service->find($id);

        if (!$clienteMembresia) {
            $this->flashToast('error', 'Membresía no encontrada');
            return;
        }

        $this->clienteMembresiaId = $clienteMembresia->id;
        $this->mapClienteMembresiaToForm($clienteMembresia);
        $this->modalState['create'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->clienteMembresiaId = $id;
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

    public function updatedFormDataMembresiaId()
    {
        if ($this->formData['membresia_id']) {
            $membresia = \App\Models\Core\Membresia::find($this->formData['membresia_id']);
            if ($membresia) {
                $this->formData['precio_lista'] = $membresia->precio_base;
                $this->calculatePrecioFinal();
            }
        }
    }

    public function updatedFormDataPrecioLista()
    {
        $this->calculatePrecioFinal();
    }

    public function updatedFormDataDescuentoMonto()
    {
        $this->calculatePrecioFinal();
    }

    protected function calculatePrecioFinal()
    {
        $precioLista = (float) ($this->formData['precio_lista'] ?? 0);
        $descuento = (float) ($this->formData['descuento_monto'] ?? 0);
        $this->formData['precio_final'] = max(0, $precioLista - $descuento);
    }

    public function updatedFormDataFechaInicio()
    {
        if ($this->formData['fecha_inicio'] && $this->formData['membresia_id']) {
            $membresia = \App\Models\Core\Membresia::find($this->formData['membresia_id']);
            if ($membresia) {
                $fechaInicio = \Carbon\Carbon::parse($this->formData['fecha_inicio']);
                $this->formData['fecha_fin'] = $fechaInicio->copy()->addDays($membresia->duracion_dias)->format('Y-m-d');
            }
        }
    }

    public function save()
    {
        try {
            if (!$this->selectedClienteId) {
                $this->flashToast('error', 'Debes seleccionar un cliente primero');
                return;
            }

            $data = $this->mapFormToData();
            $data['cliente_id'] = $this->selectedClienteId;

            if ($this->clienteMembresiaId) {
                $this->service->update($this->clienteMembresiaId, $data);
                $this->flashToast('success', 'Membresía actualizada correctamente');
            } else {
                $this->service->create($data);
                $this->flashToast('success', 'Membresía creada correctamente');
            }

            \App\Models\Core\Cliente::where('id', $this->selectedClienteId)->update(['estado_cliente' => 'activo']);

            $this->closeModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        try {
            $this->service->delete($this->clienteMembresiaId);
            $this->flashToast('success', 'Membresía eliminada correctamente');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function mapClienteMembresiaToForm(ClienteMembresia $clienteMembresia): void
    {
        $this->formData = [
            'membresia_id' => $clienteMembresia->membresia_id,
            'fecha_matricula' => $clienteMembresia->fecha_matricula?->format('Y-m-d') ?? '',
            'fecha_inicio' => $clienteMembresia->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $clienteMembresia->fecha_fin->format('Y-m-d'),
            'estado' => $clienteMembresia->estado,
            'precio_lista' => $clienteMembresia->precio_lista,
            'descuento_monto' => $clienteMembresia->descuento_monto,
            'precio_final' => $clienteMembresia->precio_final,
            'asesor_id' => $clienteMembresia->asesor_id,
            'canal_venta' => $clienteMembresia->canal_venta ?? 'presencial',
            'fechas_congelacion' => $clienteMembresia->fechas_congelacion ?? [],
            'motivo_cancelacion' => $clienteMembresia->motivo_cancelacion ?? '',
        ];
    }

    protected function mapFormToData(): array
    {
        return [
            'membresia_id' => $this->formData['membresia_id'],
            'fecha_matricula' => $this->formData['fecha_matricula'],
            'fecha_inicio' => $this->formData['fecha_inicio'],
            'fecha_fin' => $this->formData['fecha_fin'],
            'estado' => $this->formData['estado'],
            'precio_lista' => $this->formData['precio_lista'],
            'descuento_monto' => $this->formData['descuento_monto'] ?? 0,
            'precio_final' => $this->formData['precio_final'],
            'asesor_id' => $this->formData['asesor_id'] ?: auth()->id(),
            'canal_venta' => $this->formData['canal_venta'] ?: null,
            'fechas_congelacion' => $this->formData['fechas_congelacion'] ?: null,
            'motivo_cancelacion' => $this->formData['motivo_cancelacion'] ?: null,
        ];
    }

    protected function resetForm(): void
    {
        $this->clienteMembresiaId = null;
        $this->formData = [
            'membresia_id' => '',
            'fecha_matricula' => now()->format('Y-m-d'),
            'fecha_inicio' => '',
            'fecha_fin' => '',
            'estado' => 'activa',
            'precio_lista' => 0.00,
            'descuento_monto' => 0.00,
            'precio_final' => 0.00,
            'asesor_id' => auth()->id(),
            'canal_venta' => 'presencial',
            'fechas_congelacion' => [],
            'motivo_cancelacion' => '',
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

    public function render()
    {
        $membresias = collect([]);
        $membresiasActivas = collect([]);

        if ($this->selectedClienteId) {
            $membresias = $this->service->getByCliente(
                $this->selectedClienteId,
                $this->estadoFilter ?: null,
                $this->perPage
            );
        }

        if ($this->modalState['create']) {
            $membresiasActivas = $this->service->getMembresiasActivas();
        }

        return view('livewire.cliente-membresias.cliente-membresia-live', [
            'membresias' => $membresias,
            'membresiasActivas' => $membresiasActivas,
        ]);
    }
}
