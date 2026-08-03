<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\AuthorizesReportAccess;
use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Models\User;
use App\Services\ReporteModuloService;
use App\Services\SucursalContext;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteClientesLive extends Component
{
    use AuthorizesReportAccess;
    use PaginatesReportTables;
    use ScopesReporteBySucursal;
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
        $this->authorizeReport('clientes');
        $this->mountReporteSucursalScope();
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
            (int) $this->ventanaDias,
            $this->reporteSucursalFilter(),
        );

        $usuarios = User::orderBy('name')->get(['id', 'name']);
        $trainers = $this->trainersForReporteFilter();

        return view('livewire.reportes.reporte-clientes-live', array_merge([
            'clientes' => $this->paginateReportCollection($data['clientes'], $this->perPageClientes, 'clientesPage'),
            'resumen' => $data['resumen'],
            'usuarios' => $usuarios,
            'trainers' => $trainers,
        ], $this->reporteSucursalScopeViewData()));
    }

    protected function trainersForReporteFilter(): Collection
    {
        $filter = $this->reporteSucursalFilter();
        $context = app(SucursalContext::class);

        if ($filter->isConsolidated()) {
            $sucursalIds = $context->availableForUser(auth()->user())->pluck('id');

            if ($sucursalIds->isEmpty()) {
                return collect();
            }

            return User::role('trainer')
                ->where(function ($query) use ($sucursalIds): void {
                    $query->whereHas('sucursales', fn ($s) => $s->whereIn('id', $sucursalIds))
                        ->orWhereIn('default_sucursal_id', $sucursalIds);
                })
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        $sucursalId = $filter->isSpecific()
            ? $filter->specificSucursalId()
            : $context->getSucursalId();

        return User::trainersForSucursal($sucursalId)->get(['id', 'name']);
    }

    protected function resetReportePagination(): void
    {
        $this->resetPage('clientesPage');
    }
}
