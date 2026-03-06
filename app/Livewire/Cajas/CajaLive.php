<?php

namespace App\Livewire\Cajas;

use App\Livewire\Concerns\FlashesToast;
use App\Services\CajaService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class CajaLive extends Component
{
    use FlashesToast, WithPagination;

    // Filtros y paginación
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $perPage = 15;

    // Estado de modales
    public $mostrarModalApertura = false;
    public $mostrarModalCierre = false;
    public $mostrarModalReporte = false;
    public $mostrarModalHistorial = false;

    // Caja seleccionada
    public $cajaSeleccionada = null;
    public $reporteCierre = null;
    
    // Detalle de venta
    public $mostrarModalDetalleVenta = false;
    public $ventaDetalle = null;

    // Formulario de apertura
    public $formApertura = [
        'saldo_inicial' => '0.00',
        'observaciones_apertura' => '',
    ];

    // Formulario de cierre
    public $formCierre = [
        'observaciones_cierre' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected CajaService $service;

    public function boot(CajaService $service)
    {
        $this->service = $service;
    }

    public function mount()
    {
        $this->authorize('cajas.view');
        // Establecer valores por defecto para los filtros de fecha
        $this->fechaDesde = now()->subDays(15)->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
        $this->resetPage();
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

    /**
     * Abrir modal de apertura de caja
     */
    public function abrirModalApertura()
    {
        $this->authorize('cajas.create');
        $this->resetFormApertura();
        $this->mostrarModalApertura = true;
    }

    /**
     * Cerrar modal de apertura
     */
    public function cerrarModalApertura()
    {
        $this->mostrarModalApertura = false;
        $this->resetFormApertura();
    }

    /**
     * Abrir una nueva caja
     */
    public function abrirCaja()
    {
        $this->validate([
            'formApertura.saldo_inicial' => ['required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'formApertura.observaciones_apertura' => ['nullable', 'string', 'max:1000'],
        ], [
            'formApertura.saldo_inicial.required' => 'El saldo inicial es obligatorio.',
            'formApertura.saldo_inicial.numeric' => 'El saldo inicial debe ser un número válido.',
            'formApertura.saldo_inicial.min' => 'El saldo inicial no puede ser negativo.',
            'formApertura.saldo_inicial.regex' => 'El saldo inicial debe tener máximo 2 decimales.',
            'formApertura.observaciones_apertura.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ]);

        try {
            $this->service->abrirCaja($this->formApertura);
            
            $this->flashToast('success', 'Caja abierta exitosamente.');
            $this->cerrarModalApertura();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Abrir modal de cierre de caja
     */
    public function abrirModalCierre($cajaId)
    {
        $this->mostrarModalHistorial = false;
        $this->cajaSeleccionada = \App\Models\Core\Caja::with(['usuario', 'pagos'])->find($cajaId);

        if (!$this->cajaSeleccionada) {
            $this->flashToast('error', 'Caja no encontrada.');
            return;
        }

        if ($this->cajaSeleccionada->estado === 'cerrada') {
            $this->flashToast('error', 'La caja ya está cerrada.');
            return;
        }

        if ($this->cajaSeleccionada->usuario_id !== Auth::user()->id) {
            $this->flashToast('error', 'Solo el usuario responsable puede cerrar esta caja.');
            return;
        }

        // Generar reporte previo
        $this->reporteCierre = $this->service->generarReporteCierre($this->cajaSeleccionada->id);
        $this->resetFormCierre();
        $this->mostrarModalCierre = true;
    }

    /**
     * Cerrar modal de cierre
     */
    public function cerrarModalCierre()
    {
        $this->mostrarModalCierre = false;
        $this->cajaSeleccionada = null;
        $this->reporteCierre = null;
        $this->resetFormCierre();
    }

    /**
     * Cerrar una caja
     */
    public function cerrarCaja()
    {
        if (!$this->cajaSeleccionada) {
            $this->flashToast('error', 'No se ha seleccionado una caja.');
            return;
        }

        $this->validate([
            'formCierre.observaciones_cierre' => ['nullable', 'string', 'max:1000'],
        ], [
            'formCierre.observaciones_cierre.max' => 'Las observaciones no pueden exceder 1000 caracteres.',
        ]);

        try {
            $this->service->cerrarCaja($this->cajaSeleccionada->id, $this->formCierre);
            
            $this->flashToast('success', 'Caja cerrada exitosamente.');
            $this->cerrarModalCierre();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Ver reporte de cierre
     */
    public function verReporte($cajaId)
    {
        try {
            $this->reporteCierre = $this->service->generarReporteCierre($cajaId);
            $this->cajaSeleccionada = \App\Models\Core\Caja::with(['usuario'])->find($cajaId);
            $this->mostrarModalReporte = true;
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Cerrar modal de reporte
     */
    public function cerrarModalReporte()
    {
        $this->mostrarModalReporte = false;
        $this->cajaSeleccionada = null;
        $this->reporteCierre = null;
    }

    /**
     * Ver detalle de venta
     */
    public function verDetalleVenta($ventaId)
    {
        $venta = \App\Models\Core\Venta::with([
            'items',
            'cliente',
            'usuario',
            'caja'
        ])->find($ventaId);
        
        if (!$venta) {
            $this->flashToast('error', 'Venta no encontrada.');
            return;
        }
        
        $this->ventaDetalle = $venta;
        $this->mostrarModalDetalleVenta = true;
    }
    
    /**
     * Cerrar modal de detalle de venta
     */
    public function cerrarModalDetalleVenta()
    {
        $this->mostrarModalDetalleVenta = false;
        $this->ventaDetalle = null;
    }

    /**
     * Resetear formulario de apertura
     */
    protected function resetFormApertura()
    {
        $this->formApertura = [
            'saldo_inicial' => '0.00',
            'observaciones_apertura' => '',
        ];
    }

    /**
     * Resetear formulario de cierre
     */
    protected function resetFormCierre()
    {
        $this->formCierre = [
            'observaciones_cierre' => '',
        ];
    }

    /**
     * Abrir modal historial de cajas
     */
    public function abrirModalHistorial()
    {
        $this->mostrarModalHistorial = true;
    }

    /**
     * Cerrar modal historial
     */
    public function cerrarModalHistorial()
    {
        $this->mostrarModalHistorial = false;
    }

    /**
     * Obtener caja abierta del usuario actual (solo una por usuario)
     * Con caché para evitar múltiples consultas
     */
    public function getCajasAbiertasProperty()
    {
        $cajaAbierta = $this->service->obtenerCajaAbiertaPorUsuario(Auth::user()->id);
        return $cajaAbierta ? collect([$cajaAbierta]) : collect([]);
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

        $cajas = $this->service->obtenerCajas($this->perPage, $filtros);

        $entradasCajaAbierta = collect([]);
        $salidasCajaAbierta = collect([]);
        $cajaAbierta = $this->service->obtenerCajaAbiertaPorUsuario(Auth::user()->id);
        if ($cajaAbierta) {
            $movimientosEntrada = $cajaAbierta->movimientos()
                ->where('tipo', 'entrada')
                ->with(['usuario', 'referencia'])
                ->orderBy('fecha_movimiento', 'desc')
                ->get();
            $pagos = $cajaAbierta->pagos()
                ->with(['clienteMatricula.membresia', 'clienteMatricula.clase', 'clienteMembresia.membresia'])
                ->orderBy('fecha_pago', 'desc')
                ->get();
            $entradasCajaAbierta = collect([])
                ->merge($movimientosEntrada->map(fn ($m) => (object) [
                    'fecha' => $m->fecha_movimiento,
                    'concepto' => $m->concepto,
                    'monto' => (float) $m->monto,
                ]))
                ->merge($pagos->map(function ($p) {
                    if ($p->cliente_matricula_id && $p->clienteMatricula) {
                        $nombre = $p->clienteMatricula->esMembresia()
                            ? ($p->clienteMatricula->membresia->nombre ?? 'Membresía')
                            : ($p->clienteMatricula->clase->nombre ?? 'Clase');
                        $concepto = 'Pago ' . ($p->clienteMatricula->esMembresia() ? 'membresía' : 'clase') . ': ' . $nombre;
                    } elseif ($p->cliente_membresia_id && $p->clienteMembresia) {
                        $nombre = $p->clienteMembresia->membresia->nombre ?? 'Membresía';
                        $concepto = 'Pago membresía: ' . $nombre;
                    } else {
                        $concepto = 'Pago';
                    }
                    return (object) [
                        'fecha' => $p->fecha_pago,
                        'concepto' => $concepto,
                        'monto' => (float) $p->monto,
                    ];
                }))
                ->sortByDesc('fecha')
                ->values()
                ->take(50);

            $salidasCajaAbierta = $cajaAbierta->movimientos()
                ->where('tipo', 'salida')
                ->with(['usuario', 'referencia'])
                ->orderBy('fecha_movimiento', 'desc')
                ->limit(50)
                ->get();
        }

        return view('livewire.cajas.caja-live', [
            'cajas' => $cajas,
            'entradasCajaAbierta' => $entradasCajaAbierta,
            'salidasCajaAbierta' => $salidasCajaAbierta,
        ]);
    }
}
