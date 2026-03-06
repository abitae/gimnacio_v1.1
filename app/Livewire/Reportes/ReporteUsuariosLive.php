<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteUsuariosLive extends Component
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
        $data = $service->datosReporteUsuarios($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-usuarios-live', [
            'porUsuario' => $data['por_usuario'],
            'resumen' => $data['resumen'],
        ]);
    }
}
