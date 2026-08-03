<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Reporte de asistencia</h1>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Resumen mensual por empleado y detalle diario del período.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ $exportUrl }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300 dark:hover:bg-green-900/30">
                <flux:icon name="table-cells" class="size-4" />
                Exportar Excel
            </a>
            <flux:button variant="ghost" size="xs" href="{{ route('employees.attendances.index') }}" wire:navigate>
                Volver al listado
            </flux:button>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Mes</label>
            <select wire:model.live="mes"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}">{{ now()->month($m)->translatedFormat('F') }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Año</label>
            <select wire:model.live="anio"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                @for ($y = now()->year; $y >= now()->year - 3; $y--)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endfor
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Empleado</label>
            <select wire:model.live="employeeId"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="">Todos con registros</option>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->nombres }} {{ $e->apellidos }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Empleados con registro</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $totales['empleados_con_registro'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-900/50 dark:bg-sky-950/30">
            <div class="text-xs font-medium text-sky-600 dark:text-sky-400">Días registrados</div>
            <div class="text-lg font-bold text-sky-700 dark:text-sky-300">{{ $totales['total_dias'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
            <div class="text-xs font-medium text-amber-700 dark:text-amber-400">Tardanzas (min)</div>
            <div class="text-lg font-bold text-amber-800 dark:text-amber-300">{{ $totales['total_tardanza_minutos'] ?? 0 }}</div>
        </div>
    </div>

    <p class="text-xs text-zinc-500 dark:text-zinc-400">
        Período: {{ $start->format('d/m/Y') }} – {{ $end->format('d/m/Y') }}
    </p>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Resumen por empleado</h2>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Empleado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Documento</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Cargo</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Días</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Tardanza (min)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($filas as $fila)
                    <tr wire:key="reporte-resumen-{{ $fila['employee']->id }}">
                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $fila['employee']->nombre_completo }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $fila['employee']->documento ?: '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300">{{ $fila['employee']->cargo ?: '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $fila['dias'] }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $fila['tardanza_minutos'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">
                            No hay registros de asistencia en el período seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Detalle diario</h2>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Empleado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Ingreso</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Salida</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Tardanza</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Registrado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($detalle as $fila)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $fila['empleado'] }}</div>
                            @if ($fila['documento'])
                                <div class="text-[11px] text-zinc-500">{{ $fila['documento'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $fila['fecha'] }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $fila['hora_ingreso'] ?: '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $fila['hora_salida'] ?: '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ $fila['tardanza_minutos'] }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-500">{{ $fila['registrado_por'] ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">
                            Sin detalle para el período seleccionado.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
