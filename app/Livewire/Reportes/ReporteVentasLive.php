<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteVentasLive extends Component
{
    use PaginatesReportTables;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public int $perPageVentas = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage('ventasPage');
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage('ventasPage');
    }

    public function updatingPerPageVentas(): void
    {
        $this->resetPage('ventasPage');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteVentas($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-ventas-live', [
            'ventas' => $this->paginateReportCollection($data['ventas'], $this->perPageVentas, 'ventasPage'),
            'resumen' => $data['resumen'],
        ]);
    }
}
