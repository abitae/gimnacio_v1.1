<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteGimnasioLive extends Component
{
    use ScopesReporteBySucursal;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->mountReporteSucursalScope();
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteGimnasio($this->fechaDesde, $this->fechaHasta, $this->reporteSucursalFilter());

        return view('livewire.reportes.reporte-gimnasio-live', array_merge([
            'resumen' => $data['resumen'],
            'fechaDesde' => $data['fecha_desde'],
            'fechaHasta' => $data['fecha_hasta'],
        ], $this->reporteSucursalScopeViewData()));
    }
}
