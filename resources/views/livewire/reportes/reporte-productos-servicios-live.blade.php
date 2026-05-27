<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-orange-600 to-orange-700 dark:from-orange-800 dark:to-orange-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Productos y Servicios</h1>
                <p class="text-sm text-orange-100">Más vendidos y productos con stock bajo</p>
            </div>
            <x-reportes.exportar-buttons tipo="productos-servicios" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-orange-100 dark:border-orange-900/50 bg-orange-50/50 dark:bg-orange-950/30 p-4">
            <div class="text-xs font-medium text-orange-600 dark:text-orange-400">Productos activos</div>
            <div class="text-lg font-bold text-orange-700 dark:text-orange-300">{{ $resumen['total_productos_activos'] }}</div>
        </div>
        <div class="rounded-xl border border-orange-100 dark:border-orange-900/50 bg-orange-50/60 dark:bg-orange-950/30 p-4">
            <div class="text-xs font-medium text-orange-600 dark:text-orange-400">Servicios activos</div>
            <div class="text-lg font-bold text-orange-700 dark:text-orange-300">{{ $resumen['total_servicios_activos'] }}</div>
        </div>
        <div class="rounded-xl border border-red-100 dark:border-red-900/50 bg-red-50/60 dark:bg-red-950/30 p-4">
            <div class="text-xs font-medium text-red-600 dark:text-red-400">Productos bajo stock</div>
            <div class="text-lg font-bold text-red-700 dark:text-red-300">{{ $resumen['productos_bajo_stock'] }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Productos vendidos</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['productos_vendidos'] ?? 0 }}</div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-200 px-3 py-2 font-semibold text-sm">Más vendidos (período)</div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-orange-50 dark:bg-orange-900/30">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">Tipo</th>
                            <th class="px-3 py-1.5 text-left font-medium">Nombre</th>
                            <th class="px-3 py-1.5 text-right font-medium">Cant.</th>
                            <th class="px-3 py-1.5 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($itemsMasVendidos as $item)
                            <tr>
                                <td class="px-3 py-1.5 capitalize">{{ $item->tipo_item }}</td>
                                <td class="px-3 py-1.5">{{ $item->nombre_item }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $item->cantidad_vendida }}</td>
                                <td class="px-3 py-1.5 text-right">S/ {{ number_format($item->total ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-2 text-center text-zinc-500">Sin ventas en el período</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-200 px-3 py-2 font-semibold text-sm">Productos con stock bajo</div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-red-50 dark:bg-red-900/30">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">Código</th>
                            <th class="px-3 py-1.5 text-left font-medium">Nombre</th>
                            <th class="px-3 py-1.5 text-right font-medium">Stock</th>
                            <th class="px-3 py-1.5 text-right font-medium">Mínimo</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($productosBajoStock as $p)
                            <tr>
                                <td class="px-3 py-1.5">{{ $p->codigo }}</td>
                                <td class="px-3 py-1.5">{{ $p->nombre }}</td>
                                <td class="px-3 py-1.5 text-right text-red-600 dark:text-red-400">{{ $p->stock_actual }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $p->stock_minimo }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-2 text-center text-zinc-500">Ningún producto bajo stock</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-zinc-100 dark:bg-zinc-900/60 text-zinc-800 dark:text-zinc-200 px-3 py-2 font-semibold text-sm">Productos vendidos por caja</div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">Caja</th>
                            <th class="px-3 py-1.5 text-left font-medium">Usuario caja</th>
                            <th class="px-3 py-1.5 text-right font-medium">Ventas</th>
                            <th class="px-3 py-1.5 text-right font-medium">Cant.</th>
                            <th class="px-3 py-1.5 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($productosPorCaja as $row)
                            <tr>
                                <td class="px-3 py-1.5">{{ $row['caja'] }}</td>
                                <td class="px-3 py-1.5">{{ $row['usuario_caja'] }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $row['ventas_count'] }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $row['cantidad_productos'] }}</td>
                                <td class="px-3 py-1.5 text-right">S/ {{ number_format($row['total'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-2 text-center text-zinc-500">Sin productos vendidos por caja</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-zinc-100 dark:bg-zinc-900/60 text-zinc-800 dark:text-zinc-200 px-3 py-2 font-semibold text-sm">Productos vendidos por usuario</div>
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">Usuario</th>
                            <th class="px-3 py-1.5 text-right font-medium">Ventas</th>
                            <th class="px-3 py-1.5 text-right font-medium">Cant.</th>
                            <th class="px-3 py-1.5 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($productosPorUsuario as $row)
                            <tr>
                                <td class="px-3 py-1.5">{{ $row['usuario'] }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $row['ventas_count'] }}</td>
                                <td class="px-3 py-1.5 text-right">{{ $row['cantidad_productos'] }}</td>
                                <td class="px-3 py-1.5 text-right">S/ {{ number_format($row['total'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-3 py-2 text-center text-zinc-500">Sin productos vendidos por usuario</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="bg-zinc-100 dark:bg-zinc-900/60 text-zinc-800 dark:text-zinc-200 px-3 py-2 font-semibold text-sm">Detalle de productos vendidos</div>
        <div class="overflow-x-auto max-h-[30rem] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900/40">
                    <tr>
                        <th class="px-3 py-1.5 text-left font-medium">Fecha</th>
                        <th class="px-3 py-1.5 text-left font-medium">Caja</th>
                        <th class="px-3 py-1.5 text-left font-medium">Vendedor</th>
                        <th class="px-3 py-1.5 text-left font-medium">Producto</th>
                        <th class="px-3 py-1.5 text-right font-medium">Cant.</th>
                        <th class="px-3 py-1.5 text-right font-medium">Subtotal</th>
                        <th class="px-3 py-1.5 text-left font-medium">Comprobante</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($detalleProductosVendidos as $row)
                        <tr>
                            <td class="px-3 py-1.5 whitespace-nowrap">{{ $row['fecha']?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-1.5">{{ $row['caja'] }}</td>
                            <td class="px-3 py-1.5">{{ $row['vendedor'] }}</td>
                            <td class="px-3 py-1.5">{{ $row['producto'] }}</td>
                            <td class="px-3 py-1.5 text-right">{{ $row['cantidad'] }}</td>
                            <td class="px-3 py-1.5 text-right">S/ {{ number_format($row['subtotal'] ?? 0, 2) }}</td>
                            <td class="px-3 py-1.5">
                                <a class="text-orange-700 underline underline-offset-2 dark:text-orange-300"
                                   href="{{ route('ventas.comprobante.pdf', ['venta' => $row['venta_id'], 'reprint' => 1]) }}"
                                   target="_blank">
                                    {{ $row['comprobante'] ?: $row['numero_venta'] }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-2 text-center text-zinc-500">Sin detalle de productos vendidos</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
