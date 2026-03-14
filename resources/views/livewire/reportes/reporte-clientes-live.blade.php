<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-800 dark:to-indigo-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Clientes</h1>
                <p class="text-sm text-indigo-100">Clientes, vigencia comercial, asistencia y traspasos</p>
            </div>
            <x-reportes.exportar-buttons tipo="clientes" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" :estado="$estadoFilter" :createdById="$createdById" :trainerUserId="$trainerUserId" :vigencia="$vigenciaFilter" :ventanaDias="$ventanaDias" />
        </div>
    </div>

    <div class="px-5 space-y-4">
        <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />

        <div class="flex flex-wrap items-end gap-4">
            <div class="w-48">
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Estado</label>
                <select wire:model.live="estadoFilter" class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="w-52">
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Registrado por</label>
                <select wire:model.live="createdById" class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-52">
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Entrenador</label>
                <select wire:model.live="trainerUserId" class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    @foreach($usuarios as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-52">
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Vigencia</label>
                <select wire:model.live="vigenciaFilter" class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos</option>
                    <option value="activos">Con plan activo</option>
                    <option value="inactivos">Clientes inactivos</option>
                    <option value="por_vencer">Por vencer</option>
                    <option value="por_iniciar">Por iniciar</option>
                </select>
            </div>
            <div class="w-40">
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Ventana</label>
                <select wire:model.live="ventanaDias" class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500">
                    <option value="7">7 días</option>
                    <option value="15">15 días</option>
                    <option value="30">30 días</option>
                    <option value="45">45 días</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-8 gap-3">
        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/30 p-4">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Total clientes</div>
            <div class="text-lg font-bold text-indigo-700 dark:text-indigo-300">{{ $resumen['total'] }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Clientes activos</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['activos'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Clientes inactivos</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $resumen['inactivos'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-950/30 p-4">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Por vencer</div>
            <div class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $resumen['clientes_por_vencer'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-sky-100 dark:border-sky-900/50 bg-sky-50/60 dark:bg-sky-950/30 p-4">
            <div class="text-xs font-medium text-sky-600 dark:text-sky-400">Por iniciar</div>
            <div class="text-lg font-bold text-sky-700 dark:text-sky-300">{{ $resumen['membresias_por_iniciar'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-fuchsia-100 dark:border-fuchsia-900/50 bg-fuchsia-50/60 dark:bg-fuchsia-950/30 p-4">
            <div class="text-xs font-medium text-fuchsia-600 dark:text-fuchsia-400">Traspasos</div>
            <div class="text-lg font-bold text-fuchsia-700 dark:text-fuchsia-300">{{ $resumen['traspasos'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-lime-100 dark:border-lime-900/50 bg-lime-50/60 dark:bg-lime-950/30 p-4">
            <div class="text-xs font-medium text-lime-700 dark:text-lime-400">Asistencias</div>
            <div class="text-lg font-bold text-lime-700 dark:text-lime-300">{{ $resumen['asistencias'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-rose-100 dark:border-rose-900/50 bg-rose-50/60 dark:bg-rose-950/30 p-4">
            <div class="text-xs font-medium text-rose-600 dark:text-rose-400">Inasistencias</div>
            <div class="text-lg font-bold text-rose-700 dark:text-rose-300">{{ $resumen['inasistencias'] ?? 0 }}</div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Documento</th>
                        <th class="px-3 py-2 text-left font-medium">Nombres</th>
                        <th class="px-3 py-2 text-left font-medium">Plan actual</th>
                        <th class="px-3 py-2 text-left font-medium">Matrícula</th>
                        <th class="px-3 py-2 text-left font-medium">Inicio</th>
                        <th class="px-3 py-2 text-left font-medium">Fin</th>
                        <th class="px-3 py-2 text-left font-medium">Teléfono</th>
                        <th class="px-3 py-2 text-left font-medium">Estado</th>
                        <th class="px-3 py-2 text-left font-medium">Asist.</th>
                        <th class="px-3 py-2 text-left font-medium">Inasist.</th>
                        <th class="px-3 py-2 text-left font-medium">Traspasos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($clientes as $c)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-3 py-2">{{ $c->tipo_documento }} {{ $c->numero_documento }}</td>
                            <td class="px-3 py-2">{{ $c->nombres }} {{ $c->apellidos }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $c->plan_actual ?? 'Sin plan' }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 capitalize">{{ $c->plan_actual_tipo ?? '—' }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $c->fecha_matricula_actual?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $c->fecha_inicio_actual?->format('d/m/Y') ?? ($c->proxima_fecha_inicio?->format('d/m/Y') ?? '-') }}</td>
                            <td class="px-3 py-2">
                                @if($c->proxima_fecha_fin)
                                    <span class="{{ $c->por_vencer ? 'text-amber-600 dark:text-amber-400 font-medium' : '' }}">{{ $c->proxima_fecha_fin->format('d/m/Y') }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $c->telefono ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="capitalize">{{ $c->estado_cliente ?? '-' }}</div>
                                @if($c->membresia_por_iniciar)
                                    <div class="text-xs text-sky-600 dark:text-sky-400">Por iniciar</div>
                                @elseif($c->por_vencer)
                                    <div class="text-xs text-amber-600 dark:text-amber-400">Por vencer</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $c->asistencias_count ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $c->inasistencias_count ?? 0 }}</td>
                            <td class="px-3 py-2">{{ $c->traspasos_count ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-3 py-4 text-center text-zinc-500">No hay clientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
