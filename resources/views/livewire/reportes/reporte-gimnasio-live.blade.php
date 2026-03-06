<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-800 dark:to-blue-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte del Gimnasio</h1>
                <p class="text-sm text-blue-100">Resumen ejecutivo del negocio</p>
            </div>
            <x-reportes.exportar-buttons tipo="gimnasio" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-blue-100 dark:border-blue-900/50 bg-blue-50/50 dark:bg-blue-950/30 p-4 shadow-sm">
            <div class="text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wide">Ventas (período)</div>
            <div class="mt-1 text-2xl font-bold text-blue-700 dark:text-blue-300">S/ {{ number_format($resumen['ventas_total'], 2) }}</div>
            <div class="text-xs text-zinc-500">{{ $resumen['ventas_cantidad'] }} transacciones</div>
        </div>
        <div class="rounded-xl border border-violet-100 dark:border-violet-900/50 bg-violet-50/60 dark:bg-violet-950/30 p-4 shadow-sm">
            <div class="text-xs font-medium text-violet-600 dark:text-violet-400 uppercase tracking-wide">Matrículas nuevas</div>
            <div class="mt-1 text-2xl font-bold text-violet-700 dark:text-violet-300">{{ $resumen['matriculas_nuevas'] }}</div>
            <div class="text-xs text-zinc-500">S/ {{ number_format($resumen['ingresos_matriculas'], 2) }} ingresos</div>
        </div>
        <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-950/30 p-4 shadow-sm">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wide">Ingresos totales</div>
            <div class="mt-1 text-2xl font-bold text-amber-700 dark:text-amber-300">S/ {{ number_format($resumen['ingresos_totales'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/60 dark:bg-indigo-950/30 p-4 shadow-sm">
            <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wide">Clientes totales</div>
            <div class="mt-1 text-2xl font-bold text-indigo-700 dark:text-indigo-300">{{ $resumen['clientes_totales'] }}</div>
            <div class="text-xs text-zinc-500">{{ $resumen['clientes_activos'] }} activos</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4 shadow-sm sm:col-span-2 lg:col-span-1">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wide">Membresías activas</div>
            <div class="mt-1 text-2xl font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['membresias_activas'] }}</div>
        </div>
    </div>
    </div>
</div>
