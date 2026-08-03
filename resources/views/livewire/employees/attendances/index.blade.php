<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Asistencia de personal</h1>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Busque un empleado para registrar ingreso o salida, y consulte el listado del día.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button size="xs" variant="ghost" icon="chart-bar" href="{{ route('employees.attendances.report') }}" wire:navigate>
                Reporte mensual
            </flux:button>
            @if ($puedeRegistrar)
                <flux:button size="xs" variant="primary" icon="plus" href="{{ route('employees.attendances.create', ['fecha' => $fecha]) }}" wire:navigate>
                    Registro avanzado
                </flux:button>
            @endif
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <flux:input type="date" size="xs" wire:model.live="fecha" label="Fecha" />
        <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="employeeSearch"
            label="Buscar empleado"
            placeholder="Nombre, apellido o documento…"
            class="md:col-span-2" />
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Filtrar listado</label>
            <select wire:model.live="employeeId"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="">Todos los empleados</option>
                @foreach ($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->nombres }} {{ $e->apellidos }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @if (strlen(trim($employeeSearch)) >= 2)
        <div class="rounded-xl border border-sky-200 bg-sky-50/60 p-4 dark:border-sky-900/40 dark:bg-sky-950/20">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-sky-900 dark:text-sky-100">Resultados de búsqueda</h2>
                <span class="text-xs text-sky-700 dark:text-sky-300">{{ $empleadosBusqueda->count() }} empleado(s)</span>
            </div>

            @if ($empleadosBusqueda->isEmpty())
                <p class="text-xs text-zinc-500 dark:text-zinc-400">No se encontraron empleados activos con ese criterio.</p>
            @else
                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($empleadosBusqueda as $empleado)
                        @php
                            $asistencia = $asistenciasBusqueda->get($empleado->id);
                            $tieneIngreso = $asistencia && $asistencia->hora_ingreso;
                            $tieneSalida = $asistencia && $asistencia->hora_salida;
                        @endphp
                        <div wire:key="empleado-busqueda-{{ $empleado->id }}"
                            class="rounded-xl border border-white/80 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="truncate font-semibold text-zinc-900 dark:text-zinc-100">{{ $empleado->nombre_completo }}</div>
                                    <div class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                        @if ($empleado->documento)
                                            Doc. {{ $empleado->documento }}
                                        @endif
                                        @if ($empleado->cargo)
                                            · {{ $empleado->cargo }}
                                        @endif
                                    </div>
                                </div>
                                @if ($asistencia)
                                    <flux:badge color="{{ $tieneSalida ? 'green' : 'amber' }}" class="shrink-0 text-[10px]">
                                        {{ $tieneSalida ? 'Completo' : 'En turno' }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" class="shrink-0 text-[10px]">Sin registro</flux:badge>
                                @endif
                            </div>

                            @if ($asistencia)
                                <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                    <div>
                                        <span class="text-zinc-400">Ingreso</span>
                                        <div class="font-medium tabular-nums">
                                            {{ $asistencia->hora_ingreso ? \Carbon\Carbon::parse($asistencia->hora_ingreso)->format('H:i') : '—' }}
                                        </div>
                                    </div>
                                    <div>
                                        <span class="text-zinc-400">Salida</span>
                                        <div class="font-medium tabular-nums">
                                            {{ $asistencia->hora_salida ? \Carbon\Carbon::parse($asistencia->hora_salida)->format('H:i') : '—' }}
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @if ($puedeRegistrar)
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @if (! $asistencia)
                                        <flux:button size="xs" variant="primary" color="green" icon="arrow-right-end-on-rectangle"
                                            wire:click="registrarIngreso({{ $empleado->id }})">
                                            Registrar ingreso
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" icon="pencil-square"
                                            wire:click="abrirModalRegistro({{ $empleado->id }})">
                                            Manual
                                        </flux:button>
                                    @elseif (! $tieneSalida)
                                        <flux:button size="xs" variant="primary" color="sky" icon="arrow-left-start-on-rectangle"
                                            wire:click="registrarSalida({{ $empleado->id }})">
                                            Registrar salida
                                        </flux:button>
                                    @else
                                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Asistencia completa para {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @elseif (strlen(trim($employeeSearch)) > 0)
        <p class="text-xs text-zinc-500 dark:text-zinc-400">Escriba al menos 2 caracteres para buscar empleados.</p>
    @endif

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700"
        wire:loading.delay.class="opacity-60 pointer-events-none"
        wire:target="fecha,employeeSearch,employeeId,perPage,registrarIngreso,registrarSalida,guardarRegistroManual">
        <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-2 dark:border-zinc-700 dark:bg-zinc-900">
            <h2 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">
                Registros del {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
            </h2>
        </div>
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Empleado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Ingreso</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Salida</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Registrado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($attendances as $a)
                    <tr wire:key="attendance-row-{{ $a->id }}">
                        <td class="px-4 py-3">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $a->employee->nombre_completo }}</div>
                            @if ($a->employee->documento)
                                <div class="text-[11px] text-zinc-500">{{ $a->employee->documento }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $a->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $a->hora_ingreso ? \Carbon\Carbon::parse($a->hora_ingreso)->format('H:i') : '—' }}</td>
                        <td class="px-4 py-3 tabular-nums">{{ $a->hora_salida ? \Carbon\Carbon::parse($a->hora_salida)->format('H:i') : '—' }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-500">{{ $a->registradoPor?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">
                            No hay registros de asistencia para la fecha seleccionada.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">{{ $attendances->links() }}</div>

    <flux:modal wire:model="mostrarModalRegistro" class="md:w-lg">
        <div class="space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Registro manual de asistencia</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ $empleadoModal?->nombre_completo ?? 'Empleado' }}
                    · {{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}
                </p>
            </div>
            <flux:input size="xs" type="time" wire:model="registroForm.hora_ingreso" label="Hora ingreso" />
            <flux:input size="xs" type="time" wire:model="registroForm.hora_salida" label="Hora salida" />
            <flux:textarea size="xs" wire:model="registroForm.observaciones" label="Observaciones" rows="2" />
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cerrarModalRegistro">Cancelar</flux:button>
                <flux:button variant="primary" color="green" wire:click="guardarRegistroManual">Guardar</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
