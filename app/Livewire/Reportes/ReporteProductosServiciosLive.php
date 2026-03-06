<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteProductosServiciosLive extends Component
{
    public $fechaDesde = '';

    public $fechaHasta = '';

    public function mount(): void
    {
        $this->authorize('reportes.view');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteProductosServicios($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-productos-servicios-live', [
            'itemsMasVendidos' => $data['items_mas_vendidos'],
            'productosBajoStock' => $data['productos_bajo_stock'],
            'resumen' => $data['resumen'],
        ]);
    }
}
