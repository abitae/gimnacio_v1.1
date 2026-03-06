<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteMatriculasLive extends Component
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
        $data = $service->datosReporteMatriculas($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-matriculas-live', [
            'matriculas' => $data['matriculas'],
            'resumen' => $data['resumen'],
        ]);
    }
}
