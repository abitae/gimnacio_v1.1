<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteCajasLive extends Component
{
    public $fechaDesde = '';

    public $fechaHasta = '';

    public $sucursalId = '';

    public $usuarioId = '';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteCajas(
            $this->fechaDesde,
            $this->fechaHasta,
            $this->sucursalId ? (int) $this->sucursalId : null,
            $this->usuarioId ? (int) $this->usuarioId : null,
        );

        return view('livewire.reportes.reporte-cajas-live', [
            'cajas' => $data['cajas'],
            'resumen' => $data['resumen'],
            'detalleMovimientos' => $data['detalle_movimientos'],
            'sucursales' => \App\Models\System\Sucursal::query()->orderBy('nombre')->get(['id', 'nombre']),
            'usuarios' => \App\Models\User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }
}
