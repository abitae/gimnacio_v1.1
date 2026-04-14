<div class="space-y-4 p-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Importación') }} #{{ $import->id }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $tipoLabel }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('importaciones.historial')" variant="ghost" size="sm" wire:navigate>
                {{ __('Historial') }}
            </flux:button>
            <flux:button :href="route('importaciones.index')" variant="primary" size="sm" wire:navigate>
                {{ __('Nueva importación') }}
            </flux:button>
        </div>
    </div>

    <dl class="grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
        <div><span class="text-zinc-500">{{ __('Sucursal') }}:</span> {{ $import->sucursal?->nombre ?? '—' }}</div>
        <div><span class="text-zinc-500">{{ __('Archivo') }}:</span> {{ $import->archivo_nombre }}</div>
        <div><span class="text-zinc-500">{{ __('Estado') }}:</span> {{ $import->estado }}</div>
        <div><span class="text-zinc-500">{{ __('Total filas') }}:</span> {{ $import->total_filas }}</div>
        <div><span class="text-zinc-500">{{ __('Válidas') }}:</span> {{ $import->filas_validas }}</div>
        <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $import->filas_error }}</div>
        <div><span class="text-zinc-500">{{ __('Importadas / procesadas') }}:</span> {{ $import->filas_importadas }}</div>
        <div><span class="text-zinc-500">{{ __('Usuario') }}:</span> {{ $import->importedBy?->name ?? '—' }}</div>
        <div><span class="text-zinc-500">{{ __('Finalizado') }}:</span> {{ $import->finished_at?->format('Y-m-d H:i') ?? '—' }}</div>
    </dl>

    @if($import->filas_error > 0)
        <div>
            <flux:button :href="route('importaciones.errores.excel', $import)" variant="filled" size="sm">
                {{ __('Descargar filas con error (Excel)') }}
            </flux:button>
        </div>
    @endif

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-2 py-2 text-left">{{ __('Fila') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Estado') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Errores') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-2 py-1.5 tabular-nums">{{ $row->fila_numero }}</td>
                        <td class="px-2 py-1.5">{{ $row->estado }}</td>
                        <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                            @if(is_array($row->errores_json) && count($row->errores_json))
                                {{ implode('; ', $row->errores_json) }}
                            @elseif(is_array($row->data_json) && !empty($row->data_json['errores']))
                                {{ implode('; ', $row->data_json['errores']) }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-2 py-6 text-center text-zinc-500">{{ __('Sin filas almacenadas (importación antigua o sin detalle).') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>
</div>
