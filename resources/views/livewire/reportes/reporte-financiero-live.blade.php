<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-amber-600 to-amber-700 dark:from-amber-800 dark:to-amber-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte Financiero</h1>
                <p class="text-sm text-amber-100">Ingresos por pagos y ventas</p>
            </div>
            <x-reportes.exportar-buttons tipo="financiero" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/30 p-4">
            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Total pagos</div>
            <div class="text-lg font-bold text-indigo-700 dark:text-indigo-300">S/ {{ number_format($resumen['total_pagos'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total ventas</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($resumen['total_ventas'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-950/30 p-4">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Ingresos totales</div>
            <div class="text-lg font-bold text-amber-700 dark:text-amber-300">S/ {{ number_format($resumen['ingresos_totales'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Transacciones</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $resumen['cantidad_pagos'] }} pagos / {{ $resumen['cantidad_ventas'] }} ventas</div>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-2 font-semibold text-sm">Últimos pagos</div>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-amber-50 dark:bg-amber-900/30">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">Fecha</th>
                            <th class="px-3 py-1.5 text-left font-medium">Cliente</th>
                            <th class="px-3 py-1.5 text-right font-medium">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($pagos->take(20) as $p)
                            <tr>
                                <td class="px-3 py-1.5">{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-1.5">{{ $p->cliente ? $p->cliente->nombres . ' ' . $p->cliente->apellidos : '-' }}</td>
                                <td class="px-3 py-1.5 text-right">S/ {{ number_format($p->monto, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-2 text-center text-zinc-500">Sin pagos</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-2 font-semibold text-sm">Últimas ventas</div>
            <div class="overflow-x-auto max-h-64 overflow-y-auto">
                <table class="w-full text-sm">
                    <thead class="bg-amber-50 dark:bg-amber-900/30">
                        <tr>
                            <th class="px-3 py-1.5 text-left font-medium">Fecha</th>
                            <th class="px-3 py-1.5 text-left font-medium">Cliente</th>
                            <th class="px-3 py-1.5 text-right font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($ventas->take(20) as $v)
                            <tr>
                                <td class="px-3 py-1.5">{{ $v->fecha_venta?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-1.5">{{ $v->cliente ? $v->cliente->nombres . ' ' . $v->cliente->apellidos : '-' }}</td>
                                <td class="px-3 py-1.5 text-right">S/ {{ number_format($v->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-3 py-2 text-center text-zinc-500">Sin ventas</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
</div>
