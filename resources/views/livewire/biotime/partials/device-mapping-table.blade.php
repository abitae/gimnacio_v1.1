<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-3 py-2 text-left">BioTime</th>
                <th class="px-3 py-2 text-left">Nombre</th>
                <th class="px-3 py-2 text-left">Sucursal</th>
                <th class="px-3 py-2 text-left">Rol acceso</th>
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($rows as $row)
                <tr wire:key="device-map-{{ $row->biotime_id }}">
                    <td class="px-3 py-2 font-mono text-xs">{{ $row->biotime_id }}</td>
                    <td class="px-3 py-2">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->alias ?? '-' }}</div>
                        <div class="text-xs text-zinc-500">{{ $row->serial_number ?? '-' }}</div>
                    </td>
                    <td class="px-3 py-2">
                        <select wire:model="deviceTargets.{{ $row->biotime_id }}" class="w-48 rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Sin mapear</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-3 py-2">
                        <select wire:model="deviceRoles.{{ $row->biotime_id }}" class="w-36 rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Sin rol</option>
                            <option value="entrada">Entrada</option>
                            <option value="salida">Salida</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </td>
                    <td class="px-3 py-2 text-right space-x-1">
                        @can('biotime.editar')
                            <button type="button" wire:click="saveSucursalMapping('device', {{ $row->biotime_id }})" class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">Guardar sede</button>
                            <button type="button" wire:click="saveDeviceAccessRole({{ $row->biotime_id }})" class="rounded-md border border-zinc-300 px-2.5 py-1.5 text-xs font-medium text-zinc-800 dark:border-zinc-600 dark:text-zinc-100">Guardar rol</button>
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
