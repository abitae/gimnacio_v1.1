<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\LogsLivewireErrors;
use App\Livewire\POS\Concerns\ManagesPosCartTotals;
use App\Models\Core\Cliente;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Producto;
use App\Models\Core\RentableSpace;
use App\Models\Core\ServicioExterno;
use App\Models\Core\VentaItem;
use App\Services\CajaService;
use App\Services\ClientDebtService;
use App\Services\ClienteMatriculaService;
use App\Services\ClienteMembresiaService;
use App\Services\ClienteService;
use App\Services\DailyOperationsDebtService;
use App\Services\EnrollmentInstallmentService;
use App\Services\Pos\PosCartService;
use App\Services\Pos\PosSaleOrchestrator;
use App\Services\PosAlquilerReservaService;
use App\Services\ProductoService;
use App\Services\ServicioExternoService;
use App\Services\SucursalContext;
use App\Services\VentaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

/**
 * POS — orquestador de venta diaria.
 *
 * Dominios: catálogo/búsqueda, carrito (PosCartService), confirmación (PosSaleOrchestrator),
 * cobro membresía/clase (DailyOperationsDebtService), modales comprador/pago.
 */
class POSLive extends Component
{
    use FlashesToast;
    use LogsLivewireErrors;
    use ManagesPosCartTotals;

    // Búsqueda
    public $busqueda = '';

    public $resultadosBusqueda = [];

    public $categoriaFiltro = '';

    public $tipoItem = 'producto'; // 'producto', 'servicio' o 'alquiler'

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

    private const CLIENTE_SOLO_VENTA_NOMBRE_DEFAULT = 'ninguno';

    private const CLIENTE_SOLO_VENTA_DOCUMENTO_DEFAULT = '00000000';

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

    public array $pagosVenta = [];

    public $codigoCupon = '';

    public $cuponAplicado = null;

    public $montoDescuentoCupon = 0.0;

    public $esCredito = false;

    public $montoInicial = 0.0;

    public $fechaVencimientoDeuda = '';

    public $alquilerFecha = '';

    public $alquilerHoraInicio = '09:00';

    public $alquilerHoraFin = '10:00';

    // Estado modales
    public $mostrarModalCliente = false;

    public $mostrarModalProcesarVenta = false;

    public int $modalProcesarVentaKey = 0;

    public bool $cajaAbierta = false;

    public $mostrarModalConfirmacion = false; // post-venta (legacy)

    public $ventaProcesada = null;

    /** ID de la venta para mostrar el PDF del comprobante en modal */
    public $ventaIdComprobante = null;

    public $mostrarModalComprobante = false;

    public bool $mostrarModalTicketPagoCobro = false;

    public ?int $pagoIdTicketCobro = null;

    // Modo Cobrar membresía/clase
    public $modoCobroMembresiaClase = false;

    public $clienteSearchCobro = '';

    public $clientesCobro;

    public $selectedClienteCobro = null;

    public $isSearchingCobro = false;

    public $itemsConSaldo = [];

    public $debtSummaryCobro = [];

    public $mostrarModalCobro = false;

    public $cobroItemTipo = null; // 'matricula' | 'membresia' (cuotas se cobran vía ruta cuotas.pagar)

    public $cobroItemId = null;

    public $saldoPendienteCobro = 0.00;

    public $cobroFormData = [
        'monto_pago' => 0.00,
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
    ];

    protected CajaService $cajaService;

    protected ProductoService $productoService;

    protected ServicioExternoService $servicioExternoService;

    protected VentaService $ventaService;

    protected ClienteService $clienteService;

    protected ClienteMatriculaService $clienteMatriculaService;

    protected ClienteMembresiaService $clienteMembresiaService;

    protected EnrollmentInstallmentService $enrollmentInstallmentService;

    protected DailyOperationsDebtService $dailyOperationsDebtService;

    protected ClientDebtService $clientDebtService;

    protected PosAlquilerReservaService $posAlquilerReservaService;

    protected PosSaleOrchestrator $posSaleOrchestrator;

    public function boot(
        CajaService $cajaService,
        ProductoService $productoService,
        ServicioExternoService $servicioExternoService,
        VentaService $ventaService,
        ClienteService $clienteService,
        ClienteMatriculaService $clienteMatriculaService,
        ClienteMembresiaService $clienteMembresiaService,
        EnrollmentInstallmentService $enrollmentInstallmentService,
        DailyOperationsDebtService $dailyOperationsDebtService,
        ClientDebtService $clientDebtService,
        PosAlquilerReservaService $posAlquilerReservaService,
        PosCartService $posCartService,
        PosSaleOrchestrator $posSaleOrchestrator,
    ) {
        $this->cajaService = $cajaService;
        $this->productoService = $productoService;
        $this->servicioExternoService = $servicioExternoService;
        $this->ventaService = $ventaService;
        $this->clienteService = $clienteService;
        $this->clienteMatriculaService = $clienteMatriculaService;
        $this->clienteMembresiaService = $clienteMembresiaService;
        $this->enrollmentInstallmentService = $enrollmentInstallmentService;
        $this->dailyOperationsDebtService = $dailyOperationsDebtService;
        $this->clientDebtService = $clientDebtService;
        $this->posAlquilerReservaService = $posAlquilerReservaService;
        $this->posCartService = $posCartService;
        $this->posSaleOrchestrator = $posSaleOrchestrator;
    }

    public function mount()
    {
        $this->authorize('punto_venta.ver');
        $this->clientesCobro = collect([]);
        $this->clientesProcesar = collect([]);
        $this->employeesProcesar = collect([]);
        $this->refrescarEstadoCaja();
        $this->alquilerFecha = now()->format('Y-m-d');
        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->paymentMethodId = $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id;
        $this->resetPagosVenta();
        // Validar que haya caja abierta
        if (! $this->cajaService->validarCajaAbierta(Auth::id())) {
            $this->flashToast('error', 'No hay una caja abierta. Por favor, abra una caja antes de usar el punto de venta.');
        }

        $cobrarClienteId = (int) request()->query('cobrar_cliente', 0);
        if ($cobrarClienteId > 0 && Cliente::query()->whereKey($cobrarClienteId)->exists()) {
            $this->irACobrarCliente($cobrarClienteId);
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

    public function updatedPaymentMethodId($value): void
    {
        if (isset($this->pagosVenta[0]) && count($this->pagosVenta) === 1) {
            $this->pagosVenta[0]['payment_method_id'] = $value ? (int) $value : null;
        }
    }

    public function updatedEsCredito(): void
    {
        if ($this->esCredito && isset($this->pagosVenta[0])) {
            $this->montoInicial = 0;
            $this->pagosVenta[0]['monto'] = 0;
        }

        $this->syncMontoPagoPrincipal();
    }

    public function agregarPagoVenta(): void
    {
        $this->pagosVenta[] = [
            'payment_method_id' => $this->paymentMethodId,
            'monto' => 0,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
    }

    public function quitarPagoVenta(int $index): void
    {
        unset($this->pagosVenta[$index]);
        $this->pagosVenta = array_values($this->pagosVenta);
        if ($this->pagosVenta === []) {
            $this->agregarPagoVenta();
        }
    }

    protected function resetPagosVenta(): void
    {
        $this->pagosVenta = [[
            'payment_method_id' => $this->paymentMethodId,
            'monto' => 0,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ]];
    }

    protected function syncMontoPagoPrincipal(): void
    {
        if (! isset($this->pagosVenta[0])) {
            $this->resetPagosVenta();
        }

        if (! $this->esCredito) {
            $this->pagosVenta[0]['monto'] = $this->total;
            $this->montoInicial = $this->total;
        }
    }

    /**
     * Recalcula totales del carrito (computed) y sincroniza montos de pago.
     */
    protected function calcularTotales(): void
    {
        $this->syncMontoPagoPrincipal();
    }

    protected function refrescarEstadoCaja(): void
    {
        $this->cajaAbierta = $this->cajaService->validarCajaAbierta(auth()->id());
    }

    protected function aplicarTipoComprador(string $tab): void
    {
        if (! in_array($tab, ['cliente', 'empleado', 'cliente_solo_venta'], true)) {
            $tab = 'cliente';
        }

        $this->tipoComprador = $tab;

        if ($tab === 'cliente_solo_venta') {
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
        } elseif ($this->tipoItem === 'servicio') {
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
        } else {
            $term = '%'.$this->busqueda.'%';
            $espacios = RentableSpace::activos()
                ->with('rates')
                ->where(function ($q) use ($term) {
                    $q->where('nombre', 'like', $term)
                        ->orWhere('descripcion', 'like', $term);
                })
                ->orderBy('nombre')
                ->limit(40)
                ->get()
                ->filter(fn (RentableSpace $espacio) => $espacio->tienePrecioPos());

            foreach ($espacios as $espacio) {
                $this->resultadosBusqueda[] = $this->itemAlquilerDesdeEspacio($espacio);
            }
        }
    }

    protected function itemAlquilerDesdeEspacio(RentableSpace $espacio): array
    {
        return [
            'tipo' => 'alquiler',
            'id' => $espacio->id,
            'codigo' => 'ESP-'.$espacio->id,
            'nombre' => $espacio->nombre,
            'precio' => $espacio->precioPos(),
            'capacidad' => $espacio->capacidad,
        ];
    }

    public function obtenerEspaciosParaPOS()
    {
        $espacios = RentableSpace::activos()
            ->with('rates')
            ->orderBy('nombre')
            ->get()
            ->filter(fn (RentableSpace $espacio) => $espacio->tienePrecioPos())
            ->map(function (RentableSpace $espacio) {
                $espacio->setAttribute('precio_pos', $espacio->precioPos());

                return $espacio;
            });

        if ($espacios->isEmpty()) {
            return collect();
        }

        return collect(['Espacios' => $espacios]);
    }

    protected function obtenerEspaciosParaPOSCached(int $sucursalId, int $catalogTtl): \Illuminate\Support\Collection
    {
        return Cache::remember(
            "pos.catalog.espacios.{$sucursalId}",
            $catalogTtl,
            fn () => $this->obtenerEspaciosParaPOS()
        );
    }

    protected function obtenerProductosPorCategoriaCached(int $sucursalId, int $catalogTtl): \Illuminate\Support\Collection
    {
        $filtro = $this->categoriaFiltro ?: 'all';

        return Cache::remember(
            "pos.catalog.productos.{$sucursalId}.{$filtro}",
            $catalogTtl,
            fn () => $this->obtenerProductosPorCategoria()
        );
    }

    protected function obtenerProductosMasVendidos(int $sucursalId): \Illuminate\Support\Collection
    {
        return Cache::remember("pos.top_productos.{$sucursalId}", 300, function () use ($sucursalId) {
            return VentaItem::query()
                ->select('item_id', DB::raw('SUM(cantidad) as cantidad_vendida'))
                ->where('tipo_item', 'producto')
                ->where('sucursal_id', $sucursalId)
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('item_id')
                ->orderByDesc('cantidad_vendida')
                ->limit(8)
                ->get()
                ->map(function (VentaItem $row) {
                    $producto = Producto::query()
                        ->where('estado', 'activo')
                        ->where('stock_actual', '>', 0)
                        ->find($row->item_id);

                    if (! $producto) {
                        return null;
                    }

                    $producto->setAttribute('cantidad_vendida', (int) $row->cantidad_vendida);

                    return $producto;
                })
                ->filter()
                ->values();
        });
    }

    protected function obtenerServiciosPorCategoriaCached(int $sucursalId, int $catalogTtl): \Illuminate\Support\Collection
    {
        $filtro = $this->categoriaFiltro ?: 'all';

        return Cache::remember(
            "pos.catalog.servicios.{$sucursalId}.{$filtro}",
            $catalogTtl,
            fn () => $this->obtenerServiciosPorCategoria()
        );
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
        $this->agregarItemAlCarrito(is_array($item) ? $item : []);
    }

    public function agregarAlCarritoPorTipo(string $tipo, int $id): void
    {
        $item = match ($tipo) {
            'producto' => $this->itemProductoParaCarrito($id),
            'servicio' => $this->itemServicioParaCarrito($id),
            'alquiler' => $this->itemAlquilerParaCarrito($id),
            default => null,
        };

        if ($item === null) {
            $this->flashToast('error', 'Ítem no encontrado o no disponible.');

            return;
        }

        $this->agregarItemAlCarrito($item);
    }

    protected function agregarItemAlCarrito(array $item): void
    {
        $key = ($item['tipo'] ?? '').'-'.($item['id'] ?? '');

        if ($key === '-' || ! isset($item['tipo'], $item['id'])) {
            return;
        }

        if (isset($this->carrito[$key])) {
            $this->carrito[$key]['cantidad']++;
        } else {
            $this->carrito[$key] = [
                'tipo' => $item['tipo'],
                'id' => $item['id'],
                'codigo' => $item['codigo'] ?? '',
                'nombre' => $item['nombre'] ?? '',
                'precio' => (float) ($item['precio'] ?? 0),
                'cantidad' => 1,
                'descuento' => 0,
            ];
        }

        $this->calcularTotales();
        $this->busqueda = '';
        $this->resultadosBusqueda = [];
    }

    protected function itemProductoParaCarrito(int $id): ?array
    {
        $producto = Producto::query()
            ->where('estado', 'activo')
            ->where('stock_actual', '>', 0)
            ->find($id);

        if (! $producto) {
            return null;
        }

        return [
            'tipo' => 'producto',
            'id' => $producto->id,
            'codigo' => $producto->codigo,
            'nombre' => $producto->nombre,
            'precio' => (float) $producto->precio_venta,
        ];
    }

    protected function itemServicioParaCarrito(int $id): ?array
    {
        $servicio = ServicioExterno::query()->where('estado', 'activo')->find($id);

        if (! $servicio) {
            return null;
        }

        return [
            'tipo' => 'servicio',
            'id' => $servicio->id,
            'codigo' => $servicio->codigo,
            'nombre' => $servicio->nombre,
            'precio' => (float) $servicio->precio,
        ];
    }

    protected function itemAlquilerParaCarrito(int $id): ?array
    {
        $espacio = RentableSpace::activos()->with('rates')->find($id);

        if (! $espacio || ! $espacio->tienePrecioPos()) {
            return null;
        }

        return $this->itemAlquilerDesdeEspacio($espacio);
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
                if ($producto && ! $producto->tieneStockSuficiente($cantidad)) {
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
     * Abrir modal Procesar venta (solo si hay ítems en el carrito)
     */
    public function abrirModalProcesarVenta()
    {
        if (empty($this->carrito)) {
            $this->flashToast('error', 'El carrito está vacío.');

            return;
        }
        $this->syncMontoPagoPrincipal();
        $this->modalProcesarVentaKey++;
        $this->mostrarModalProcesarVenta = true;
    }

    /**
     * Cerrar modal Procesar venta y opcionalmente volver a estado inicial
     */
    public function cerrarModalProcesarVenta()
    {
        $this->mostrarModalProcesarVenta = false;
    }

    protected function validarDatosProcesarVenta(): bool
    {
        if ($this->carritoTieneAlquiler) {
            if ($this->esCredito) {
                $this->flashToast('error', 'No se puede vender alquileres a crédito desde el POS.');

                return false;
            }
            if (empty($this->alquilerFecha) || empty($this->alquilerHoraInicio) || empty($this->alquilerHoraFin)) {
                $this->flashToast('error', 'Indique fecha y horario del alquiler.');

                return false;
            }
            if ($this->alquilerHoraFin <= $this->alquilerHoraInicio) {
                $this->flashToast('error', 'La hora de fin debe ser posterior a la hora de inicio.');

                return false;
            }
        }

        if ($this->tipoComprador === 'cliente' && ! $this->clienteId) {
            $this->flashToast('error', 'Seleccione un cliente del gimnasio.');

            return false;
        }
        if ($this->tipoComprador === 'empleado' && ! $this->employeeId) {
            $this->flashToast('error', 'Seleccione un empleado.');

            return false;
        }
        if (! $this->paymentMethodId) {
            $this->flashToast('error', 'Seleccione un método de pago.');

            return false;
        }
        $paymentMethod = PaymentMethod::find($this->paymentMethodId);
        if ($paymentMethod && $paymentMethod->requiere_numero_operacion && empty(trim((string) $this->numeroOperacion))) {
            $this->flashToast('error', 'Este método de pago requiere número de operación.');

            return false;
        }
        if ($paymentMethod && $paymentMethod->requiere_entidad && empty(trim((string) $this->entidadFinanciera))) {
            $this->flashToast('error', 'Este método de pago requiere entidad financiera.');

            return false;
        }
        if ($this->esCredito && ($this->tipoComprador === 'cliente' || $this->tipoComprador === 'empleado')) {
            if (empty($this->fechaVencimientoDeuda)) {
                $this->flashToast('error', 'Indique la fecha de vencimiento de la deuda.');

                return false;
            }
        }

        return true;
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
    protected function validarDatosProcesarVentaConPagos(): bool
    {
        if ($this->carritoTieneAlquiler) {
            if ($this->esCredito) {
                $this->flashToast('error', 'No se puede vender alquileres a credito desde el POS.');

                return false;
            }
            if (empty($this->alquilerFecha) || empty($this->alquilerHoraInicio) || empty($this->alquilerHoraFin)) {
                $this->flashToast('error', 'Indique fecha y horario del alquiler.');

                return false;
            }
            if ($this->alquilerHoraFin <= $this->alquilerHoraInicio) {
                $this->flashToast('error', 'La hora de fin debe ser posterior a la hora de inicio.');

                return false;
            }
        }

        if ($this->tipoComprador === 'cliente' && ! $this->clienteId) {
            $this->flashToast('error', 'Seleccione un cliente del gimnasio.');

            return false;
        }
        if ($this->tipoComprador === 'empleado' && ! $this->employeeId) {
            $this->flashToast('error', 'Seleccione un empleado.');

            return false;
        }

        $pagosValidos = collect($this->pagosVenta)
            ->map(fn ($pago) => [
                'payment_method_id' => $pago['payment_method_id'] ?? null,
                'monto' => round((float) ($pago['monto'] ?? 0), 2),
                'numero_operacion' => trim((string) ($pago['numero_operacion'] ?? '')),
                'entidad_financiera' => trim((string) ($pago['entidad_financiera'] ?? '')),
            ])
            ->filter(fn ($pago) => $pago['monto'] > 0)
            ->values();

        if ($pagosValidos->isEmpty() && ! $this->esCredito) {
            $this->flashToast('error', 'Registre al menos un pago para ventas al contado.');

            return false;
        }

        foreach ($pagosValidos as $pago) {
            if (! $pago['payment_method_id']) {
                $this->flashToast('error', 'Seleccione un metodo de pago en cada linea.');

                return false;
            }

            $paymentMethod = PaymentMethod::find($pago['payment_method_id']);
            if ($paymentMethod && $paymentMethod->requiere_numero_operacion && $pago['numero_operacion'] === '') {
                $this->flashToast('error', "El metodo {$paymentMethod->nombre} requiere numero de operacion.");

                return false;
            }
            if ($paymentMethod && $paymentMethod->requiere_entidad && $pago['entidad_financiera'] === '') {
                $this->flashToast('error', "El metodo {$paymentMethod->nombre} requiere entidad financiera.");

                return false;
            }
        }

        $totalPagos = round((float) $pagosValidos->sum('monto'), 2);
        $totalVenta = round($this->total, 2);
        if (! $this->esCredito && abs($totalPagos - $totalVenta) > 0.009) {
            $this->flashToast('error', 'La suma de pagos debe ser igual al total de la venta.');

            return false;
        }
        if ($this->esCredito && $totalPagos > $totalVenta) {
            $this->flashToast('error', 'La suma de pagos no puede superar el total de la venta a credito.');

            return false;
        }

        if ($this->esCredito && ($this->tipoComprador === 'cliente' || $this->tipoComprador === 'empleado')) {
            if (empty($this->fechaVencimientoDeuda)) {
                $this->flashToast('error', 'Indique la fecha de vencimiento de la deuda.');

                return false;
            }
        }

        return true;
    }

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
     * Procesar venta desde el modal Procesar venta (ticket + reservas de alquiler si aplica).
     */
    public function procesarVenta(?string $compradorTab = null)
    {
        if ($compradorTab !== null) {
            $this->aplicarTipoComprador($compradorTab);
        }

        if (empty($this->carrito)) {
            $this->flashToast('error', 'El carrito está vacío.');

            return;
        }

        if (! $this->validarDatosProcesarVentaConPagos()) {
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

            $carritoSnapshot = $this->carrito;
            $tieneAlquiler = $this->carritoTieneAlquiler;

            $venta = $this->posSaleOrchestrator->completeSale(
                [
                    'tipo_comprador' => $this->tipoComprador,
                    'cliente_id' => $this->tipoComprador === 'cliente' ? $this->clienteId : null,
                    'employee_id' => $this->tipoComprador === 'empleado' ? $this->employeeId : null,
                    'cliente_venta_nombre' => $this->tipoComprador === 'cliente_solo_venta' ? $this->clienteSoloVentaNombreParaVenta() : null,
                    'cliente_venta_documento' => $this->tipoComprador === 'cliente_solo_venta' ? $this->clienteSoloVentaDocumentoParaVenta() : null,
                    'cliente_venta_telefono' => $this->tipoComprador === 'cliente_solo_venta' ? trim((string) $this->clienteSoloVentaTelefono) : null,
                    'tipo_comprobante' => 'ticket',
                    'payment_method_id' => $this->paymentMethodId,
                    'numero_operacion' => trim((string) $this->numeroOperacion) ?: null,
                    'entidad_financiera' => trim((string) $this->entidadFinanciera) ?: null,
                    'pagos' => $this->pagosVenta,
                    'es_credito' => $this->esCredito && ($this->clienteId || $this->employeeId),
                    'monto_inicial' => $this->esCredito ? (float) collect($this->pagosVenta)->sum('monto') : 0,
                    'fecha_vencimiento_deuda' => $this->esCredito && $this->fechaVencimientoDeuda ? $this->fechaVencimientoDeuda : null,
                    'descuento' => $this->descuento,
                    'discount_coupon_id' => $this->cuponAplicado,
                    'monto_descuento_cupon' => (float) $this->montoDescuentoCupon,
                    'observaciones' => $this->observaciones,
                    'items' => $items,
                ],
                $carritoSnapshot,
                $tieneAlquiler,
                [
                    'fecha' => $this->alquilerFecha,
                    'hora_inicio' => $this->alquilerHoraInicio,
                    'hora_fin' => $this->alquilerHoraFin,
                ]
            );

            $this->mostrarModalProcesarVenta = false;
            $this->limpiarCarrito();
            $this->resetearDatosModalVenta();
            $this->ventaIdComprobante = $venta->id;
            $this->mostrarModalComprobante = true;
            $this->refrescarEstadoCaja();
            $this->flashToast('success', 'Venta procesada exitosamente.');
        } catch (\Exception $e) {
            $this->reportLivewireError($e, 'Error al procesar venta en POS.');
        }
    }

    public function clienteSoloVentaNombreParaVenta(): string
    {
        $nombre = trim((string) $this->clienteSoloVentaNombre);

        return $nombre !== '' ? $nombre : self::CLIENTE_SOLO_VENTA_NOMBRE_DEFAULT;
    }

    public function clienteSoloVentaDocumentoParaVenta(): string
    {
        $documento = trim((string) $this->clienteSoloVentaDocumento);

        return $documento !== '' ? $documento : self::CLIENTE_SOLO_VENTA_DOCUMENTO_DEFAULT;
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
        $this->resetPagosVenta();
        $this->fechaVencimientoDeuda = '';
        $this->codigoCupon = '';
        $this->cuponAplicado = null;
        $this->montoDescuentoCupon = 0.0;
        $this->numeroOperacion = '';
        $this->entidadFinanciera = '';
        $this->tipoComprobante = 'ticket';
        $this->alquilerFecha = now()->format('Y-m-d');
        $this->alquilerHoraInicio = '09:00';
        $this->alquilerHoraFin = '10:00';
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

        try {
            $result = $this->posCartService->resolveCouponDiscount(
                (string) $this->codigoCupon,
                $this->carrito,
                (float) $this->descuento
            );
            $this->cuponAplicado = $result['coupon_id'];
            $this->montoDescuentoCupon = $result['monto'];
            $this->flashToast('success', 'Cupón aplicado: -S/ '.number_format($result['monto'], 2));
        } catch (\InvalidArgumentException $e) {
            $this->reportLivewireError($e, 'Error al aplicar cupón en POS.');
        }
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

    public function cerrarModalTicketPagoCobro(): void
    {
        $this->mostrarModalTicketPagoCobro = false;
        $this->pagoIdTicketCobro = null;
    }

    // --- Cobrar membresía/clase ---

    public function activarModoCobroMembresiaClase()
    {
        $this->modoCobroMembresiaClase = true;
        $this->selectedClienteCobro = null;
        $this->clienteSearchCobro = '';
        $this->clientesCobro = collect([]);
        $this->itemsConSaldo = [];
        $this->debtSummaryCobro = [];
    }

    public function desactivarModoCobroMembresiaClase()
    {
        $this->modoCobroMembresiaClase = false;
        $this->selectedClienteCobro = null;
        $this->clienteSearchCobro = '';
        $this->clientesCobro = collect([]);
        $this->itemsConSaldo = [];
        $this->debtSummaryCobro = [];
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
        if (! $this->selectedClienteCobro) {
            return;
        }
        $this->clienteSearchCobro = $this->selectedClienteCobro->nombres.' '.$this->selectedClienteCobro->apellidos;
        $this->clientesCobro = collect([]);
        $this->cargarItemsConSaldo();
    }

    public function clearClienteCobro()
    {
        $this->selectedClienteCobro = null;
        $this->clienteSearchCobro = '';
        $this->clientesCobro = collect([]);
        $this->itemsConSaldo = [];
        $this->debtSummaryCobro = [];
    }

    public function cargarItemsConSaldo()
    {
        $this->itemsConSaldo = [];
        $this->debtSummaryCobro = [];
        if (! $this->selectedClienteCobro) {
            return;
        }
        $summary = $this->dailyOperationsDebtService->summarizeCliente($this->selectedClienteCobro->id);
        $this->debtSummaryCobro = $summary;
        $this->itemsConSaldo = $summary['items']->all();
    }

    public function openCobroModal(string $tipo, int $id)
    {
        if (! in_array($tipo, ['matricula', 'membresia', 'client_debt'], true)) {
            return;
        }
        $this->cobroItemTipo = $tipo;
        $this->cobroItemId = $id;
        if ($tipo === 'matricula') {
            $this->saldoPendienteCobro = $this->clienteMatriculaService->obtenerSaldoPendiente($id);
        } elseif ($tipo === 'membresia') {
            $this->saldoPendienteCobro = $this->clienteMembresiaService->obtenerSaldoPendiente($id);
        } else {
            $this->saldoPendienteCobro = (float) (\App\Models\Core\ClientDebt::find($id)?->saldo_pendiente ?? 0);
        }
        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->cobroFormData['monto_pago'] = $this->saldoPendienteCobro;
        $this->cobroFormData['payment_method_id'] = $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id;
        $this->cobroFormData['numero_operacion'] = '';
        $this->cobroFormData['entidad_financiera'] = '';
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
        ];
        $this->cargarItemsConSaldo();
    }

    public function procesarCobro()
    {
        try {
            if (! $this->cobroItemId || ! $this->cobroItemTipo) {
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
                'fecha_pago' => now(),
                'payment_method_id' => $pmId,
                'numero_operacion' => trim((string) ($this->cobroFormData['numero_operacion'] ?? '')) ?: null,
                'entidad_financiera' => trim((string) ($this->cobroFormData['entidad_financiera'] ?? '')) ?: null,
            ];
            $pago = match ($this->cobroItemTipo) {
                'matricula' => $this->clienteMatriculaService->procesarPago($this->cobroItemId, $data),
                'membresia' => $this->clienteMembresiaService->procesarPago($this->cobroItemId, $data),
                'client_debt' => $this->clientDebtService->procesarPago($this->cobroItemId, $data),
                default => throw new \InvalidArgumentException('Tipo de cobro no soportado.'),
            };
            $this->flashToast('success', 'Cobro registrado correctamente. El pago se ha reportado a la caja abierta.');
            $this->cerrarModalCobro();
            $this->pagoIdTicketCobro = $pago->id;
            $this->mostrarModalTicketPagoCobro = true;
        } catch (\Exception $e) {
            $this->reportLivewireError($e, 'Error al procesar venta en POS.');
        }
    }

    public function render()
    {
        $sucursalId = app(SucursalContext::class)->getSucursalId() ?? 0;
        $catalogTtl = 120;
        $deudaTtl = 25;

        // Obtener categorías según el tipo seleccionado (caché corta por sucursal)
        if ($this->tipoItem === 'producto') {
            $categorias = Cache::remember(
                "pos.categorias_producto.{$sucursalId}",
                $catalogTtl,
                fn () => \App\Models\Core\CategoriaProducto::where('estado', 'activa')
                    ->orderBy('nombre')
                    ->get()
            );
            $itemsPorCategoria = $this->obtenerProductosPorCategoriaCached($sucursalId, $catalogTtl);
        } elseif ($this->tipoItem === 'servicio') {
            $categorias = Cache::remember(
                "pos.categorias_servicio.{$sucursalId}",
                $catalogTtl,
                fn () => \App\Models\Core\CategoriaServicio::where('estado', 'activa')
                    ->orderBy('nombre')
                    ->get()
            );
            $itemsPorCategoria = $this->obtenerServiciosPorCategoriaCached($sucursalId, $catalogTtl);
        } else {
            $categorias = collect();
            $itemsPorCategoria = $this->obtenerEspaciosParaPOSCached($sucursalId, $catalogTtl);
        }

        $clientesConDeuda = $this->modoCobroMembresiaClase
            ? Cache::remember(
                "pos.clientes_con_deuda.{$sucursalId}",
                $deudaTtl,
                fn () => $this->dailyOperationsDebtService->clientesConDeuda(100)
            )
            : collect();

        $paymentMethods = Cache::remember(
            "pos.payment_methods.{$sucursalId}",
            $catalogTtl,
            fn () => PaymentMethod::activos()->orderBy('nombre')->get()
        );
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
            'productosMasVendidos' => $this->tipoItem === 'producto' ? $this->obtenerProductosMasVendidos($sucursalId) : collect(),
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
