<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Producto;
use App\Models\Core\ServicioExterno;
use App\Services\CajaService;
use App\Services\ClienteMembresiaService;
use App\Services\ClienteMatriculaService;
use App\Services\ClienteService;
use App\Services\ProductoService;
use App\Services\ServicioExternoService;
use App\Services\VentaService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;

class POSLive extends Component
{
    use FlashesToast;
    // Búsqueda
    public $busqueda = '';
    public $resultadosBusqueda = [];
    public $categoriaFiltro = '';
    public $tipoItem = 'producto'; // 'producto' o 'servicio'

    // Carrito
    public $carrito = [];
    public $clienteId = null;
    public $clienteSeleccionado = null;
    public $tipoComprobante = 'ticket';
    public $metodoPago = 'efectivo';
    public $descuento = 0;
    public $observaciones = '';

    // Estado
    public $mostrarModalCliente = false;
    public $mostrarModalConfirmacion = false;
    public $ventaProcesada = null;

    // Modo Cobrar membresía/clase
    public $modoCobroMembresiaClase = false;
    public $clienteSearchCobro = '';
    public $clientesCobro;
    public $selectedClienteCobro = null;
    public $isSearchingCobro = false;
    public $itemsConSaldo = [];
    public $mostrarModalCobro = false;
    public $cobroItemTipo = null; // 'matricula' | 'membresia'
    public $cobroItemId = null;
    public $saldoPendienteCobro = 0.00;
    public $cobroFormData = [
        'monto_pago' => 0.00,
        'metodo_pago' => 'efectivo',
        'comprobante_tipo' => '',
        'comprobante_numero' => '',
    ];

    protected CajaService $cajaService;
    protected ProductoService $productoService;
    protected ServicioExternoService $servicioExternoService;
    protected VentaService $ventaService;
    protected ClienteService $clienteService;
    protected ClienteMatriculaService $clienteMatriculaService;
    protected ClienteMembresiaService $clienteMembresiaService;

    public function boot(
        CajaService $cajaService,
        ProductoService $productoService,
        ServicioExternoService $servicioExternoService,
        VentaService $ventaService,
        ClienteService $clienteService,
        ClienteMatriculaService $clienteMatriculaService,
        ClienteMembresiaService $clienteMembresiaService
    ) {
        $this->cajaService = $cajaService;
        $this->productoService = $productoService;
        $this->servicioExternoService = $servicioExternoService;
        $this->ventaService = $ventaService;
        $this->clienteService = $clienteService;
        $this->clienteMatriculaService = $clienteMatriculaService;
        $this->clienteMembresiaService = $clienteMembresiaService;
    }

    public function mount()
    {
        $this->authorize('pos.view');
        $this->clientesCobro = collect([]);
        // Validar que haya caja abierta
        if (!$this->cajaService->validarCajaAbierta(auth()->id())) {
            $this->flashToast('error', 'No hay una caja abierta. Por favor, abra una caja antes de usar el punto de venta.');
        }
    }

    public function updatedBusqueda()
    {
        $this->buscar();
    }

    public function updatedCategoriaFiltro()
    {
        // No necesita hacer nada, solo actualizar la vista
    }

    public function updatedTipoItem()
    {
        // Limpiar búsqueda y filtros al cambiar tipo
        $this->busqueda = '';
        $this->resultadosBusqueda = [];
        $this->categoriaFiltro = '';
    }

    /**
     * Buscar productos o servicios
     */
    public function buscar()
    {
        if (empty($this->busqueda)) {
            $this->resultadosBusqueda = [];
            return;
        }

        $this->resultadosBusqueda = [];

        if ($this->tipoItem === 'producto') {
            $productos = $this->productoService->buscarParaPOS($this->busqueda);
            
            foreach ($productos as $producto) {
                $this->resultadosBusqueda[] = [
                    'tipo' => 'producto',
                    'id' => $producto->id,
                    'codigo' => $producto->codigo,
                    'nombre' => $producto->nombre,
                    'precio' => $producto->precio_venta,
                    'stock' => $producto->stock_actual,
                    'imagen' => $producto->imagen,
                ];
            }
        } else {
            $servicios = $this->servicioExternoService->buscarParaPOS($this->busqueda);
            
            foreach ($servicios as $servicio) {
                $this->resultadosBusqueda[] = [
                    'tipo' => 'servicio',
                    'id' => $servicio->id,
                    'codigo' => $servicio->codigo,
                    'nombre' => $servicio->nombre,
                    'precio' => $servicio->precio,
                    'duracion_minutos' => $servicio->duracion_minutos,
                ];
            }
        }
    }

    /**
     * Obtener productos agrupados por categoría
     */
    public function obtenerProductosPorCategoria()
    {
        $query = Producto::with(['categoria'])
            ->where('estado', 'activo')
            ->where('stock_actual', '>', 0);

        if ($this->categoriaFiltro) {
            $query->where('categoria_id', $this->categoriaFiltro);
        }

        $productos = $query->orderBy('nombre')->get();

        return $productos->groupBy(function ($producto) {
            return $producto->categoria ? $producto->categoria->nombre : 'Sin categoría';
        });
    }

    /**
     * Obtener servicios agrupados por categoría
     */
    public function obtenerServiciosPorCategoria()
    {
        $query = ServicioExterno::with(['categoria'])
            ->where('estado', 'activo');

        if ($this->categoriaFiltro) {
            $query->where('categoria_id', $this->categoriaFiltro);
        }

        $servicios = $query->orderBy('nombre')->get();

        return $servicios->groupBy(function ($servicio) {
            return $servicio->categoria ? $servicio->categoria->nombre : 'Sin categoría';
        });
    }

    /**
     * Agregar item al carrito
     */
    public function agregarAlCarrito($item)
    {
        $key = $item['tipo'] . '-' . $item['id'];
        
        if (isset($this->carrito[$key])) {
            $this->carrito[$key]['cantidad']++;
        } else {
            $this->carrito[$key] = [
                'tipo' => $item['tipo'],
                'id' => $item['id'],
                'codigo' => $item['codigo'],
                'nombre' => $item['nombre'],
                'precio' => $item['precio'],
                'cantidad' => 1,
                'descuento' => 0,
            ];
        }

        $this->calcularTotales();
        $this->busqueda = '';
        $this->resultadosBusqueda = [];
    }

    /**
     * Actualizar cantidad en carrito
     */
    public function actualizarCantidad($key, $cantidad)
    {
        if ($cantidad <= 0) {
            $this->eliminarDelCarrito($key);
            return;
        }

        if (isset($this->carrito[$key])) {
            // Validar stock solo para productos
            if ($this->carrito[$key]['tipo'] === 'producto') {
                $producto = Producto::find($this->carrito[$key]['id']);
                if ($producto && !$producto->tieneStockSuficiente($cantidad)) {
                    $this->flashToast('error', "Stock insuficiente. Disponible: {$producto->stock_actual}");
                    return;
                }
            }

            $this->carrito[$key]['cantidad'] = $cantidad;
            $this->calcularTotales();
        }
    }

    /**
     * Eliminar del carrito
     */
    public function eliminarDelCarrito($key)
    {
        unset($this->carrito[$key]);
        $this->calcularTotales();
    }

    /**
     * Calcular totales
     */
    public function calcularTotales()
    {
        // Los totales se calculan en tiempo real en la vista
    }

    /**
     * Obtener subtotal del carrito
     */
    public function getSubtotalProperty(): float
    {
        $subtotal = 0;
        foreach ($this->carrito as $item) {
            $precioItem = ($item['precio'] * $item['cantidad']) - ($item['descuento'] ?? 0);
            $subtotal += $precioItem;
        }
        return $subtotal;
    }

    /**
     * Obtener IGV (desglosado, ya que está incluido en el precio)
     */
    public function getIgvProperty(): float
    {
        $base = $this->subtotal - $this->descuento;
        // IGV incluido: calcular el IGV del monto que ya lo incluye
        // Si el precio incluye IGV, entonces: precio = base * 1.18
        // Por lo tanto: base = precio / 1.18, e IGV = base * 0.18
        // Simplificado: IGV = precio * 18/118
        return round($base * 18 / 118, 2);
    }

    /**
     * Obtener subtotal sin IGV (para desglose)
     */
    public function getSubtotalSinIgvProperty(): float
    {
        $base = $this->subtotal - $this->descuento;
        return round($base - $this->igv, 2);
    }

    /**
     * Obtener total (el precio ya incluye IGV, solo restamos descuento)
     */
    public function getTotalProperty(): float
    {
        return $this->subtotal - $this->descuento;
    }

    /**
     * Seleccionar cliente
     */
    public function seleccionarCliente($clienteId)
    {
        $this->clienteId = $clienteId;
        $this->clienteSeleccionado = \App\Models\Core\Cliente::find($clienteId);
        $this->mostrarModalCliente = false;
    }

    /**
     * Limpiar cliente
     */
    public function limpiarCliente()
    {
        $this->clienteId = null;
        $this->clienteSeleccionado = null;
    }

    /**
     * Procesar venta
     */
    public function procesarVenta()
    {
        if (empty($this->carrito)) {
            $this->flashToast('error', 'El carrito está vacío.');
            return;
        }

        try {
            // Preparar items para el servicio (productos y servicios)
            $items = [];
            foreach ($this->carrito as $item) {
                $items[] = [
                    'tipo' => $item['tipo'], // 'producto' o 'servicio'
                    'id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'descuento' => $item['descuento'] ?? 0,
                ];
            }

            $venta = $this->ventaService->procesarVenta([
                'cliente_id' => $this->clienteId,
                'tipo_comprobante' => $this->tipoComprobante,
                'metodo_pago' => $this->metodoPago,
                'descuento' => $this->descuento,
                'observaciones' => $this->observaciones,
                'items' => $items,
            ]);

            $this->ventaProcesada = $venta;
            $this->limpiarCarrito();
            $this->mostrarModalConfirmacion = true;
            $this->flashToast('success', 'Venta procesada exitosamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Limpiar carrito
     */
    public function limpiarCarrito()
    {
        $this->carrito = [];
        $this->descuento = 0;
        $this->observaciones = '';
        $this->clienteId = null;
        $this->clienteSeleccionado = null;
    }

    /**
     * Cerrar modal de confirmación
     */
    public function cerrarModalConfirmacion()
    {
        $this->mostrarModalConfirmacion = false;
        $this->ventaProcesada = null;
    }

    // --- Cobrar membresía/clase ---

    public function activarModoCobroMembresiaClase()
    {
        $this->modoCobroMembresiaClase = true;
        $this->selectedClienteCobro = null;
        $this->clienteSearchCobro = '';
        $this->clientesCobro = collect([]);
        $this->itemsConSaldo = [];
    }

    public function desactivarModoCobroMembresiaClase()
    {
        $this->modoCobroMembresiaClase = false;
        $this->selectedClienteCobro = null;
        $this->clienteSearchCobro = '';
        $this->clientesCobro = collect([]);
        $this->itemsConSaldo = [];
    }

    public function updatedClienteSearchCobro()
    {
        $this->searchClientesCobro();
    }

    public function searchClientesCobro()
    {
        $searchTerm = trim($this->clienteSearchCobro);
        if (strlen($searchTerm) >= 2) {
            $this->isSearchingCobro = true;
            $this->clientesCobro = $this->clienteService->quickSearch($searchTerm, 10);
            $this->isSearchingCobro = false;
        } else {
            $this->clientesCobro = collect([]);
        }
    }

    public function selectClienteCobro($clienteId)
    {
        $this->selectedClienteCobro = $this->clienteService->find((int) $clienteId);
        if (!$this->selectedClienteCobro) {
            return;
        }
        $this->clienteSearchCobro = $this->selectedClienteCobro->nombres . ' ' . $this->selectedClienteCobro->apellidos;
        $this->clientesCobro = collect([]);
        $this->cargarItemsConSaldo();
    }

    public function clearClienteCobro()
    {
        $this->selectedClienteCobro = null;
        $this->clienteSearchCobro = '';
        $this->clientesCobro = collect([]);
        $this->itemsConSaldo = [];
    }

    public function cargarItemsConSaldo()
    {
        $this->itemsConSaldo = [];
        if (!$this->selectedClienteCobro) {
            return;
        }
        $clienteId = $this->selectedClienteCobro->id;

        $matriculas = $this->clienteMatriculaService->getByCliente($clienteId, [], 100);
        foreach ($matriculas->items() as $mat) {
            $saldo = $this->clienteMatriculaService->obtenerSaldoPendiente($mat->id);
            if ($saldo > 0) {
                $this->itemsConSaldo[] = [
                    'tipo' => 'matricula',
                    'id' => $mat->id,
                    'nombre' => $mat->nombre,
                    'saldo_pendiente' => $saldo,
                ];
            }
        }

        $membresias = $this->clienteMembresiaService->getByCliente($clienteId, null, 100);
        foreach ($membresias->items() as $mem) {
            $saldo = $this->clienteMembresiaService->obtenerSaldoPendiente($mem->id);
            if ($saldo > 0) {
                $this->itemsConSaldo[] = [
                    'tipo' => 'membresia',
                    'id' => $mem->id,
                    'nombre' => $mem->membresia ? $mem->membresia->nombre : 'N/A',
                    'saldo_pendiente' => $saldo,
                ];
            }
        }
    }

    public function openCobroModal(string $tipo, int $id)
    {
        $this->cobroItemTipo = $tipo;
        $this->cobroItemId = $id;
        if ($tipo === 'matricula') {
            $this->saldoPendienteCobro = $this->clienteMatriculaService->obtenerSaldoPendiente($id);
        } else {
            $this->saldoPendienteCobro = $this->clienteMembresiaService->obtenerSaldoPendiente($id);
        }
        $this->cobroFormData['monto_pago'] = $this->saldoPendienteCobro;
        $this->cobroFormData['metodo_pago'] = 'efectivo';
        $this->cobroFormData['comprobante_tipo'] = '';
        $this->cobroFormData['comprobante_numero'] = '';
        $this->mostrarModalCobro = true;
    }

    public function cerrarModalCobro()
    {
        $this->mostrarModalCobro = false;
        $this->cobroItemTipo = null;
        $this->cobroItemId = null;
        $this->saldoPendienteCobro = 0.00;
        $this->cobroFormData = [
            'monto_pago' => 0.00,
            'metodo_pago' => 'efectivo',
            'comprobante_tipo' => '',
            'comprobante_numero' => '',
        ];
        $this->cargarItemsConSaldo();
    }

    public function procesarCobro()
    {
        try {
            if (!$this->cobroItemId || !$this->cobroItemTipo) {
                $this->flashToast('error', 'No se ha seleccionado un ítem para cobrar.');
                return;
            }
            $data = [
                'monto_pago' => (float) $this->cobroFormData['monto_pago'],
                'metodo_pago' => $this->cobroFormData['metodo_pago'] ?? 'efectivo',
                'comprobante_tipo' => $this->cobroFormData['comprobante_tipo'] ?? '',
                'comprobante_numero' => $this->cobroFormData['comprobante_numero'] ?? '',
            ];
            if ($this->cobroItemTipo === 'matricula') {
                $this->clienteMatriculaService->procesarPago($this->cobroItemId, $data);
            } else {
                $this->clienteMembresiaService->procesarPago($this->cobroItemId, $data);
            }
            $this->flashToast('success', 'Cobro registrado correctamente. El pago se ha reportado a la caja abierta.');
            $this->cerrarModalCobro();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $clientes = \App\Models\Core\Cliente::where('estado_cliente', 'activo')
            ->orderBy('nombres')
            ->limit(50)
            ->get();

        // Obtener categorías según el tipo seleccionado
        if ($this->tipoItem === 'producto') {
            $categorias = \App\Models\Core\CategoriaProducto::where('estado', 'activa')
                ->orderBy('nombre')
                ->get();
            $itemsPorCategoria = $this->obtenerProductosPorCategoria();
        } else {
            $categorias = \App\Models\Core\CategoriaServicio::where('estado', 'activa')
                ->orderBy('nombre')
                ->get();
            $itemsPorCategoria = $this->obtenerServiciosPorCategoria();
        }

        // Clientes con deuda (tienen al menos un pago con saldo_pendiente > 0 y deuda_total > 0)
        $clienteIdsConSaldo = \App\Models\Core\Pago::where('saldo_pendiente', '>', 0)
            ->distinct()
            ->pluck('cliente_id')
            ->filter()
            ->unique()
            ->values();
        $clientesConDeuda = collect([]);
        if ($clienteIdsConSaldo->isNotEmpty()) {
            $clientesConDeuda = \App\Models\Core\Cliente::whereIn('id', $clienteIdsConSaldo)
                ->orderBy('nombres')
                ->get()
                ->filter(fn ($c) => $c->deuda_total > 0)
                ->values();
        }

        return view('livewire.p-o-s.p-o-s-live', [
            'clientes' => $clientes,
            'categorias' => $categorias,
            'itemsPorCategoria' => $itemsPorCategoria,
            'clientesConDeuda' => $clientesConDeuda,
        ]);
    }

    /**
     * Ir al modo cobro y seleccionar el cliente para cobrar su deuda.
     */
    public function irACobrarCliente(int $clienteId): void
    {
        $this->activarModoCobroMembresiaClase();
        $this->selectClienteCobro($clienteId);
    }
}
