<?php

namespace App\Livewire\Clientes;

use App\Livewire\Concerns\FlashesToast;
use App\Services\ClienteService;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteLive extends Component
{
    use FlashesToast;
    use WithPagination;

    public $search = '';

    public $codigoSearch = '';

    public $estadoFilter = '';

    public $perPage = 15;

    protected $paginationTheme = 'tailwind';

    protected ClienteService $service;

    public function boot(ClienteService $service): void
    {
        $this->service = $service;
    }

    public function mount(): void
    {
        $this->authorize('cliente.ver');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCodigoSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function verPerfil(int $id): void
    {
        $this->redirect(route('clientes.perfil', ['cliente' => $id]), navigate: true);
    }

    public function render()
    {
        $codigoTrim = trim($this->codigoSearch);

        if ($this->search || $this->estadoFilter || $codigoTrim !== '') {
            $clientes = $this->service->search(
                $this->search,
                $this->estadoFilter ?: null,
                $this->perPage,
                $codigoTrim !== '' ? $codigoTrim : null
            );
        } else {
            $clientes = $this->service->paginate($this->perPage);
        }

        return view('livewire.clientes.cliente-live', [
            'clientes' => $clientes,
        ]);
    }
}
