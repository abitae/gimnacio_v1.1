<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\AuthorizesReportAccess;
use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteMatriculasLive extends Component
{
    use AuthorizesReportAccess;
    use PaginatesReportTables;
    use ScopesReporteBySucursal;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public int $perPageMatriculas = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeReport('matriculas');
        $this->mountReporteSucursalScope();
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage('matriculasPage');
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage('matriculasPage');
    }

    public function updatingPerPageMatriculas(): void
    {
        $this->resetPage('matriculasPage');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteMatriculas($this->fechaDesde, $this->fechaHasta, $this->reporteSucursalFilter());

        return view('livewire.reportes.reporte-matriculas-live', array_merge([
            'matriculas' => $this->paginateReportCollection($data['matriculas'], $this->perPageMatriculas, 'matriculasPage'),
            'resumen' => $data['resumen'],
        ], $this->reporteSucursalScopeViewData()));
    }

    protected function resetReportePagination(): void
    {
        $this->resetPage('matriculasPage');
    }
}
