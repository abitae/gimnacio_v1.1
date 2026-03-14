<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-cyan-600 to-cyan-700 dark:from-cyan-800 dark:to-cyan-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Clientes con membresía y clases activas</h1>
                <p class="text-sm text-cyan-100">Membresías activas, clases activas y pagos por período</p>
            </div>
            <x-reportes.exportar-buttons tipo="clientes-membresia-clases" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
        </div>
    </div>
    <div class="px-5 space-y-4 pb-5">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            <div class="rounded-xl border border-violet-100 dark:border-violet-900/50 bg-violet-50/50 dark:bg-violet-950/30 p-4">
                <div class="text-xs font-medium text-violet-600 dark:text-violet-400">Membresías activas</div>
                <div class="text-lg font-bold text-violet-700 dark:text-violet-300">{{ $resumen['cantidad_membresias_activas'] }}</div>
            </div>
            <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/50 dark:bg-amber-950/30 p-4">
                <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Clases activas</div>
                <div class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $resumen['cantidad_clases_activas'] }}</div>
            </div>
            <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/30 p-4">
                <div class="text-xs font-medium text-indigo-600 dark:text-indigo-400">Clientes con membresía activa</div>
                <div class="text-lg font-bold text-indigo-700 dark:text-indigo-300">{{ $resumen['clientes_con_membresia_activa'] }}</div>
            </div>
            <div class="rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-4">
                <div class="text-xs font-medium text-slate-600 dark:text-slate-400">Clientes con clase activa</div>
                <div class="text-lg font-bold text-slate-700 dark:text-slate-300">{{ $resumen['clientes_con_clase_activa'] }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
                <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Pagos membresía</div>
                <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($resumen['total_pagos_membresia'], 2) }}</div>
                <div class="text-xs text-zinc-500">{{ $resumen['cantidad_pagos_membresia'] }} pagos</div>
            </div>
            <div class="rounded-xl border border-orange-100 dark:border-orange-900/50 bg-orange-50/60 dark:bg-orange-950/30 p-4">
                <div class="text-xs font-medium text-orange-600 dark:text-orange-400">Pagos clases</div>
                <div class="text-lg font-bold text-orange-700 dark:text-orange-300">S/ {{ number_format($resumen['total_pagos_clase'], 2) }}</div>
                <div class="text-xs text-zinc-500">{{ $resumen['cantidad_pagos_clase'] }} pagos</div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="bg-violet-100 dark:bg-violet-900/40 text-violet-800 dark:text-violet-200 px-3 py-2 font-semibold text-sm">Membresías activas</div>
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-violet-50 dark:bg-violet-900/30">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium">Cliente</th>
                                <th class="px-3 py-1.5 text-left font-medium">Membresía / Producto</th>
                                <th class="px-3 py-1.5 text-left font-medium">Inicio</th>
                                <th class="px-3 py-1.5 text-left font-medium">Fin</th>
                                <th class="px-3 py-1.5 text-right font-medium">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($membresias_activas as $m)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '-' }}</td>
                                    <td class="px-3 py-1.5">{{ $m->membresia?->nombre ?? 'N/A' }}</td>
                                    <td class="px-3 py-1.5">{{ $m->fecha_inicio?->format('d/m/Y') }}</td>
                                    <td class="px-3 py-1.5">{{ $m->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-3 py-1.5 text-right">S/ {{ number_format($m->precio_final ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                            @foreach($matriculas_membresia_activas as $mat)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $mat->cliente ? trim($mat->cliente->nombres . ' ' . $mat->cliente->apellidos) : '-' }}</td>
                                    <td class="px-3 py-1.5">{{ $mat->nombre }}</td>
                                    <td class="px-3 py-1.5">{{ $mat->fecha_inicio?->format('d/m/Y') }}</td>
                                    <td class="px-3 py-1.5">{{ $mat->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-3 py-1.5 text-right">S/ {{ number_format($mat->precio_final ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                            @if($membresias_activas->isEmpty() && $matriculas_membresia_activas->isEmpty())
                                <tr><td colspan="5" class="px-3 py-2 text-center text-zinc-500">Sin membresías activas</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 px-3 py-2 font-semibold text-sm">Clases activas</div>
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-amber-50 dark:bg-amber-900/30">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium">Cliente</th>
                                <th class="px-3 py-1.5 text-left font-medium">Clase</th>
                                <th class="px-3 py-1.5 text-left font-medium">Inicio</th>
                                <th class="px-3 py-1.5 text-left font-medium">Fin</th>
                                <th class="px-3 py-1.5 text-right font-medium">Precio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($matriculas_clase_activas as $mat)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $mat->cliente ? trim($mat->cliente->nombres . ' ' . $mat->cliente->apellidos) : '-' }}</td>
                                    <td class="px-3 py-1.5">{{ $mat->nombre }}</td>
                                    <td class="px-3 py-1.5">{{ $mat->fecha_inicio?->format('d/m/Y') }}</td>
                                    <td class="px-3 py-1.5">{{ $mat->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="px-3 py-1.5 text-right">S/ {{ number_format($mat->precio_final ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                            @if($matriculas_clase_activas->isEmpty())
                                <tr><td colspan="5" class="px-3 py-2 text-center text-zinc-500">Sin clases activas</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="bg-emerald-100 dark:bg-emerald-900/40 text-emerald-800 dark:text-emerald-200 px-3 py-2 font-semibold text-sm">Pagos de membresía (período)</div>
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-emerald-50 dark:bg-emerald-900/30">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium">Fecha</th>
                                <th class="px-3 py-1.5 text-left font-medium">Cliente</th>
                                <th class="px-3 py-1.5 text-left font-medium">Membresía</th>
                                <th class="px-3 py-1.5 text-right font-medium">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($pagos_membresia as $p)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-1.5">{{ $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-' }}</td>
                                    <td class="px-3 py-1.5">{{ $p->clienteMembresia?->membresia?->nombre ?? $p->clienteMatricula?->membresia?->nombre ?? '-' }}</td>
                                    <td class="px-3 py-1.5 text-right">S/ {{ number_format($p->monto, 2) }}</td>
                                </tr>
                            @endforeach
                            @if($pagos_membresia->isEmpty())
                                <tr><td colspan="4" class="px-3 py-2 text-center text-zinc-500">Sin pagos en el período</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="bg-orange-100 dark:bg-orange-900/40 text-orange-800 dark:text-orange-200 px-3 py-2 font-semibold text-sm">Pagos de clases (período)</div>
                <div class="overflow-x-auto max-h-72 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-orange-50 dark:bg-orange-900/30">
                            <tr>
                                <th class="px-3 py-1.5 text-left font-medium">Fecha</th>
                                <th class="px-3 py-1.5 text-left font-medium">Cliente</th>
                                <th class="px-3 py-1.5 text-left font-medium">Clase</th>
                                <th class="px-3 py-1.5 text-right font-medium">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($pagos_clase as $p)
                                <tr>
                                    <td class="px-3 py-1.5">{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-1.5">{{ $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-' }}</td>
                                    <td class="px-3 py-1.5">{{ $p->clienteMatricula?->nombre ?? '-' }}</td>
                                    <td class="px-3 py-1.5 text-right">S/ {{ number_format($p->monto, 2) }}</td>
                                </tr>
                            @endforeach
                            @if($pagos_clase->isEmpty())
                                <tr><td colspan="4" class="px-3 py-2 text-center text-zinc-500">Sin pagos en el período</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
