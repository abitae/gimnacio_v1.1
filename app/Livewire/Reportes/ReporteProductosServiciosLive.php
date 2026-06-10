<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Services\ReporteModuloService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteProductosServiciosLive extends Component
{
    use PaginatesReportTables;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public int $perPageItemsMasVendidos = 10;

    public int $perPageProductosBajoStock = 10;

    public int $perPageProductosPorCaja = 10;

    public int $perPageProductosPorUsuario = 10;

    public int $perPageDetalleProductosVendidos = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetProductosPages();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetProductosPages();
    }

    public function updatingPerPageItemsMasVendidos(): void
    {
        $this->resetPage('itemsMasVendidosPage');
    }

    public function updatingPerPageProductosBajoStock(): void
    {
        $this->resetPage('productosBajoStockPage');
    }

    public function updatingPerPageProductosPorCaja(): void
    {
        $this->resetPage('productosPorCajaPage');
    }

    public function updatingPerPageProductosPorUsuario(): void
    {
        $this->resetPage('productosPorUsuarioPage');
    }

    public function updatingPerPageDetalleProductosVendidos(): void
    {
        $this->resetPage('detalleProductosVendidosPage');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteProductosServicios($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-productos-servicios-live', [
            'itemsMasVendidos' => $this->paginateReportCollection($data['items_mas_vendidos'], $this->perPageItemsMasVendidos, 'itemsMasVendidosPage'),
            'productosPorCaja' => $this->paginateReportCollection($data['productos_por_caja'], $this->perPageProductosPorCaja, 'productosPorCajaPage'),
            'productosPorUsuario' => $this->paginateReportCollection($data['productos_por_usuario'], $this->perPageProductosPorUsuario, 'productosPorUsuarioPage'),
            'detalleProductosVendidos' => $this->paginateReportCollection($data['detalle_productos_vendidos'], $this->perPageDetalleProductosVendidos, 'detalleProductosVendidosPage'),
            'productosBajoStock' => $this->paginateReportCollection($data['productos_bajo_stock'], $this->perPageProductosBajoStock, 'productosBajoStockPage'),
            'resumen' => $data['resumen'],
        ]);
    }

    protected function resetProductosPages(): void
    {
        $this->resetReportPages([
            'itemsMasVendidosPage',
            'productosBajoStockPage',
            'productosPorCajaPage',
            'productosPorUsuarioPage',
            'detalleProductosVendidosPage',
        ]);
    }
}
