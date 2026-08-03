<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\AuthorizesReportAccess;
use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteClientesMembresiaClasesLive extends Component
{
    use AuthorizesReportAccess;
    use PaginatesReportTables;
    use ScopesReporteBySucursal;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public int $perPageMembresiasActivas = 10;

    public int $perPageClasesActivas = 10;

    public int $perPagePagosMembresia = 10;

    public int $perPagePagosClase = 10;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeReport('clientes-membresia-clases');
        $this->mountReporteSucursalScope();
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetClientesMembresiaPages();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetClientesMembresiaPages();
    }

    public function updatingPerPageMembresiasActivas(): void
    {
        $this->resetPage('membresiasActivasPage');
    }

    public function updatingPerPageClasesActivas(): void
    {
        $this->resetPage('clasesActivasPage');
    }

    public function updatingPerPagePagosMembresia(): void
    {
        $this->resetPage('pagosMembresiaPage');
    }

    public function updatingPerPagePagosClase(): void
    {
        $this->resetPage('pagosClasePage');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteClientesMembresiaClasesActivas(
            $this->fechaDesde ?: null,
            $this->fechaHasta ?: null,
            $this->reporteSucursalFilter(),
        );

        $membresiasActivas = $data['membresias_activas']
            ->map(fn ($membresia) => [
                'cliente' => $membresia->cliente,
                'nombre' => $membresia->membresia?->nombre ?? 'N/A',
                'fecha_inicio' => $membresia->fecha_inicio,
                'fecha_fin' => $membresia->fecha_fin,
                'precio_final' => $membresia->precio_final ?? 0,
            ])
            ->concat($data['matriculas_membresia_activas']->map(fn ($matricula) => [
                'cliente' => $matricula->cliente,
                'nombre' => $matricula->nombre,
                'fecha_inicio' => $matricula->fecha_inicio,
                'fecha_fin' => $matricula->fecha_fin,
                'precio_final' => $matricula->precio_final ?? 0,
            ]))
            ->values();

        return view('livewire.reportes.reporte-clientes-membresia-clases-live', array_merge($data, [
            'membresias_activas' => $this->paginateReportCollection($membresiasActivas, $this->perPageMembresiasActivas, 'membresiasActivasPage'),
            'matriculas_clase_activas' => $this->paginateReportCollection($data['matriculas_clase_activas'], $this->perPageClasesActivas, 'clasesActivasPage'),
            'pagos_membresia' => $this->paginateReportCollection($data['pagos_membresia'], $this->perPagePagosMembresia, 'pagosMembresiaPage'),
            'pagos_clase' => $this->paginateReportCollection($data['pagos_clase'], $this->perPagePagosClase, 'pagosClasePage'),
        ], $this->reporteSucursalScopeViewData()));
    }

    protected function resetClientesMembresiaPages(): void
    {
        $this->resetReportPages([
            'membresiasActivasPage',
            'clasesActivasPage',
            'pagosMembresiaPage',
            'pagosClasePage',
        ]);
    }

    protected function resetReportePagination(): void
    {
        $this->resetClientesMembresiaPages();
    }
}
