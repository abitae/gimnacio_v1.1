<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCajasLive extends Component
{
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public $sucursalId = '';

    public $usuarioId = '';

    public int $perPageCajas = 10;

    public int $perPageMovimientos = 15;

    public bool $mostrarModalDetalleCaja = false;

    public bool $mostrarModalTicketVenta = false;

    public bool $mostrarModalTicketPago = false;

    public ?int $cajaDetalleId = null;

    public ?int $ventaIdTicketReporte = null;

    public ?int $pagoIdTicketReporte = null;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetReportPages();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetReportPages();
    }

    public function updatingSucursalId(): void
    {
        $this->resetReportPages();
    }

    public function updatingUsuarioId(): void
    {
        $this->resetReportPages();
    }

    public function updatingPerPageCajas(): void
    {
        $this->resetPage('cajasPage');
    }

    public function updatingPerPageMovimientos(): void
    {
        $this->resetPage('movimientosPage');
    }

    public function abrirDetalleCaja(int $cajaId): void
    {
        $this->cajaDetalleId = $cajaId;
        $this->mostrarModalDetalleCaja = true;
    }

    public function cerrarDetalleCaja(): void
    {
        $this->mostrarModalDetalleCaja = false;
        $this->cajaDetalleId = null;
    }

    public function abrirTicketVenta(int $ventaId): void
    {
        $this->ventaIdTicketReporte = $ventaId;
        $this->mostrarModalTicketVenta = true;
    }

    public function cerrarTicketVenta(): void
    {
        $this->mostrarModalTicketVenta = false;
        $this->ventaIdTicketReporte = null;
    }

    public function abrirTicketPago(int $pagoId): void
    {
        $this->pagoIdTicketReporte = $pagoId;
        $this->mostrarModalTicketPago = true;
    }

    public function cerrarTicketPago(): void
    {
        $this->mostrarModalTicketPago = false;
        $this->pagoIdTicketReporte = null;
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
            'cajas' => $this->paginateCollection($data['cajas'], $this->perPageCajas, 'cajasPage'),
            'resumen' => $data['resumen'],
            'detalleMovimientos' => $this->paginateCollection($data['detalle_movimientos'], $this->perPageMovimientos, 'movimientosPage'),
            'sucursales' => \App\Models\System\Sucursal::query()->orderBy('nombre')->get(['id', 'nombre']),
            'usuarios' => \App\Models\User::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    protected function resetReportPages(): void
    {
        $this->resetPage('cajasPage');
        $this->resetPage('movimientosPage');
    }

    protected function paginateCollection($items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $collection = $items instanceof Collection ? $items->values() : collect($items)->values();
        $page = max(1, (int) $this->getPage($pageName));
        $perPage = max(1, $perPage);

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }
}
