<div class="space-y-4 p-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Historial de importaciones') }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ __('Registros de importaciones Excel por sucursal.') }}</p>
        </div>
        <flux:button :href="route('importaciones.index')" variant="ghost" size="sm" wire:navigate>
            {{ __('Nueva importación') }}
        </flux:button>
    </div>

    <div class="max-w-xs">
        <flux:select wire:model.live="sucursalFiltro" label="{{ __('Filtrar por sucursal') }}" size="sm">
            <option value="">{{ __('Todas') }}</option>
            @foreach($sucursales as $s)
                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-2 py-2 text-left">{{ __('Fecha') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Tipo') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Sucursal') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Archivo') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Estado') }}</th>
                    <th class="px-2 py-2 text-right">{{ __('Filas') }}</th>
                    <th class="px-2 py-2 text-left"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($imports as $imp)
                    <tr>
                        <td class="px-2 py-1.5 whitespace-nowrap">{{ $imp->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="px-2 py-1.5">{{ $tipoLabels[$imp->tipo_importacion] ?? $imp->tipo_importacion }}</td>
                        <td class="px-2 py-1.5">{{ $imp->sucursal?->nombre ?? '—' }}</td>
                        <td class="px-2 py-1.5 max-w-[12rem] truncate" title="{{ $imp->archivo_nombre }}">{{ $imp->archivo_nombre }}</td>
                        <td class="px-2 py-1.5">{{ $imp->estado }}</td>
                        <td class="px-2 py-1.5 text-right tabular-nums">{{ $imp->total_filas }} / {{ $imp->filas_importadas }}</td>
                        <td class="px-2 py-1.5">
                            <flux:button :href="route('importaciones.show', $imp)" variant="ghost" size="xs" wire:navigate>
                                {{ __('Ver') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-2 py-6 text-center text-zinc-500">{{ __('No hay importaciones registradas.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $imports->links() }}</div>
</div>
