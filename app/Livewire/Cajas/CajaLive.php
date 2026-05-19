<?php

namespace App\Livewire\Cajas;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Venta;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\CajaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CajaLive extends Component
{
    use FlashesToast;
    use WithPagination;

    public $fechaDesde = '';

    public $fechaHasta = '';

    public $usuarioFiltro = '';

    public $estadoFiltro = '';

    public $sucursalFiltro = '';

    public $perPage = 15;

    /** Pestaña activa dentro del card Entradas (clave de categoría). */
    public $tabEntradaCategoria = '';

    /** Pestaña activa dentro del card Salidas. */
    public $tabSalidaCategoria = '';

    public $mostrarModalApertura = false;

    public $mostrarModalCierre = false;

    public $mostrarModalReporte = false;

    public $mostrarModalHistorial = false;

    public $mostrarModalIngresoManual = false;

    public $mostrarModalSalidaManual = false;

    public $mostrarModalDetalleVenta = false;

    public bool $mostrarModalTicketPago = false;

    public bool $mostrarModalTicketVenta = false;

    public bool $mostrarModalReporteEntradas = false;

    public ?int $pagoIdTicketCaja = null;

    public ?int $ventaIdTicketCaja = null;

    public $cajaSeleccionada = null;

    public $reporteCierre = null;

    public $ventaDetalle = null;

    public $formApertura = [
        'saldo_inicial' => '0.00',
        'observaciones_apertura' => '',
    ];

    public $formCierre = [
        'saldo_contado' => '0.00',
        'observaciones_cierre' => '',
    ];

    public $formIngresoManual = [
        'caja_id' => null,
        'monto' => '',
        'concepto' => '',
        'observaciones' => '',
    ];

    public $formSalidaManual = [
        'caja_id' => null,
        'monto' => '',
        'concepto' => '',
        'observaciones' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected CajaService $service;

    public function boot(CajaService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('caja.ver');
        $this->fechaDesde = now()->subDays(15)->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
        $this->syncTabsDesdeMovimientos();
    }

    public function updatingFechaDesde()
    {
        $this->resetPage();
    }

    public function updatingFechaHasta()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingUsuarioFiltro()
    {
        $this->resetPage();
    }

    public function updatingEstadoFiltro()
    {
        $this->resetPage();
    }

    public function updatingSucursalFiltro()
    {
        $this->resetPage();
    }

    public function abrirModalApertura()
    {
        $this->authorize('caja.crear');
        $this->resetFormApertura();
        $this->mostrarModalApertura = true;
    }

    public function cerrarModalApertura()
    {
        $this->mostrarModalApertura = false;
        $this->resetFormApertura();
    }

    public function abrirCaja()
    {
        $this->validate([
            'formApertura.saldo_inicial' => ['required', 'numeric', 'min:0'],
            'formApertura.observaciones_apertura' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->abrirCaja($this->formApertura);
            $this->syncTabsDesdeMovimientos();
            $this->flashToast('success', 'Caja abierta exitosamente.');
            $this->cerrarModalApertura();
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function abrirModalCierre($cajaId)
    {
        $caja = Caja::with('usuario')->find($cajaId);
        if (! $caja) {
            $this->flashToast('error', 'Caja no encontrada.');

            return;
        }

        if ($caja->usuario_id !== Auth::id()) {
            $this->flashToast('error', 'Solo el usuario responsable puede cerrar esta caja.');

            return;
        }

        $this->cajaSeleccionada = $caja;
        $this->reporteCierre = $this->service->generarReporteCierre($cajaId);
        $this->resetFormCierre();
        $this->formCierre['saldo_contado'] = number_format((float) ($this->reporteCierre['saldo_final_esperado'] ?? 0), 2, '.', '');
        $this->mostrarModalCierre = true;
    }

    public function cerrarModalCierre()
    {
        $this->mostrarModalCierre = false;
        $this->cajaSeleccionada = null;
        $this->reporteCierre = null;
        $this->resetFormCierre();
    }

    public function cerrarCaja()
    {
        if (! $this->cajaSeleccionada) {
            return;
        }

        $this->validate([
            'formCierre.saldo_contado' => ['required', 'numeric', 'min:0'],
            'formCierre.observaciones_cierre' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->cerrarCaja($this->cajaSeleccionada->id, $this->formCierre);
            $this->tabEntradaCategoria = '';
            $this->tabSalidaCategoria = '';
            $this->flashToast('success', 'Caja cerrada exitosamente.');
            $this->cerrarModalCierre();
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function abrirModalIngresoManual()
    {
        $caja = $this->cajaActiva;
        if (! $caja) {
            $this->flashToast('error', 'Debes tener la caja abierta.');

            return;
        }
        $this->formIngresoManual = [
            'caja_id' => $caja->id,
            'monto' => '',
            'concepto' => '',
            'observaciones' => '',
        ];
        $this->mostrarModalIngresoManual = true;
    }

    public function abrirModalSalidaManual()
    {
        $caja = $this->cajaActiva;
        if (! $caja) {
            $this->flashToast('error', 'Debes tener la caja abierta.');

            return;
        }
        $this->formSalidaManual = [
            'caja_id' => $caja->id,
            'monto' => '',
            'concepto' => '',
            'observaciones' => '',
        ];
        $this->mostrarModalSalidaManual = true;
    }

    public function registrarIngresoManual()
    {
        $this->validate([
            'formIngresoManual.caja_id' => ['required', 'exists:cajas,id'],
            'formIngresoManual.monto' => ['required', 'numeric', 'gt:0'],
            'formIngresoManual.concepto' => ['required', 'string', 'max:255'],
            'formIngresoManual.observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->registrarIngresoManual((int) $this->formIngresoManual['caja_id'], $this->formIngresoManual);
            $this->flashToast('success', 'Ingreso manual registrado.');
            $this->mostrarModalIngresoManual = false;
            $this->syncTabsDesdeMovimientos();
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function registrarSalidaManual()
    {
        if (blank(trim((string) ($this->formSalidaManual['observaciones'] ?? '')))) {
            $this->flashToast('error', 'Debes indicar el motivo de la salida manual.');

            return;
        }

        $this->validate([
            'formSalidaManual.caja_id' => ['required', 'exists:cajas,id'],
            'formSalidaManual.monto' => ['required', 'numeric', 'gt:0'],
            'formSalidaManual.concepto' => ['required', 'string', 'max:255'],
            'formSalidaManual.observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->registrarSalidaManual((int) $this->formSalidaManual['caja_id'], $this->formSalidaManual);
            $this->flashToast('success', 'Salida manual registrada.');
            $this->mostrarModalSalidaManual = false;
            $this->syncTabsDesdeMovimientos();
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function verReporte($cajaId)
    {
        try {
            $this->cajaSeleccionada = Caja::with(['usuario', 'sucursal'])->findOrFail($cajaId);
            $this->mostrarModalReporte = true;
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function cerrarModalReporte()
    {
        $this->mostrarModalReporte = false;
        $this->cajaSeleccionada = null;
    }

    public function getReporteCajaPdfUrlProperty(): ?string
    {
        if (! $this->cajaSeleccionada) {
            return null;
        }

        return route('reportes.cajas.exportar.pdf', [
            'fecha_desde' => $this->fechaDesde ?: null,
            'fecha_hasta' => $this->fechaHasta ?: null,
            'usuario_id' => $this->usuarioFiltro ?: null,
            'sucursal_id' => $this->sucursalFiltro ?: null,
            'caja_id' => $this->cajaSeleccionada->id,
            'inline' => 1,
        ]);
    }

    public function abrirModalHistorial()
    {
        $this->mostrarModalHistorial = true;
    }

    public function cerrarModalHistorial()
    {
        $this->mostrarModalHistorial = false;
    }

    public function verDetalleVenta($ventaId)
    {
        $this->ventaDetalle = Venta::with(['items', 'cliente', 'usuario', 'caja'])->find($ventaId);
        if (! $this->ventaDetalle) {
            $this->flashToast('error', 'Venta no encontrada.');

            return;
        }

        $this->mostrarModalDetalleVenta = true;
    }

    public function cerrarModalDetalleVenta()
    {
        $this->mostrarModalDetalleVenta = false;
        $this->ventaDetalle = null;
    }

    public function abrirModalReporteEntradas(): void
    {
        if (! $this->cajaActiva) {
            $this->flashToast('error', 'Debes tener una caja abierta para ver el reporte de entradas.');

            return;
        }

        $this->mostrarModalReporteEntradas = true;
    }

    public function cerrarModalReporteEntradas(): void
    {
        $this->mostrarModalReporteEntradas = false;
    }

    public function abrirTicketPagoCaja(int $pagoId): void
    {
        $this->pagoIdTicketCaja = $pagoId;
        $this->mostrarModalTicketPago = true;
    }

    public function cerrarModalTicketPagoCaja(): void
    {
        $this->mostrarModalTicketPago = false;
        $this->pagoIdTicketCaja = null;
    }

    public function abrirTicketVentaCaja(int $ventaId): void
    {
        $this->ventaIdTicketCaja = $ventaId;
        $this->mostrarModalTicketVenta = true;
    }

    public function cerrarModalTicketVentaCaja(): void
    {
        $this->mostrarModalTicketVenta = false;
        $this->ventaIdTicketCaja = null;
    }

    public function setTabEntrada(string $categoria): void
    {
        $this->tabEntradaCategoria = $categoria;
    }

    public function setTabSalida(string $categoria): void
    {
        $this->tabSalidaCategoria = $categoria;
    }

    public function getCajaActivaProperty(): ?Caja
    {
        return $this->service->obtenerCajaAbiertaPorUsuario(Auth::id());
    }

    public function getReporteEntradasTicketUrlProperty(): ?string
    {
        if (! $this->cajaActiva) {
            return null;
        }

        return route('cajas.entradas-ticket.pdf', ['caja' => $this->cajaActiva->id]);
    }

    protected function resetFormApertura(): void
    {
        $this->formApertura = [
            'saldo_inicial' => '0.00',
            'observaciones_apertura' => '',
        ];
    }

    protected function resetFormCierre(): void
    {
        $this->formCierre = [
            'saldo_contado' => '0.00',
            'observaciones_cierre' => '',
        ];
    }

    /**
     * Ajusta pestañas si la categoría actual ya no existe (p. ej. tras un movimiento nuevo).
     */
    protected function syncTabsDesdeMovimientos(): void
    {
        $caja = $this->cajaActiva;
        if (! $caja) {
            return;
        }
        $movimientos = $this->service->obtenerResumenCaja($caja, [])['movimientos'] ?? [];
        $entradas = collect($movimientos)->where('tipo', 'entrada')->groupBy('categoria');
        $salidas = collect($movimientos)->where('tipo', 'salida')->groupBy('categoria');
        if ($entradas->isNotEmpty() && ($this->tabEntradaCategoria === '' || ! $entradas->has($this->tabEntradaCategoria))) {
            $this->tabEntradaCategoria = (string) $entradas->keys()->sort()->first();
        }
        if ($salidas->isNotEmpty() && ($this->tabSalidaCategoria === '' || ! $salidas->has($this->tabSalidaCategoria))) {
            $this->tabSalidaCategoria = (string) $salidas->keys()->sort()->first();
        }
    }

    public function render()
    {
        $filtros = [];
        if ($this->fechaDesde) {
            $filtros['fecha_desde'] = $this->fechaDesde;
        }
        if ($this->fechaHasta) {
            $filtros['fecha_hasta'] = $this->fechaHasta;
        }
        if ($this->usuarioFiltro) {
            $filtros['usuario_id'] = $this->usuarioFiltro;
        }
        if ($this->estadoFiltro) {
            $filtros['estado'] = $this->estadoFiltro;
        }
        if ($this->sucursalFiltro) {
            $filtros['sucursal_id'] = $this->sucursalFiltro;
        }

        $cajas = $this->service->obtenerCajas($this->perPage, $filtros);
        $cajaActiva = $this->cajaActiva;
        $resumenCaja = $cajaActiva ? $this->service->obtenerResumenCaja($cajaActiva, []) : null;
        $usuariosCaja = User::query()->orderBy('name')->get(['id', 'name']);
        $sucursalesFiltro = Sucursal::query()->orderByDesc('es_principal')->orderBy('nombre')->get(['id', 'nombre']);

        $categorias = [
            CajaMovimiento::CATEGORIA_MEMBRESIA => 'Membresías',
            CajaMovimiento::CATEGORIA_CLASE => 'Clases',
            CajaMovimiento::CATEGORIA_CUOTA => 'Cuotas',
            CajaMovimiento::CATEGORIA_POS => 'POS',
            CajaMovimiento::CATEGORIA_ALQUILER => 'Alquileres',
            CajaMovimiento::CATEGORIA_MANUAL_INGRESO => 'Ingresos manuales',
            CajaMovimiento::CATEGORIA_MANUAL_SALIDA => 'Salidas manuales',
            CajaMovimiento::CATEGORIA_AJUSTE => 'Ajustes',
            CajaMovimiento::CATEGORIA_APERTURA => 'Apertura',
        ];

        $movimientos = $resumenCaja['movimientos'] ?? [];
        $entradasPorCategoria = collect($movimientos)->where('tipo', 'entrada')->groupBy('categoria')->sortKeys();
        $salidasPorCategoria = collect($movimientos)->where('tipo', 'salida')->groupBy('categoria')->sortKeys();

        $tabEntradaActiva = $entradasPorCategoria->isNotEmpty()
            ? ($entradasPorCategoria->has($this->tabEntradaCategoria)
                ? $this->tabEntradaCategoria
                : (string) $entradasPorCategoria->keys()->first())
            : '';
        $tabSalidaActiva = $salidasPorCategoria->isNotEmpty()
            ? ($salidasPorCategoria->has($this->tabSalidaCategoria)
                ? $this->tabSalidaCategoria
                : (string) $salidasPorCategoria->keys()->first())
            : '';

        $labelCategoria = static function (string $key) use ($categorias): string {
            return $categorias[$key] ?? ucfirst(str_replace('_', ' ', $key));
        };

        return view('livewire.cajas.caja-live', [
            'cajas' => $cajas,
            'cajaActiva' => $cajaActiva,
            'resumenCaja' => $resumenCaja,
            'categorias' => $categorias,
            'entradasPorCategoria' => $entradasPorCategoria,
            'salidasPorCategoria' => $salidasPorCategoria,
            'labelCategoria' => $labelCategoria,
            'tabEntradaActiva' => $tabEntradaActiva,
            'tabSalidaActiva' => $tabSalidaActiva,
            'usuariosCaja' => $usuariosCaja,
            'sucursalesFiltro' => $sucursalesFiltro,
        ]);
    }
}
