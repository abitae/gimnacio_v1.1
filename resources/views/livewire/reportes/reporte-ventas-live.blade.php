<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 dark:from-emerald-800 dark:to-emerald-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Ventas</h1>
                <p class="text-sm text-emerald-100">Ventas por período (detallado)</p>
            </div>
            <x-reportes.exportar-buttons tipo="ventas" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>

    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/30 p-4">
            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Cantidad</div>
            <div class="text-lg font-bold text-indigo-700 dark:text-indigo-300">{{ $resumen['cantidad'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Subtotal</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($resumen['subtotal'] ?? 0, 2) }}</div>
        </div>
        <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-950/30 p-4">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Descuentos</div>
            <div class="text-lg font-bold text-amber-700 dark:text-amber-300">S/ {{ number_format($resumen['descuento_total'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">IGV</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($resumen['igv_total'] ?? 0, 2) }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total ventas</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($resumen['total'], 2) }}</div>
        </div>
    </div>

    @if(!empty($resumen['por_metodo_pago']))
        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/30 dark:bg-indigo-950/20 p-4">
            <div class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 mb-2">Por método de pago</div>
            <div class="flex flex-wrap gap-2">
                @foreach($resumen['por_metodo_pago'] as $metodo => $datos)
                    @php $tot = is_array($datos) ? ($datos['total'] ?? 0) : $datos; $cant = is_array($datos) ? ($datos['cantidad'] ?? 0) : ''; @endphp
                    <span class="inline-flex rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-200 px-2.5 py-0.5 text-xs font-medium capitalize">{{ $metodo ?: 'Sin especificar' }}: S/ {{ number_format($tot, 2) }}@if($cant) ({{ $cant }})@endif</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">#</th>
                        <th class="px-3 py-2 text-left font-medium">Fecha / Hora</th>
                        <th class="px-3 py-2 text-left font-medium">Nº Venta</th>
                        <th class="px-3 py-2 text-left font-medium">Cliente</th>
                        <th class="px-3 py-2 text-left font-medium">Documento</th>
                        <th class="px-3 py-2 text-right font-medium">Subtotal</th>
                        <th class="px-3 py-2 text-right font-medium">Desc.</th>
                        <th class="px-3 py-2 text-right font-medium">IGV</th>
                        <th class="px-3 py-2 text-right font-medium">Total</th>
                        <th class="px-3 py-2 text-left font-medium">Método</th>
                        <th class="px-3 py-2 text-left font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($ventas as $v)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-3 py-2">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2">{{ $v->fecha_venta?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2">{{ $v->numero_venta ?? $v->id }}</td>
                            <td class="px-3 py-2">{{ $v->cliente ? trim($v->cliente->nombres . ' ' . $v->cliente->apellidos) : '-' }}</td>
                            <td class="px-3 py-2">{{ $v->cliente ? ($v->cliente->tipo_documento ?? '') . ' ' . ($v->cliente->numero_documento ?? '') : '-' }}</td>
                            <td class="px-3 py-2 text-right">S/ {{ number_format($v->subtotal ?? 0, 2) }}</td>
                            <td class="px-3 py-2 text-right">S/ {{ number_format($v->descuento ?? 0, 2) }}</td>
                            <td class="px-3 py-2 text-right">S/ {{ number_format($v->igv ?? 0, 2) }}</td>
                            <td class="px-3 py-2 text-right font-medium">S/ {{ number_format($v->total, 2) }}</td>
                            <td class="px-3 py-2 capitalize">{{ $v->metodo_pago ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $v->estado ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-4 text-center text-zinc-500">No hay ventas en el período seleccionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
