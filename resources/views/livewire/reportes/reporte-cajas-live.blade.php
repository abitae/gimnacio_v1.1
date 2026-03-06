<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 dark:from-emerald-800 dark:to-emerald-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Cajas</h1>
                <p class="text-sm text-emerald-100">Aperturas, cierres e ingresos por caja</p>
            </div>
            <x-reportes.exportar-buttons tipo="cajas" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/50 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Cajas</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['cantidad'] }}</div>
        </div>
        <div class="rounded-xl border border-green-100 dark:border-green-900/50 bg-green-50/60 dark:bg-green-950/30 p-4">
            <div class="text-xs font-medium text-green-600 dark:text-green-400">Abiertas</div>
            <div class="text-lg font-bold text-green-700 dark:text-green-300">{{ $resumen['abiertas'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Cerradas</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $resumen['cerradas'] }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total ingresos</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($resumen['total_ingresos'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-red-100 dark:border-red-900/50 bg-red-50/60 dark:bg-red-950/30 p-4">
            <div class="text-xs font-medium text-red-600 dark:text-red-400">Total salidas</div>
            <div class="text-lg font-bold text-red-700 dark:text-red-300">S/ {{ number_format($resumen['total_salidas'], 2) }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200">
                <tr>
                    <th class="px-3 py-2 text-left font-medium">#</th>
                    <th class="px-3 py-2 text-left font-medium">Usuario</th>
                    <th class="px-3 py-2 text-left font-medium">Apertura</th>
                    <th class="px-3 py-2 text-left font-medium">Cierre</th>
                    <th class="px-3 py-2 text-left font-medium">Estado</th>
                    <th class="px-3 py-2 text-right font-medium">Saldo inicial</th>
                    <th class="px-3 py-2 text-right font-medium">Saldo final</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($cajas as $c)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-3 py-2">{{ $c->id }}</td>
                        <td class="px-3 py-2">{{ $c->usuario?->name ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $c->fecha_apertura?->format('d/m/Y H:i') }}</td>
                        <td class="px-3 py-2">{{ $c->fecha_cierre?->format('d/m/Y H:i') ?? '-' }}</td>
                        <td class="px-3 py-2 capitalize">{{ $c->estado }}</td>
                        <td class="px-3 py-2 text-right">S/ {{ number_format($c->saldo_inicial, 2) }}</td>
                        <td class="px-3 py-2 text-right">S/ {{ number_format($c->saldo_final ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-3 py-4 text-center text-zinc-500">No hay cajas en el período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
