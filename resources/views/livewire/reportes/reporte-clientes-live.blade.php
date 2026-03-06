<div class="space-y-5 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/50 shadow-sm overflow-hidden">
    <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-800 dark:to-indigo-900 px-5 py-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold text-white">Reporte de Clientes</h1>
                <p class="text-sm text-indigo-100">Clientes por estado y fecha de registro</p>
            </div>
            <x-reportes.exportar-buttons tipo="clientes" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" :estado="$estadoFilter" :createdById="$createdById" :trainerUserId="$trainerUserId" />
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
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
        <div class="rounded-xl border border-indigo-100 dark:border-indigo-900/50 bg-indigo-50/50 dark:bg-indigo-950/30 p-4">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Total clientes</div>
            <div class="text-lg font-bold text-indigo-700 dark:text-indigo-300">{{ $resumen['total'] }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 dark:border-emerald-900/50 bg-emerald-50/60 dark:bg-emerald-950/30 p-4">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Con membresías</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['con_membresias'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-amber-100 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-950/30 p-4">
            <div class="text-xs font-medium text-amber-600 dark:text-amber-400">Con pagos</div>
            <div class="text-lg font-bold text-amber-700 dark:text-amber-300">{{ $resumen['con_pagos'] ?? 0 }}</div>
        </div>
        @foreach($resumen['por_estado'] ?? [] as $estado => $cant)
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50 p-4">
                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400 capitalize">{{ $estado ?: 'Sin estado' }}</div>
                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $cant }}</div>
            </div>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-indigo-100 dark:bg-indigo-900/40 text-indigo-800 dark:text-indigo-200">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium">Documento</th>
                        <th class="px-3 py-2 text-left font-medium">Nombres</th>
                        <th class="px-3 py-2 text-left font-medium">Teléfono</th>
                        <th class="px-3 py-2 text-left font-medium">Estado</th>
                        <th class="px-3 py-2 text-left font-medium">Membresías</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($clientes as $c)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-3 py-2">{{ $c->tipo_documento }} {{ $c->numero_documento }}</td>
                            <td class="px-3 py-2">{{ $c->nombres }} {{ $c->apellidos }}</td>
                            <td class="px-3 py-2">{{ $c->telefono ?? '-' }}</td>
                            <td class="px-3 py-2 capitalize">{{ $c->estado_cliente ?? '-' }}</td>
                            <td class="px-3 py-2">{{ $c->cliente_membresias_count ?? 0 }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-4 text-center text-zinc-500">No hay clientes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>
