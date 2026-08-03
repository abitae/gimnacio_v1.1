<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\AuthorizesReportAccess;
use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteFinancieroLive extends Component
{
    use AuthorizesReportAccess;
    use PaginatesReportTables;
    use ScopesReporteBySucursal;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public int $perPagePagos = 10;

    public int $perPageVentas = 10;

    public bool $mostrarModalTicketPago = false;

    public ?int $pagoIdTicket = null;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeReport('financiero');
        $this->mountReporteSucursalScope();
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetReportPages(['pagosPage', 'ventasPage']);
    }

    public function updatingFechaHasta(): void
    {
        $this->resetReportPages(['pagosPage', 'ventasPage']);
    }

    public function updatingPerPagePagos(): void
    {
        $this->resetPage('pagosPage');
    }

    public function updatingPerPageVentas(): void
    {
        $this->resetPage('ventasPage');
    }

    public function abrirTicketPago(int $pagoId): void
    {
        $this->pagoIdTicket = $pagoId;
        $this->mostrarModalTicketPago = true;
    }

    public function cerrarModalTicketPago(): void
    {
        $this->mostrarModalTicketPago = false;
        $this->pagoIdTicket = null;
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteFinanciero($this->fechaDesde, $this->fechaHasta, $this->reporteSucursalFilter());

        return view('livewire.reportes.reporte-financiero-live', array_merge([
            'pagos' => $this->paginateReportCollection($data['pagos'], $this->perPagePagos, 'pagosPage'),
            'ventas' => $this->paginateReportCollection($data['ventas'], $this->perPageVentas, 'ventasPage'),
            'resumen' => $data['resumen'],
        ], $this->reporteSucursalScopeViewData()));
    }

    protected function resetReportePagination(): void
    {
        $this->resetReportPages(['pagosPage', 'ventasPage']);
    }
}
