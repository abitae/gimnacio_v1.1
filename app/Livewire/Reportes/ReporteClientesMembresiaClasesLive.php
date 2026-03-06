<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteClientesMembresiaClasesLive extends Component
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
        $data = $service->datosReporteClientesMembresiaClasesActivas(
            $this->fechaDesde ?: null,
            $this->fechaHasta ?: null
        );

        return view('livewire.reportes.reporte-clientes-membresia-clases-live', $data);
    }
}
