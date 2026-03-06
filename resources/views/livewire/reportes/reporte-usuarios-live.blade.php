<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-slate-600 to-slate-700 dark:from-slate-800 dark:to-slate-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Usuarios</h1>
                <p class="text-sm text-slate-200">Ventas y actividad por usuario</p>
            </div>
            <x-reportes.exportar-buttons tipo="usuarios" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total ventas</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($resumen['total_ventas'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-4">
            <div class="text-xs font-medium text-slate-600 dark:text-slate-400">Transacciones</div>
            <div class="text-lg font-bold text-slate-700 dark:text-slate-300">{{ $resumen['total_transacciones'] }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-slate-100 dark:bg-slate-900/40 text-slate-800 dark:text-slate-200">
                <tr>
                    <th class="px-3 py-2 text-left font-medium">Usuario</th>
                    <th class="px-3 py-2 text-right font-medium">Cantidad ventas</th>
                    <th class="px-3 py-2 text-right font-medium">Total vendido</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($porUsuario as $row)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                        <td class="px-3 py-2">{{ $row->usuario ? $row->usuario->name : 'Usuario #' . $row->usuario_id }}</td>
                        <td class="px-3 py-2 text-right">{{ $row->cantidad }}</td>
                        <td class="px-3 py-2 text-right font-medium">S/ {{ number_format($row->total_ventas ?? 0, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-3 py-4 text-center text-zinc-500">No hay ventas en el período.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </div>
</div>
