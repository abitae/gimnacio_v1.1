<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\PaymentMethod;
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
    public $descuento = 0;
    public $observaciones = '';

    // Modal Procesar venta (paso 2): tipo comprador, cupón, comprobante, pago
    /** @var string 'cliente'|'empleado'|'cliente_solo_venta' */
    public $tipoComprador = 'cliente';
    public $clienteId = null;
    public $clienteSeleccionado = null;
    public $employeeId = null;
    public $employeeSeleccionado = null;
    public $clienteSoloVentaNombre = '';
    public $clienteSoloVentaDocumento = '';
    public $clienteSoloVentaTelefono = '';
    /** Búsqueda en modal Procesar venta (por nombre o documento) */
    public $clienteSearchProcesar = '';
    public $employeeSearchProcesar = '';
    /** @var \Illuminate\Support\Collection */
    public $clientesProcesar;
    /** @var \Illuminate\Support\Collection */
    public $employeesProcesar;
    public $tipoComprobante = 'ticket';
    /** @var int|null ID del método de pago (PaymentMethod) */
    public $paymentMethodId = null;
    public $numeroOperacion = '';
    public $entidadFinanciera = '';
    public $codigoCupon = '';
    public $cuponAplicado = null;
    public $montoDescuentoCupon = 0.0;
    public $esCredito = false;
    public $montoInicial = 0.0;
    public $fechaVencimientoDeuda = '';

    // Estado modales
    public $mostrarModalCliente = false;
    public $mostrarModalProcesarVenta = false;
    public $mostrarModalConfirmacionVenta = false; // resumen antes de confirmar
    public $mostrarModalConfirmacion = false; // post-venta (legacy)
    public $ventaProcesada = null;
    /** ID de la venta para mostrar el PDF del comprobante en modal */
    public $ventaIdComprobante = null;
    public $mostrarModalComprobante = false;

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
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
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
        $this->clientesProcesar = collect([]);
        $this->employeesProcesar = collect([]);
        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->paymentMethodId = $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id;
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

    public function updatedEmployeeId($value)
    {
        $this->employeeSeleccionado = $value ? \App\Models\Core\Employee::find($value) : null;
    }

    public function updatedTipoComprador($value)
    {
        if ($value === 'cliente_solo_venta') {
            $this->esCredito = false;
        }
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
     * Base para totales (subtotal - descuento - cupón)
     */
    public function getBaseParaTotalProperty(): float
    {
        return $this->subtotal - (float) $this->descuento - (float) $this->montoDescuentoCupon;
    }

    /**
     * Obtener IGV (desglosado, ya que está incluido en el precio)
     */
    public function getIgvProperty(): float
    {
        $base = max(0, $this->baseParaTotal);
        return round($base * 18 / 118, 2);
    }

    /**
     * Obtener subtotal sin IGV (para desglose)
     */
    public function getSubtotalSinIgvProperty(): float
    {
        $base = max(0, $this->baseParaTotal);
        return round($base - $this->igv, 2);
    }

    /**
     * Obtener total (subtotal - descuento - cupón)
     */
    public function getTotalProperty(): float
    {
        return max(0, $this->baseParaTotal);
    }

    /**
     * Abrir modal Procesar venta (solo si hay ítems en el carrito)
     */
    public function abrirModalProcesarVenta()
    {
        if (empty($this->carrito)) {
            $this->flashToast('error', 'El carrito está vacío.');
            return;
        }
        $this->mostrarModalProcesarVenta = true;
    }

    /**
     * Cerrar modal Procesar venta y opcionalmente volver a estado inicial
     */
    public function cerrarModalProcesarVenta()
    {
        $this->mostrarModalProcesarVenta = false;
    }

    /**
     * Validar datos del modal Procesar venta y abrir modal Confirmación (resumen)
     */
    public function abrirModalConfirmacionVenta()
    {
        if ($this->tipoComprador === 'cliente' && !$this->clienteId) {
            $this->flashToast('error', 'Seleccione un cliente del gimnasio.');
            return;
        }
        if ($this->tipoComprador === 'empleado' && !$this->employeeId) {
            $this->flashToast('error', 'Seleccione un empleado.');
            return;
        }
        if ($this->tipoComprador === 'cliente_solo_venta') {
            $nombre = trim((string) $this->clienteSoloVentaNombre);
            $doc = trim((string) $this->clienteSoloVentaDocumento);
            if ($nombre === '' || $doc === '') {
                $this->flashToast('error', 'Ingrese nombre y documento del cliente solo venta.');
                return;
            }
        }
        if (!$this->paymentMethodId) {
            $this->flashToast('error', 'Seleccione un método de pago.');
            return;
        }
        $paymentMethod = PaymentMethod::find($this->paymentMethodId);
        if ($paymentMethod && $paymentMethod->requiere_numero_operacion && empty(trim((string) $this->numeroOperacion))) {
            $this->flashToast('error', 'Este método de pago requiere número de operación.');
            return;
        }
        if ($paymentMethod && $paymentMethod->requiere_entidad && empty(trim((string) $this->entidadFinanciera))) {
            $this->flashToast('error', 'Este método de pago requiere entidad financiera.');
            return;
        }
        if ($this->esCredito && ($this->tipoComprador === 'cliente' || $this->tipoComprador === 'empleado')) {
            if (empty($this->fechaVencimientoDeuda)) {
                $this->flashToast('error', 'Indique la fecha de vencimiento de la deuda.');
                return;
            }
        }
        $this->mostrarModalConfirmacionVenta = true;
    }

    /**
     * Cerrar modal Confirmación (volver al modal Procesar venta)
     */
    public function cerrarModalConfirmacionVenta()
    {
        $this->mostrarModalConfirmacionVenta = false;
    }

    /**
     * Búsqueda de clientes en modal Procesar venta (por nombre o documento)
     */
    public function updatedClienteSearchProcesar($value)
    {
        $term = trim((string) $value);
        if (strlen($term) < 2) {
            $this->clientesProcesar = collect([]);
            return;
        }
        $this->clientesProcesar = $this->clienteService->quickSearch($term, 15);
    }

    /**
     * Búsqueda de empleados en modal Procesar venta (por nombre o documento)
     */
    public function updatedEmployeeSearchProcesar($value)
    {
        $term = trim((string) $value);
        if (strlen($term) < 2) {
            $this->employeesProcesar = collect([]);
            return;
        }
        $this->employeesProcesar = \App\Models\Core\Employee::activos()
            ->where(function ($q) use ($term) {
                $q->where('nombres', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhere('documento', 'like', "%{$term}%")
                    ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ["%{$term}%"]);
            })
            ->orderBy('nombres')
            ->limit(15)
            ->get();
    }

    /**
     * Seleccionar cliente (desde resultados de búsqueda en modal Procesar venta)
     */
    public function seleccionarCliente($clienteId)
    {
        $this->clienteId = $clienteId;
        $this->clienteSeleccionado = \App\Models\Core\Cliente::find($clienteId);
        $this->clienteSearchProcesar = '';
        $this->clientesProcesar = collect([]);
        $this->mostrarModalCliente = false;
    }

    /**
     * Limpiar cliente
     */
    public function limpiarCliente()
    {
        $this->clienteId = null;
        $this->clienteSeleccionado = null;
        $this->clienteSearchProcesar = '';
        $this->clientesProcesar = collect([]);
    }

    /**
     * Seleccionar empleado (desde resultados de búsqueda en modal Procesar venta)
     */
    public function seleccionarEmpleado($empId)
    {
        $this->employeeId = $empId;
        $this->employeeSeleccionado = \App\Models\Core\Employee::find($empId);
        $this->employeeSearchProcesar = '';
        $this->employeesProcesar = collect([]);
    }

    /**
     * Limpiar empleado
     */
    public function limpiarEmpleado()
    {
        $this->employeeId = null;
        $this->employeeSeleccionado = null;
        $this->employeeSearchProcesar = '';
        $this->employeesProcesar = collect([]);
    }

    /**
     * Procesar venta (llamado desde modal Confirmación; ejecuta la venta y abre comprobante en nueva pestaña)
     */
    public function confirmarYProcesarVenta()
    {
        if (empty($this->carrito)) {
            $this->flashToast('error', 'El carrito está vacío.');
            return;
        }

        try {
            $items = [];
            foreach ($this->carrito as $item) {
                $items[] = [
                    'tipo' => $item['tipo'],
                    'id' => $item['id'],
                    'cantidad' => $item['cantidad'],
                    'descuento' => $item['descuento'] ?? 0,
                ];
            }

            $venta = $this->ventaService->procesarVenta([
                'tipo_comprador' => $this->tipoComprador,
                'cliente_id' => $this->tipoComprador === 'cliente' ? $this->clienteId : null,
                'employee_id' => $this->tipoComprador === 'empleado' ? $this->employeeId : null,
                'cliente_venta_nombre' => $this->tipoComprador === 'cliente_solo_venta' ? trim((string) $this->clienteSoloVentaNombre) : null,
                'cliente_venta_documento' => $this->tipoComprador === 'cliente_solo_venta' ? trim((string) $this->clienteSoloVentaDocumento) : null,
                'cliente_venta_telefono' => $this->tipoComprador === 'cliente_solo_venta' ? trim((string) $this->clienteSoloVentaTelefono) : null,
                'tipo_comprobante' => $this->tipoComprobante,
                'payment_method_id' => $this->paymentMethodId,
                'numero_operacion' => trim((string) $this->numeroOperacion) ?: null,
                'entidad_financiera' => trim((string) $this->entidadFinanciera) ?: null,
                'es_credito' => $this->esCredito && ($this->clienteId || $this->employeeId),
                'monto_inicial' => $this->esCredito ? (float) $this->montoInicial : 0,
                'fecha_vencimiento_deuda' => $this->esCredito && $this->fechaVencimientoDeuda ? $this->fechaVencimientoDeuda : null,
                'descuento' => $this->descuento,
                'discount_coupon_id' => $this->cuponAplicado,
                'monto_descuento_cupon' => (float) $this->montoDescuentoCupon,
                'observaciones' => $this->observaciones,
                'items' => $items,
            ]);

            $this->mostrarModalConfirmacionVenta = false;
            $this->mostrarModalProcesarVenta = false;
            $this->limpiarCarrito();
            $this->resetearDatosModalVenta();
            $this->ventaIdComprobante = $venta->id;
            $this->mostrarModalComprobante = true;
            $this->flashToast('success', 'Venta procesada exitosamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Resetear datos del modal de venta (tipo comprador, cliente, empleado, etc.)
     */
    protected function resetearDatosModalVenta()
    {
        $this->tipoComprador = 'cliente';
        $this->clienteId = null;
        $this->clienteSeleccionado = null;
        $this->clienteSearchProcesar = '';
        $this->clientesProcesar = collect([]);
        $this->employeeId = null;
        $this->employeeSeleccionado = null;
        $this->employeeSearchProcesar = '';
        $this->employeesProcesar = collect([]);
        $this->clienteSoloVentaNombre = '';
        $this->clienteSoloVentaDocumento = '';
        $this->clienteSoloVentaTelefono = '';
        $this->esCredito = false;
        $this->montoInicial = 0.0;
        $this->fechaVencimientoDeuda = '';
        $this->codigoCupon = '';
        $this->cuponAplicado = null;
        $this->montoDescuentoCupon = 0.0;
        $this->numeroOperacion = '';
        $this->entidadFinanciera = '';
    }

    /**
     * Limpiar carrito (ítems, descuento manual, observaciones)
     */
    public function limpiarCarrito()
    {
        $this->carrito = [];
        $this->descuento = 0;
        $this->observaciones = '';
    }

    public function aplicarCupon(): void
    {
        $this->cuponAplicado = null;
        $this->montoDescuentoCupon = 0.0;
        $codigo = strtoupper(trim((string) $this->codigoCupon));
        if (! $codigo) {
            $this->flashToast('error', 'Ingresa el código del cupón.');
            return;
        }
        $coupon = \App\Models\Core\DiscountCoupon::where('codigo', $codigo)->first();
        if (! $coupon) {
            $this->flashToast('error', 'Cupón no encontrado.');
            return;
        }
        if (! $coupon->puedeUsarse()) {
            $this->flashToast('error', 'El cupón no está vigente o ya alcanzó el límite de usos.');
            return;
        }
        if (! $coupon->aplicaA('pos')) {
            $this->flashToast('error', 'Este cupón no aplica para ventas en POS.');
            return;
        }
        $subtotalCarrito = collect($this->carrito)->sum(fn ($i) => ($i['precio'] * $i['cantidad']) - ($i['descuento'] ?? 0));
        $base = $subtotalCarrito - (float) $this->descuento;
        $monto = $coupon->calcularDescuento($base);
        $this->cuponAplicado = $coupon->id;
        $this->montoDescuentoCupon = $monto;
        $this->flashToast('success', 'Cupón aplicado: -S/ ' . number_format($monto, 2));
    }

    public function quitarCupon(): void
    {
        $this->codigoCupon = '';
        $this->cuponAplicado = null;
        $this->montoDescuentoCupon = 0.0;
    }

    /**
     * Cerrar modal de confirmación
     */
    public function cerrarModalConfirmacion()
    {
        $this->mostrarModalConfirmacion = false;
        $this->ventaProcesada = null;
    }

    /**
     * Cerrar modal del comprobante PDF
     */
    public function cerrarModalComprobante()
    {
        $this->mostrarModalComprobante = false;
        $this->ventaIdComprobante = null;
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
        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->cobroFormData['monto_pago'] = $this->saldoPendienteCobro;
        $this->cobroFormData['payment_method_id'] = $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id;
        $this->cobroFormData['numero_operacion'] = '';
        $this->cobroFormData['entidad_financiera'] = '';
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
            'payment_method_id' => null,
            'numero_operacion' => '',
            'entidad_financiera' => '',
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
            $pmId = $this->cobroFormData['payment_method_id'] ?? null;
            $paymentMethod = $pmId ? PaymentMethod::find($pmId) : null;
            if ($paymentMethod && $paymentMethod->requiere_numero_operacion && empty(trim((string) ($this->cobroFormData['numero_operacion'] ?? '')))) {
                $this->flashToast('error', 'Este método de pago requiere número de operación.');
                return;
            }
            if ($paymentMethod && $paymentMethod->requiere_entidad && empty(trim((string) ($this->cobroFormData['entidad_financiera'] ?? '')))) {
                $this->flashToast('error', 'Este método de pago requiere entidad financiera.');
                return;
            }
            $data = [
                'monto_pago' => (float) $this->cobroFormData['monto_pago'],
                'payment_method_id' => $pmId,
                'numero_operacion' => trim((string) ($this->cobroFormData['numero_operacion'] ?? '')) ?: null,
                'entidad_financiera' => trim((string) ($this->cobroFormData['entidad_financiera'] ?? '')) ?: null,
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

        $paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();
        $selectedPaymentMethod = $this->paymentMethodId ? PaymentMethod::find($this->paymentMethodId) : null;
        $cobroPaymentMethod = isset($this->cobroFormData['payment_method_id']) && $this->cobroFormData['payment_method_id']
            ? PaymentMethod::find($this->cobroFormData['payment_method_id']) : null;

        return view('livewire.p-o-s.p-o-s-live', [
            'categorias' => $categorias,
            'itemsPorCategoria' => $itemsPorCategoria,
            'clientesConDeuda' => $clientesConDeuda,
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'cobroPaymentMethod' => $cobroPaymentMethod,
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
