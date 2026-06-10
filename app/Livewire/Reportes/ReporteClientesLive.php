<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Models\User;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteClientesLive extends Component
{
    use PaginatesReportTables;
    use WithPagination;

    public $estadoFilter = '';

    public $fechaDesde = '';

    public $fechaHasta = '';

    /** ID del usuario que registró al cliente (created_by) */
    public $createdById = '';

    /** ID del usuario entrenador asignado (trainer_user_id) */
    public $trainerUserId = '';

    public $vigenciaFilter = '';

    public $ventanaDias = 15;

    public int $perPageClientes = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->fechaDesde = now()->subYear()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingCreatedById(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingTrainerUserId(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingVigenciaFilter(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingVentanaDias(): void
    {
        $this->resetPage('clientesPage');
    }

    public function updatingPerPageClientes(): void
    {
        $this->resetPage('clientesPage');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteClientes(
            $this->estadoFilter ?: null,
            $this->fechaDesde ?: null,
            $this->fechaHasta ?: null,
            $this->createdById !== '' ? (int) $this->createdById : null,
            $this->trainerUserId !== '' ? (int) $this->trainerUserId : null,
            $this->vigenciaFilter ?: null,
            (int) $this->ventanaDias
        );

        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.reportes.reporte-clientes-live', [
            'clientes' => $this->paginateReportCollection($data['clientes'], $this->perPageClientes, 'clientesPage'),
            'resumen' => $data['resumen'],
            'usuarios' => $usuarios,
        ]);
    }
}
