<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-3 py-2 text-left">BioTime</th>
                <th class="px-3 py-2 text-left">Nombre</th>
                <th class="px-3 py-2 text-left">Inventario</th>
                <th class="px-3 py-2 text-left">Rol acceso</th>
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($rows as $row)
                @php
                    $expectedUsers = (int) ($desiredSelected ?? 0) + (int) ($row->protected_users_count ?? 0);
                    $deviation = $row->reported_users_count === null
                        ? null
                        : (int) $row->reported_users_count - $expectedUsers;
                @endphp
                <tr wire:key="device-map-{{ $row->biotime_id }}">
                    <td class="px-3 py-2 font-mono text-xs">{{ $row->biotime_id }}</td>
                    <td class="px-3 py-2">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->alias ?? '-' }}</div>
                        <div class="text-xs text-zinc-500">{{ $row->serial_number ?? '-' }}</div>
                    </td>
                    <td class="px-3 py-2">
                        <div>{{ $row->reported_users_count ?? '—' }}/{{ min(500, $row->capacity_limit ?: 500) }}</div>
                        <div class="text-xs text-zinc-500">
                            Protegidos {{ $row->protected_users_count ?? 0 }}
                            · {{ $row->inventory_verified ? 'verificado' : 'sin verificar' }}
                            · {{ $row->inventory_source ?? 'fuente desconocida' }}
                        </div>
                        <div class="text-xs {{ $deviation === 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-amber-600 dark:text-amber-300' }}">
                            {{ in_array($row->state, [1, 2], true) ? 'En línea' : 'Desconectado' }}
                            · Esperados {{ $expectedUsers }}
                            · Desviación {{ $deviation === null ? 'desconocida' : ($deviation > 0 ? '+'.$deviation : $deviation) }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            Última lectura {{ $row->inventory_synced_at?->diffForHumans() ?? 'nunca' }}
                        </div>
                        <label class="mt-1 flex items-center gap-1 text-xs">
                            <input type="checkbox" wire:model="deviceAccessEnabled.{{ $row->biotime_id }}">
                            Controlar acceso
                        </label>
                        <label class="mt-1 flex items-center gap-1 text-xs">
                            <input type="checkbox" wire:model="deviceInventoryVerified.{{ $row->biotime_id }}">
                            Inventario validado en piloto
                        </label>
                    </td>
                    <td class="px-3 py-2">
                        <select wire:model="deviceRoles.{{ $row->biotime_id }}" class="w-36 rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Sin rol</option>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </td>
                    <td class="px-3 py-2 text-right">
                        @can('biotime.editar')
                            <x-ui.table-actions>
                                <flux:button type="button" size="xs" variant="primary" wire:click="saveDeviceAccessRole({{ $row->biotime_id }})">Guardar rol</flux:button>
                            </x-ui.table-actions>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="px-3 py-6 text-center text-zinc-500">Sin terminales. Reinicia el puente o ejecuta <code class="text-xs">sync-catalog</code>.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="mt-2 text-xs text-zinc-500">Rol: Entrada / Salida / Ambos (toggle). Sin rol = no genera asistencia. Recomendado max. 2 terminales con rol por sede.</p>
</div>
