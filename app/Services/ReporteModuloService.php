<?php

namespace App\Services;

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Asistencia;
use App\Models\Core\Cita;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClientePlanTraspaso;
use App\Models\Core\Pago;
use App\Models\Core\Producto;
use App\Models\Core\ServicioExterno;
use App\Models\Core\Venta;
use App\Models\Core\VentaItem;
use App\Models\User;
use Carbon\Carbon;
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
        ?int $trainerUserId = null,
        ?string $vigencia = null,
        int $ventanaDias = 15
    ): array {
        $query = Cliente::with(['registroPor', 'trainerUser'])->withCount(['clienteMembresias', 'pagos']);
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
        $clienteIds = $clientes->pluck('id');

        $hoy = Carbon::today();
        $ventanaLimite = $hoy->copy()->addDays(max($ventanaDias, 1));
        $rangoDesde = $fechaDesde ? Carbon::parse($fechaDesde)->startOfDay() : null;
        $rangoHasta = $fechaHasta ? Carbon::parse($fechaHasta)->endOfDay() : null;

        $membresiasLegacy = ClienteMembresia::query()
            ->with('membresia')
            ->whereIn('cliente_id', $clienteIds)
            ->orderBy('fecha_inicio')
            ->get()
            ->groupBy('cliente_id');

        $matriculas = ClienteMatricula::query()
            ->with(['membresia', 'clase'])
            ->whereIn('cliente_id', $clienteIds)
            ->orderBy('fecha_inicio')
            ->get()
            ->groupBy('cliente_id');

        $asistenciasPorCliente = Asistencia::query()
            ->select('cliente_id', DB::raw('COUNT(*) as total'))
            ->whereIn('cliente_id', $clienteIds)
            ->when($rangoDesde, fn ($query) => $query->where('fecha_hora_ingreso', '>=', $rangoDesde))
            ->when($rangoHasta, fn ($query) => $query->where('fecha_hora_ingreso', '<=', $rangoHasta))
            ->groupBy('cliente_id')
            ->pluck('total', 'cliente_id');

        $inasistenciasPorCliente = Cita::query()
            ->select('cliente_id', DB::raw('COUNT(*) as total'))
            ->whereIn('cliente_id', $clienteIds)
            ->where('estado', 'no_asistio')
            ->when($rangoDesde, fn ($query) => $query->where('fecha_hora', '>=', $rangoDesde))
            ->when($rangoHasta, fn ($query) => $query->where('fecha_hora', '<=', $rangoHasta))
            ->groupBy('cliente_id')
            ->pluck('total', 'cliente_id');

        $traspasosPorCliente = ClientePlanTraspaso::query()
            ->select('cliente_id', DB::raw('COUNT(*) as total'))
            ->whereIn('cliente_id', $clienteIds)
            ->when($rangoDesde, fn ($query) => $query->where('created_at', '>=', $rangoDesde))
            ->when($rangoHasta, fn ($query) => $query->where('created_at', '<=', $rangoHasta))
            ->groupBy('cliente_id')
            ->pluck('total', 'cliente_id');

        $membresiasPorIniciar = 0;

        $clientes = $clientes->map(function (Cliente $cliente) use (
            $membresiasLegacy,
            $matriculas,
            $hoy,
            $ventanaLimite,
            $asistenciasPorCliente,
            $inasistenciasPorCliente,
            $traspasosPorCliente,
            &$membresiasPorIniciar
        ) {
            $matriculasCliente = $matriculas->get($cliente->id, collect());
            $membresiasLegacyCliente = $membresiasLegacy->get($cliente->id, collect());

            $enrollments = collect()
                ->concat($matriculasCliente->map(function ($item) {
                    return [
                        'tipo_fuente' => 'cliente_matricula',
                        'categoria' => $item->tipo,
                        'nombre' => $item->nombre,
                        'fecha_matricula' => $item->fecha_matricula,
                        'fecha_inicio' => $item->fecha_inicio,
                        'fecha_fin' => $item->fecha_fin,
                        'estado' => $item->estado,
                    ];
                }))
                ->concat(
                    $matriculasCliente->where('tipo', 'membresia')->isEmpty()
                        ? $membresiasLegacyCliente->map(function ($item) {
                            return [
                                'tipo_fuente' => 'cliente_membresia',
                                'categoria' => 'membresia',
                                'nombre' => $item->membresia?->nombre ?? 'Membresía',
                                'fecha_matricula' => $item->fecha_matricula,
                                'fecha_inicio' => $item->fecha_inicio,
                                'fecha_fin' => $item->fecha_fin,
                                'estado' => $item->estado,
                            ];
                        })
                        : collect()
                )
                ->sortBy([
                    ['fecha_inicio', 'desc'],
                    ['fecha_matricula', 'desc'],
                ])
                ->values();

            $activos = $enrollments->filter(function (array $item) use ($hoy) {
                return $item['estado'] === 'activa'
                    && $item['fecha_inicio']
                    && $item['fecha_inicio']->lte($hoy)
                    && ($item['fecha_fin'] === null || $item['fecha_fin']->gte($hoy));
            });

            $proximosVencer = $activos
                ->filter(fn (array $item) => $item['fecha_fin'] !== null && $item['fecha_fin']->betweenIncluded($hoy, $ventanaLimite))
                ->sortBy('fecha_fin')
                ->values();

            $porIniciar = $enrollments
                ->filter(fn (array $item) => $item['categoria'] === 'membresia'
                    && in_array($item['estado'], ['activa', 'congelada'], true)
                    && $item['fecha_inicio'] !== null
                    && $item['fecha_inicio']->gt($hoy))
                ->sortBy('fecha_inicio')
                ->values();

            $membresiasPorIniciar += $porIniciar->count();

            $planActual = $activos->first() ?? $enrollments->first();
            $primerInicio = $porIniciar->first();
            $primerVencimiento = $proximosVencer->first();

            $cliente->setAttribute('plan_actual', $planActual['nombre'] ?? null);
            $cliente->setAttribute('plan_actual_tipo', $planActual['categoria'] ?? null);
            $cliente->setAttribute('plan_actual_estado', $planActual['estado'] ?? null);
            $cliente->setAttribute('fecha_matricula_actual', $planActual['fecha_matricula'] ?? null);
            $cliente->setAttribute('fecha_inicio_actual', $planActual['fecha_inicio'] ?? null);
            $cliente->setAttribute('fecha_fin_actual', $planActual['fecha_fin'] ?? null);
            $cliente->setAttribute('tiene_plan_activo', $activos->isNotEmpty());
            $cliente->setAttribute('por_vencer', $proximosVencer->isNotEmpty());
            $cliente->setAttribute('membresia_por_iniciar', $porIniciar->isNotEmpty());
            $cliente->setAttribute('tiene_membresia', $enrollments->contains(fn (array $item) => $item['categoria'] === 'membresia'));
            $cliente->setAttribute('proxima_fecha_inicio', $primerInicio['fecha_inicio'] ?? null);
            $cliente->setAttribute('proxima_fecha_fin', $primerVencimiento['fecha_fin'] ?? null);
            $cliente->setAttribute('asistencias_count', (int) ($asistenciasPorCliente[$cliente->id] ?? 0));
            $cliente->setAttribute('inasistencias_count', (int) ($inasistenciasPorCliente[$cliente->id] ?? 0));
            $cliente->setAttribute('traspasos_count', (int) ($traspasosPorCliente[$cliente->id] ?? 0));

            return $cliente;
        });

        if ($vigencia === 'activos') {
            $clientes = $clientes->where('tiene_plan_activo', true)->values();
        } elseif ($vigencia === 'por_vencer') {
            $clientes = $clientes->where('por_vencer', true)->values();
        } elseif ($vigencia === 'por_iniciar') {
            $clientes = $clientes->where('membresia_por_iniciar', true)->values();
        } elseif ($vigencia === 'inactivos') {
            $clientes = $clientes->where('estado_cliente', 'inactivo')->values();
        }

        $porEstado = $clientes->groupBy('estado_cliente')->map(fn ($g) => $g->count());

        $conPagos = $clientes->filter(fn ($c) => ($c->pagos_count ?? 0) > 0)->count();
        $clientesActivos = $clientes->where('estado_cliente', 'activo')->count();
        $clientesInactivos = $clientes->where('estado_cliente', 'inactivo')->count();
        $clientesPorVencer = $clientes->where('por_vencer', true)->count();
        $totalAsistencias = $clientes->sum('asistencias_count');
        $totalInasistencias = $clientes->sum('inasistencias_count');
        $totalTraspasos = $clientes->sum('traspasos_count');

        return [
            'clientes' => $clientes,
            'resumen' => [
                'total' => $clientes->count(),
                'activos' => $clientesActivos,
                'inactivos' => $clientesInactivos,
                'clientes_por_vencer' => $clientesPorVencer,
                'membresias_por_iniciar' => $membresiasPorIniciar,
                'traspasos' => $totalTraspasos,
                'asistencias' => $totalAsistencias,
                'inasistencias' => $totalInasistencias,
                'ventana_dias' => $ventanaDias,
                'vigencia' => $vigencia ?: 'todos',
                'por_estado' => $porEstado->toArray(),
                'con_membresias' => $clientes->filter(fn ($c) => (bool) ($c->tiene_membresia ?? false))->count(),
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

        // Pagos de membresía legacy (cliente_membresia_id)
        $pagosMembresiaLegacyQuery = Pago::with(['cliente', 'clienteMembresia.membresia']);
        if ($fechaDesde) {
            $pagosMembresiaLegacyQuery->whereDate('fecha_pago', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $pagosMembresiaLegacyQuery->whereDate('fecha_pago', '<=', $fechaHasta);
        }
        $pagosMembresiaLegacy = $pagosMembresiaLegacyQuery
            ->whereNotNull('cliente_membresia_id')
            ->orderBy('fecha_pago', 'desc')
            ->get();

        // Pagos nuevos de membresía y cuotas (cliente_matricula_id tipo membresía)
        $pagosMembresiaMatriculaQuery = Pago::with(['cliente', 'clienteMatricula.membresia']);
        if ($fechaDesde) {
            $pagosMembresiaMatriculaQuery->whereDate('fecha_pago', '>=', $fechaDesde);
        }
        if ($fechaHasta) {
            $pagosMembresiaMatriculaQuery->whereDate('fecha_pago', '<=', $fechaHasta);
        }
        $pagosMembresiaMatricula = $pagosMembresiaMatriculaQuery
            ->whereNotNull('cliente_matricula_id')
            ->whereHas('clienteMatricula', fn ($q) => $q->where('tipo', 'membresia'))
            ->orderBy('fecha_pago', 'desc')
            ->get();

        $pagosMembresia = $pagosMembresiaLegacy
            ->concat($pagosMembresiaMatricula)
            ->sortByDesc(fn ($pago) => optional($pago->fecha_pago)?->timestamp ?? 0)
            ->values();

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
            'pagos_membresia_legacy' => $pagosMembresiaLegacy,
            'pagos_membresia_matricula' => $pagosMembresiaMatricula,
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
