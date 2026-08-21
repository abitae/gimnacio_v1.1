<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\AuthorizesReportAccess;
use App\Livewire\Reportes\Concerns\PaginatesReportTables;
use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\ReporteModuloService;
use App\Support\CajaMatrizTotales;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCajasLive extends Component
{
    use AuthorizesReportAccess;
    use PaginatesReportTables;
    use ScopesReporteBySucursal;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public $usuarioId = '';

    public int $perPageCajas = 10;

    public int $perPageMovimientos = 15;

    public int $perPageUsuarios = 10;

    public int $perPageVentasCredito = 10;

    public int $perPageMatriz = 10;

    public int $perPageMatrizDetalle = 15;

    public bool $mostrarModalDetalleCaja = false;

    public bool $mostrarModalTicketVenta = false;

    public bool $mostrarModalTicketPago = false;

    public bool $mostrarModalMatrizPdf = false;

    public bool $mostrarModalMatrizDetalle = false;

    public ?string $matrizDetalleTipo = null;

    public ?string $matrizDetalleMetodo = null;

    public ?int $cajaDetalleId = null;

    public ?int $ventaIdTicketReporte = null;

    public ?int $pagoIdTicketReporte = null;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeReport('cajas');
        $this->mountReporteSucursalScope();
        $this->fechaDesde = now()->startOfDay()->format('Y-m-d\TH:i');
        $this->fechaHasta = now()->setTime(23, 59)->format('Y-m-d\TH:i');
    }

    public function updatingFechaDesde(): void
    {
        $this->resetReportePagination();
    }

    public function updatingFechaHasta(): void
    {
        $this->resetReportePagination();
    }

    public function updatingUsuarioId(): void
    {
        $this->resetReportePagination();
    }

    public function updatingPerPageCajas(): void
    {
        $this->resetPage('cajasPage');
    }

    public function updatingPerPageMovimientos(): void
    {
        $this->resetPage('movimientosPage');
    }

    public function updatingPerPageUsuarios(): void
    {
        $this->resetPage('usuariosPage');
    }

    public function updatingPerPageVentasCredito(): void
    {
        $this->resetPage('ventasCreditoPage');
    }

    public function updatingPerPageMatriz(): void
    {
        $this->resetPage('matrizTiposPage');
    }

    public function updatingPerPageMatrizDetalle(): void
    {
        $this->resetPage('matrizDetallePage');
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

    public function abrirPreviewMatrizPdf(): void
    {
        $this->mostrarModalMatrizPdf = true;
    }

    public function cerrarPreviewMatrizPdf(): void
    {
        $this->mostrarModalMatrizPdf = false;
    }

    public function abrirDetalleMatriz(?string $tipo = null, ?string $metodo = null): void
    {
        $this->matrizDetalleTipo = filled($tipo) ? $tipo : null;
        $this->matrizDetalleMetodo = filled($metodo) ? $metodo : null;
        $this->resetPage('matrizDetallePage');
        $this->mostrarModalMatrizDetalle = true;
    }

    public function cerrarDetalleMatriz(): void
    {
        $this->mostrarModalMatrizDetalle = false;
        $this->matrizDetalleTipo = null;
        $this->matrizDetalleMetodo = null;
        $this->resetPage('matrizDetallePage');
    }

    /**
     * @return array<string, mixed>
     */
    public function matrizPdfQuery(bool $inline = true): array
    {
        return array_filter([
            'fecha_desde' => $this->fechaDesde ?: null,
            'fecha_hasta' => $this->fechaHasta ?: null,
            'usuario_id' => $this->usuarioId ?: null,
            'reporte_modo_sucursal' => $this->reporteModoSucursal,
            'reporte_sucursal_id' => ($this->reporteModoSucursal === 'specific' && $this->reporteSucursalId)
                ? $this->reporteSucursalId
                : null,
            'seccion' => 'matriz',
            'inline' => $inline ? 1 : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function getMatrizDetalleTituloProperty(): string
    {
        if ($this->matrizDetalleTipo && $this->matrizDetalleMetodo) {
            return $this->matrizDetalleTipo.' · '.$this->matrizDetalleMetodo;
        }

        if ($this->matrizDetalleTipo) {
            return 'Total · '.$this->matrizDetalleTipo;
        }

        if ($this->matrizDetalleMetodo) {
            return 'Total · '.$this->matrizDetalleMetodo;
        }

        return 'Total del período';
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteCajas(
            $this->fechaDesde,
            $this->fechaHasta,
            null,
            $this->usuarioId ? (int) $this->usuarioId : null,
            null,
            $this->reporteSucursalFilter(),
        );

        $matriz = $data['resumen']['matriz_tipo_metodo'] ?? [];
        $movimientosMatrizDetalle = collect($data['detalle_movimientos'] ?? [])
            ->filter(fn (array $movimiento): bool => CajaMatrizTotales::coincide(
                $movimiento,
                $this->matrizDetalleTipo,
                $this->matrizDetalleMetodo,
            ))
            ->values();

        return view('livewire.reportes.reporte-cajas-live', array_merge([
            'cajas' => $this->paginateReportCollection($data['cajas'], $this->perPageCajas, 'cajasPage'),
            'resumen' => $data['resumen'],
            'matrizTipoMetodo' => $matriz,
            'tiposMatriz' => $this->paginateReportCollection($matriz['tipos'] ?? [], $this->perPageMatriz, 'matrizTiposPage'),
            'ventasCredito' => $this->paginateReportCollection($data['ventas_credito'] ?? [], $this->perPageVentasCredito, 'ventasCreditoPage'),
            'porUsuario' => $this->paginateReportCollection($data['resumen']['por_usuario'] ?? [], $this->perPageUsuarios, 'usuariosPage'),
            'detalleMovimientos' => $this->paginateReportCollection($data['detalle_movimientos'], $this->perPageMovimientos, 'movimientosPage'),
            'movimientosMatrizDetalle' => $this->paginateReportCollection(
                $movimientosMatrizDetalle,
                $this->perPageMatrizDetalle,
                'matrizDetallePage'
            ),
            'usuarios' => \App\Models\User::query()
                ->whereIn('id', $data['cajas']->pluck('usuario_id')->filter()->unique())
                ->orderBy('name')
                ->get(['id', 'name']),
        ], $this->reporteSucursalScopeViewData()));
    }

    protected function resetReportePagination(): void
    {
        $this->resetReportPages([
            'cajasPage',
            'movimientosPage',
            'usuariosPage',
            'ventasCreditoPage',
            'matrizTiposPage',
            'matrizDetallePage',
        ]);
    }
}
