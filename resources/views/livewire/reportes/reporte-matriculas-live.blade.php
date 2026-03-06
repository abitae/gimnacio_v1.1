<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-violet-600 to-violet-700 dark:from-violet-800 dark:to-violet-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Matrículas</h1>
                <p class="text-sm text-violet-100">Membresías y clases por período</p>
            </div>
            <x-reportes.exportar-buttons tipo="matriculas" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="rounded-xl border border-violet-100 dark:border-violet-900/50 bg-violet-50/50 dark:bg-violet-950/30 p-4">
            <div class="text-xs font-medium text-violet-600 dark:text-violet-400">Total matrículas</div>
            <div class="text-lg font-bold text-violet-700 dark:text-violet-300">{{ $resumen['cantidad'] }}</div>
        </div>
        <div class="rounded-xl border border-violet-100 dark:border-violet-900/50 bg-violet-50/60 dark:bg-violet-950/30 p-4">
            <div class="text-xs font-medium text-violet-600 dark:text-violet-400">Membresías</div>
            <div class="text-lg font-bold text-violet-700 dark:text-violet-300">{{ $resumen['membresias'] }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Clases</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $resumen['clases'] }}</div>
        </div>
        <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-950/30 p-4">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Descuentos total</div>
            <div class="text-lg font-bold text-amber-700 dark:text-amber-300">S/ {{ number_format($resumen['descuentos_total'] ?? 0, 2) }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Ingresos</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($resumen['ingresos'], 2) }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-violet-100 dark:bg-violet-900/40 text-violet-800 dark:text-violet-200">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Cliente</th>
                        <th class="px-3 py-2 text-left font-medium">Tipo</th>
                        <th class="px-3 py-2 text-left font-medium">Producto</th>
                        <th class="px-3 py-2 text-left font-medium">Inicio</th>
                        <th class="px-3 py-2 text-left font-medium">Precio final</th>
                        <th class="px-3 py-2 text-left font-medium">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($matriculas as $m)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-3 py-2">{{ $m->cliente ? $m->cliente->nombres . ' ' . $m->cliente->apellidos : '-' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $m->tipo }}</td>
                            <td class="px-3 py-2">{{ $m->nombre }}</td>
                            <td class="px-3 py-2">{{ $m->fecha_inicio?->format('d/m/Y') }}</td>
                            <td class="px-3 py-2">S/ {{ number_format($m->precio_final ?? 0, 2) }}</td>
                            <td class="px-3 py-2 capitalize">{{ $m->estado ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-4 text-center text-zinc-500">No hay matrículas en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
