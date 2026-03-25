<?php

namespace App\Livewire\ClienteMatriculas;

use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\ManagesClienteMatriculaForm;
use App\Services\ClienteMatriculaService;
use App\Services\ClienteService;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteMatriculaLive extends Component
{
    use FlashesToast, ManagesClienteMatriculaForm, WithPagination;

    public string $clienteSearch = '';

    public $clientes;

    public ?int $selectedClienteId = null;

    public $selectedCliente = null;

    public string $activeTab = 'membresias';

    public string $estadoFilter = '';

    public string $tipoFilter = '';

    public int $perPage = 15;

    public bool $isSearching = false;

    protected $paginationTheme = 'tailwind';

    protected ClienteMatriculaService $matriculaService;

    protected ClienteService $clienteService;

    public function boot(ClienteMatriculaService $matriculaService, ClienteService $clienteService): void
    {
        $this->matriculaService = $matriculaService;
        $this->clienteService = $clienteService;
    }

    public function mount(): void
    {
        $this->authorize('cliente-matriculas.view');
        $this->matriculaForm['asesor_id'] = auth()->id();
        $this->matriculaForm['fecha_matricula'] = now()->format('Y-m-d');
        $this->clientes = collect([]);
    }

    protected function matriculaTabIsMembresias(): bool
    {
        return $this->activeTab === 'membresias';
    }

    public function updatingClienteSearch($value): void
    {
        $this->isSearching = true;

        if ($this->selectedCliente) {
            $nombreCompleto = $this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos;
            $valorTrim = trim((string) $value);
            if ($valorTrim !== $nombreCompleto && $valorTrim !== '') {
                $this->selectedClienteId = null;
                $this->selectedCliente = null;
            }
        }
    }

    public function updatedClienteSearch(): void
    {
        $this->searchClientes();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function updatingTipoFilter(): void
    {
        $this->resetPage();
    }

    public function updatingActiveTab(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function searchClientes(): void
    {
        $searchTerm = trim($this->clienteSearch);

        if (strlen($searchTerm) >= 2) {
            $this->clientes = $this->clienteService->quickSearch($searchTerm, 10);
        } else {
            $this->clientes = collect([]);
        }

        $this->isSearching = false;
    }

    public function selectCliente($clienteId): void
    {
        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);
        $this->clienteSearch = $this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos;
        $this->clientes = collect([]);
        $this->resetPage();
    }

    public function clearClienteSelection(): void
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->resetPage();
    }

    /** @deprecated usar openMatriculaCreateModal; se mantiene para la vista */
    public function openCreateModal(): void
    {
        $this->openMatriculaCreateModal();
    }

    /** @deprecated usar openMatriculaEditModal */
    public function openEditModal($id): void
    {
        $this->openMatriculaEditModal((int) $id);
    }

    /** @deprecated usar openMatriculaDeleteModal */
    public function openDeleteModal($id): void
    {
        $this->openMatriculaDeleteModal((int) $id);
    }

    /** @deprecated usar closeMatriculaModal */
    public function closeModal(): void
    {
        $this->closeMatriculaModal();
    }

    /** @deprecated usar saveMatricula */
    public function save(): void
    {
        $this->saveMatricula();
    }

    /** @deprecated usar deleteMatricula */
    public function delete(): void
    {
        $this->deleteMatricula();
    }

    public function render()
    {
        $matriculas = collect([]);

        if ($this->selectedClienteId) {
            $filtros = [];
            if ($this->estadoFilter) {
                $filtros['estado'] = $this->estadoFilter;
            }
            if ($this->activeTab === 'membresias') {
                $filtros['tipo'] = 'membresia';
            } else {
                $filtros['tipo'] = 'clase';
            }

            $matriculas = $this->matriculaService->getByCliente(
                $this->selectedClienteId,
                $filtros,
                $this->perPage
            );
        }

        $membresiasActivas = collect([]);
        $clasesActivas = collect([]);

        if ($this->matriculaModalState['create']) {
            if ($this->matriculaForm['tipo'] === 'membresia') {
                $membresiasActivas = $this->matriculaService->getMembresiasActivas();
            } else {
                $clasesActivas = $this->matriculaService->getClasesActivas();
            }
        }

        $matriculaMembresiasProximasAVencer = $this->matriculaService->getMembresiasProximasAVencer(30, 20);

        return view('livewire.cliente-matriculas.cliente-matricula-live', [
            'matriculas' => $matriculas,
            'membresiasActivas' => $membresiasActivas,
            'clasesActivas' => $clasesActivas,
            'matriculaMembresiasProximasAVencer' => $matriculaMembresiasProximasAVencer,
        ]);
    }
}
