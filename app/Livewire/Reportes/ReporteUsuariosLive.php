<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteUsuariosLive extends Component
{
    use PaginatesReportTables;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public int $perPageUsuarios = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetPage('usuariosPage');
    }

    public function updatingFechaHasta(): void
    {
        $this->resetPage('usuariosPage');
    }

    public function updatingPerPageUsuarios(): void
    {
        $this->resetPage('usuariosPage');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteUsuarios($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-usuarios-live', [
            'porUsuario' => $this->paginateReportCollection($data['por_usuario'], $this->perPageUsuarios, 'usuariosPage'),
            'resumen' => $data['resumen'],
        ]);
    }
}
