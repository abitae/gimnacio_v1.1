<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
        <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-3 py-2 text-left">BioTime</th>
                <th class="px-3 py-2 text-left">Nombre</th>
                <th class="px-3 py-2 text-left">Sucursal</th>
                <th class="px-3 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
            @forelse ($rows as $row)
                <tr>
                    <td class="px-3 py-2 font-mono text-xs">{{ $row->biotime_id }}</td>
                    <td class="px-3 py-2">
                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->{$nameField} ?? '-' }}</div>
                        <div class="text-xs text-zinc-500">{{ $row->{$codeField} ?? '-' }}</div>
                    </td>
                    <td class="px-3 py-2">
                        <select wire:model="{{ $targets }}.{{ $row->biotime_id }}" class="w-56 rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Sin mapear</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-3 py-2 text-right">
                        @can('biotime.editar')
                            <button type="button" wire:click="saveSucursalMapping('{{ $type }}', {{ $row->biotime_id }})" class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">Guardar</button>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="px-3 py-6 text-center text-zinc-500">Sin registros. Reinicia el puente (empuja áreas/departamentos al arrancar) o ejecuta <code class="text-xs">sync-catalog</code>.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
