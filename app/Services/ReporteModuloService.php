<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Pago;
use App\Models\Core\Producto;
use App\Models\Core\ServicioExterno;
use App\Models\Core\Venta;
use App\Models\Core\VentaItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReporteModuloService
{
    /**
     * Datos para reporte de ventas (por período).
     */
    public function datosReporteVentas(?string $fechaDesde, ?string $fechaHasta): array
    {
        $query = Venta::with(['cliente', 'usuario', 'items']);
        if ($fechaDesde) {
            $query->where('fecha_venta', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->where('fecha_venta', '<=', $fechaHasta . ' 23:59:59');
        }
        $ventas = $query->orderBy('fecha_venta', 'desc')->get();

        $totalVentas = $ventas->sum('total');
        $totalSubtotal = $ventas->sum('subtotal');
        $totalDescuento = $ventas->sum('descuento');
        $totalIgv = $ventas->sum('igv');
        $porMetodo = $ventas->groupBy('metodo_pago')->map(fn ($g) => ['cantidad' => $g->count(), 'total' => $g->sum('total')]);
        $porEstado = $ventas->groupBy('estado')->map(fn ($g) => $g->count());
        $porTipoComprobante = $ventas->groupBy('tipo_comprobante')->map(fn ($g) => ['cantidad' => $g->count(), 'total' => $g->sum('total')]);

        return [
            'ventas' => $ventas,
            'resumen' => [
                'cantidad' => $ventas->count(),
                'total' => (float) $totalVentas,
                'subtotal' => (float) $totalSubtotal,
                'descuento_total' => (float) $totalDescuento,
                'igv_total' => (float) $totalIgv,
                'por_metodo_pago' => $porMetodo->toArray(),
                'por_estado' => $porEstado->toArray(),
                'por_tipo_comprobante' => $porTipoComprobante->toArray(),
            ],
        ];
    }

    /**
     * Datos para reporte de matrículas (membresías y clases).
     */
    public function datosReporteMatriculas(?string $fechaDesde, ?string $fechaHasta): array
    {
        $query = ClienteMatricula::with(['cliente', 'membresia', 'clase', 'asesor']);
        if ($fechaDesde) {
            $query->where('fecha_inicio', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->where('fecha_inicio', '<=', $fechaHasta);
        }
        $matriculas = $query->orderBy('fecha_inicio', 'desc')->get();

        $porTipo = $matriculas->groupBy('tipo')->map(fn ($g) => $g->count());
        $porEstado = $matriculas->groupBy('estado')->map(fn ($g) => $g->count());
        $porCanal = $matriculas->groupBy('canal_venta')->map(fn ($g) => ['cantidad' => $g->count(), 'ingresos' => $g->sum('precio_final')]);
        $ingresosMatriculas = (float) $matriculas->sum('precio_final');
        $descuentosTotal = (float) $matriculas->sum('descuento_monto');

        return [
            'matriculas' => $matriculas,
            'resumen' => [
                'cantidad' => $matriculas->count(),
                'membresias' => $porTipo->get('membresia', 0),
                'clases' => $porTipo->get('clase', 0),
                'ingresos' => $ingresosMatriculas,
                'descuentos_total' => $descuentosTotal,
                'por_estado' => $porEstado->toArray(),
                'por_canal_venta' => $porCanal->toArray(),
            ],
        ];
    }

    /**
     * Datos para reporte financiero (pagos, ingresos, resumen).
     */
    public function datosReporteFinanciero(?string $fechaDesde, ?string $fechaHasta): array
    {
        $pagosQuery = Pago::with(['cliente', 'caja', 'clienteMatricula', 'clienteMembresia']);
        if ($fechaDesde) {
            $pagosQuery->where('fecha_pago', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $pagosQuery->where('fecha_pago', '<=', $fechaHasta . ' 23:59:59');
        }
        $pagos = $pagosQuery->orderBy('fecha_pago', 'desc')->get();

        $ventasData = $this->datosReporteVentas($fechaDesde, $fechaHasta);
        $totalVentas = $ventasData['resumen']['total'];
        $totalPagos = (float) $pagos->sum('monto');

        return [
            'pagos' => $pagos,
            'ventas' => $ventasData['ventas'],
            'resumen' => [
                'total_pagos' => $totalPagos,
                'total_ventas' => $totalVentas,
                'ingresos_totales' => $totalPagos + $totalVentas,
                'cantidad_pagos' => $pagos->count(),
                'cantidad_ventas' => $ventasData['resumen']['cantidad'],
            ],
        ];
    }

    /**
     * Datos para reporte de clientes (activos, por estado, nuevos).
     * Filtros opcionales: estado, fecha de registro (created_at), created_by, trainer_user_id.
     */
    public function datosReporteClientes(
        ?string $estado = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        ?int $createdBy = null,
        ?int $trainerUserId = null
    ): array {
        $query = Cliente::withCount(['clienteMembresias', 'pagos']);
        if ($estado) {
            $query->where('estado_cliente', $estado);
        }
        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }
        if ($createdBy !== null) {
            $query->where('created_by', $createdBy);
        }
        if ($trainerUserId !== null) {
            $query->where('trainer_user_id', $trainerUserId);
        }
        $clientes = $query->orderBy('nombres')->get();

        $porEstado = $clientes->groupBy('estado_cliente')->map(fn ($g) => $g->count());

        $conPagos = $clientes->filter(fn ($c) => ($c->pagos_count ?? 0) > 0)->count();

        return [
            'clientes' => $clientes,
            'resumen' => [
                'total' => $clientes->count(),
                'por_estado' => $porEstado->toArray(),
                'con_membresias' => $clientes->filter(fn ($c) => ($c->cliente_membresias_count ?? 0) > 0)->count(),
                'con_pagos' => $conPagos,
            ],
        ];
    }

    /**
     * Reporte de clientes con membresía y clases activas, y pagos de membresía y clases.
     * Filtro opcional por período de fecha de pago.
     */
    public function datosReporteClientesMembresiaClasesActivas(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $hoy = now()->format('Y-m-d');

        // Membresías activas (tabla cliente_membresias)
        $membresiasActivas = ClienteMembresia::with(['cliente', 'membresia'])
            ->where('estado', 'activa')
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy);
            })
            ->orderBy('fecha_fin', 'desc')
            ->get();

        // Matrículas tipo membresía activas (tabla cliente_matriculas)
        $matriculasMembresiaActivas = ClienteMatricula::with(['cliente', 'membresia'])
            ->where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy);
            })
            ->orderBy('fecha_fin', 'desc')
            ->get();

        // Matrículas tipo clase activas
        $matriculasClaseActivas = ClienteMatricula::with(['cliente', 'clase'])
            ->where('tipo', 'clase')
            ->where('estado', 'activa')
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy);
            })
            ->orderBy('fecha_fin', 'desc')
            ->get();

        // Pagos de membresía (cliente_membresia_id)
        $pagosMembresiaQuery = Pago::with(['cliente', 'clienteMembresia.membresia']);
        if ($fechaDesde) {
            $pagosMembresiaQuery->whereDate('fecha_pago', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $pagosMembresiaQuery->whereDate('fecha_pago', '<=', $fechaHasta);
        }
        $pagosMembresia = $pagosMembresiaQuery->whereNotNull('cliente_membresia_id')->orderBy('fecha_pago', 'desc')->get();

        // Pagos de clases (cliente_matricula_id con tipo clase)
        $pagosClaseQuery = Pago::with(['cliente', 'clienteMatricula.clase']);
        if ($fechaDesde) {
            $pagosClaseQuery->whereDate('fecha_pago', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $pagosClaseQuery->whereDate('fecha_pago', '<=', $fechaHasta);
        }
        $pagosClase = $pagosClaseQuery->whereNotNull('cliente_matricula_id')
            ->whereHas('clienteMatricula', fn ($q) => $q->where('tipo', 'clase'))
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $totalPagosMembresia = (float) $pagosMembresia->sum('monto');
        $totalPagosClase = (float) $pagosClase->sum('monto');
        $clientesConMembresiaActiva = $membresiasActivas->pluck('cliente_id')->merge($matriculasMembresiaActivas->pluck('cliente_id'))->unique()->filter()->count();
        $clientesConClaseActiva = $matriculasClaseActivas->pluck('cliente_id')->unique()->filter()->count();

        return [
            'membresias_activas' => $membresiasActivas,
            'matriculas_membresia_activas' => $matriculasMembresiaActivas,
            'matriculas_clase_activas' => $matriculasClaseActivas,
            'pagos_membresia' => $pagosMembresia,
            'pagos_clase' => $pagosClase,
            'resumen' => [
                'cantidad_membresias_activas' => $membresiasActivas->count() + $matriculasMembresiaActivas->count(),
                'cantidad_clases_activas' => $matriculasClaseActivas->count(),
                'clientes_con_membresia_activa' => $clientesConMembresiaActiva,
                'clientes_con_clase_activa' => $clientesConClaseActiva,
                'total_pagos_membresia' => $totalPagosMembresia,
                'total_pagos_clase' => $totalPagosClase,
                'cantidad_pagos_membresia' => $pagosMembresia->count(),
                'cantidad_pagos_clase' => $pagosClase->count(),
            ],
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];
    }

    /**
     * Datos para reporte de usuarios (ventas por usuario, actividad).
     */
    public function datosReporteUsuarios(?string $fechaDesde, ?string $fechaHasta): array
    {
        $ventasQuery = Venta::query()
            ->select('usuario_id', DB::raw('COUNT(*) as cantidad'), DB::raw('SUM(total) as total_ventas'))
            ->groupBy('usuario_id');
        if ($fechaDesde) {
            $ventasQuery->where('fecha_venta', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $ventasQuery->where('fecha_venta', '<=', $fechaHasta . ' 23:59:59');
        }
        $porUsuario = $ventasQuery->get();

        $usuarios = User::whereIn('id', $porUsuario->pluck('usuario_id'))->get()->keyBy('id');
        $porUsuario = $porUsuario->map(function ($row) use ($usuarios) {
            $row->usuario = $usuarios->get($row->usuario_id);

            return $row;
        });

        return [
            'por_usuario' => $porUsuario,
            'resumen' => [
                'total_ventas' => (float) $porUsuario->sum('total_ventas'),
                'total_transacciones' => $porUsuario->sum('cantidad'),
            ],
        ];
    }

    /**
     * Datos para reporte de cajas (aperturas/cierres, movimientos).
     */
    public function datosReporteCajas(?string $fechaDesde, ?string $fechaHasta): array
    {
        $query = Caja::with(['usuario']);
        if ($fechaDesde) {
            $query->where('fecha_apertura', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $query->where('fecha_apertura', '<=', $fechaHasta . ' 23:59:59');
        }
        $cajas = $query->orderBy('fecha_apertura', 'desc')->get();

        $abiertas = $cajas->where('estado', 'abierta')->count();
        $cerradas = $cajas->where('estado', 'cerrada')->count();
        $totalIngresos = 0;
        $totalSalidas = 0;
        /** @var \App\Models\Core\Caja $caja */
        foreach ($cajas as $caja) {
            $totalIngresos += $caja->calcularTotalIngresos();
            $totalSalidas += $caja->calcularTotalSalidas();
        }

        return [
            'cajas' => $cajas,
            'resumen' => [
                'cantidad' => $cajas->count(),
                'abiertas' => $abiertas,
                'cerradas' => $cerradas,
                'total_ingresos' => (float) $totalIngresos,
                'total_salidas' => (float) $totalSalidas,
            ],
        ];
    }

    /**
     * Datos para reporte de productos y servicios (más vendidos, stock bajo).
     */
    public function datosReporteProductosServicios(?string $fechaDesde, ?string $fechaHasta): array
    {
        $ventasQuery = Venta::query();
        if ($fechaDesde) {
            $ventasQuery->where('fecha_venta', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $ventasQuery->where('fecha_venta', '<=', $fechaHasta . ' 23:59:59');
        }
        $ventaIds = $ventasQuery->pluck('id');

        $items = VentaItem::whereIn('venta_id', $ventaIds)
            ->select('tipo_item', 'item_id', 'nombre_item', DB::raw('SUM(cantidad) as cantidad_vendida'), DB::raw('SUM(subtotal) as total'))
            ->groupBy('tipo_item', 'item_id', 'nombre_item')
            ->orderByDesc('cantidad_vendida')
            ->get();

        $productosBajoStock = Producto::where('estado', 'activo')
            ->whereRaw('stock_actual <= stock_minimo')
            ->orderBy('stock_actual')
            ->get();

        return [
            'items_mas_vendidos' => $items,
            'productos_bajo_stock' => $productosBajoStock,
            'resumen' => [
                'total_productos_activos' => Producto::where('estado', 'activo')->count(),
                'total_servicios_activos' => ServicioExterno::where('estado', 'activo')->count(),
                'productos_bajo_stock' => $productosBajoStock->count(),
            ],
        ];
    }

    /**
     * Datos para reporte general del gimnasio (resumen ejecutivo).
     */
    public function datosReporteGimnasio(?string $fechaDesde, ?string $fechaHasta): array
    {
        $fechaDesde = $fechaDesde ?? now()->startOfMonth()->format('Y-m-d');
        $fechaHasta = $fechaHasta ?? now()->format('Y-m-d');

        $ventasData = $this->datosReporteVentas($fechaDesde, $fechaHasta);
        $matriculasData = $this->datosReporteMatriculas($fechaDesde, $fechaHasta);
        $financieroData = $this->datosReporteFinanciero($fechaDesde, $fechaHasta);
        $clientesData = $this->datosReporteClientes(null);

        $clientesActivos = Cliente::where('estado_cliente', 'activo')->count();
        $matriculasActivas = ClienteMatricula::where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $fechaHasta)
            ->where(function ($q) use ($fechaHasta) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $fechaHasta);
            })
            ->count();

        return [
            'resumen' => [
                'ventas_total' => $ventasData['resumen']['total'],
                'ventas_cantidad' => $ventasData['resumen']['cantidad'],
                'matriculas_nuevas' => $matriculasData['resumen']['cantidad'],
                'ingresos_matriculas' => $matriculasData['resumen']['ingresos'],
                'ingresos_totales' => $financieroData['resumen']['ingresos_totales'],
                'clientes_totales' => $clientesData['resumen']['total'],
                'clientes_activos' => $clientesActivos,
                'membresias_activas' => $matriculasActivas,
            ],
            'fecha_desde' => $fechaDesde,
            'fecha_hasta' => $fechaHasta,
        ];
    }
}
