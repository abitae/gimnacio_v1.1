<div class="space-y-4 p-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Importacion') }} #{{ $import->id }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $tipoLabel }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('importaciones.historial')" variant="ghost" size="sm" wire:navigate>
                {{ __('Historial') }}
            </flux:button>
            <flux:button :href="route('importaciones.index')" variant="primary" size="sm" wire:navigate>
                {{ __('Nueva importacion') }}
            </flux:button>
        </div>
    </div>

    <dl class="grid gap-2 rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700 sm:grid-cols-2 lg:grid-cols-3">
        <div><span class="text-zinc-500">{{ __('Sucursal') }}:</span> {{ $import->sucursal?->nombre ?? '--' }}</div>
        <div><span class="text-zinc-500">{{ __('Archivo') }}:</span> {{ $import->archivo_nombre }}</div>
        <div><span class="text-zinc-500">{{ __('Estado') }}:</span> {{ $import->estado }}</div>
        <div><span class="text-zinc-500">{{ __('Total filas') }}:</span> {{ $import->total_filas }}</div>
        <div><span class="text-zinc-500">{{ __('Validas') }}:</span> {{ $import->filas_validas }}</div>
        <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $import->filas_error }}</div>
        <div><span class="text-zinc-500">{{ __('Importadas / procesadas') }}:</span> {{ $import->filas_importadas }}</div>
        <div><span class="text-zinc-500">{{ __('Usuario') }}:</span> {{ $import->importedBy?->name ?? '--' }}</div>
        <div><span class="text-zinc-500">{{ __('Finalizado') }}:</span> {{ $import->finished_at?->format('Y-m-d H:i') ?? '--' }}</div>
    </dl>

    @if($phaseSummaries !== [])
        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Usuarios') }}</h3>
                <dl class="mt-2 space-y-1">
                    <div><span class="text-zinc-500">{{ __('Detectados') }}:</span> {{ $phaseSummaries['usuarios']['detectados'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Existentes') }}:</span> {{ $phaseSummaries['usuarios']['existentes'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('A crear') }}:</span> {{ $phaseSummaries['usuarios']['a_crear'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $phaseSummaries['usuarios']['errores'] ?? 0 }}</div>
                </dl>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Clientes') }}</h3>
                <dl class="mt-2 space-y-1">
                    <div><span class="text-zinc-500">{{ __('A crear') }}:</span> {{ $phaseSummaries['clientes']['importadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('A actualizar') }}:</span> {{ $phaseSummaries['clientes']['actualizadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Omitidas') }}:</span> {{ $phaseSummaries['clientes']['omitidas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $phaseSummaries['clientes']['errores'] ?? 0 }}</div>
                </dl>
            </div>

            <div class="rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Membresias y matriculas') }}</h3>
                <dl class="mt-2 space-y-1">
                    <div><span class="text-zinc-500">{{ __('Membresias a crear') }}:</span> {{ $phaseSummaries['membresias']['catalogo_a_crear'] ?? $phaseSummaries['membresias']['catalogo_creadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Matriculas') }}:</span> {{ $phaseSummaries['membresias']['importadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Omitidas') }}:</span> {{ $phaseSummaries['membresias']['omitidas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $phaseSummaries['membresias']['errores'] ?? 0 }}</div>
                </dl>
            </div>
        </div>
    @endif

    @if($import->filas_error > 0)
        <div>
            <flux:button :href="route('importaciones.errores.excel', $import)" variant="filled" size="sm">
                {{ __('Descargar filas con error (Excel)') }}
            </flux:button>
        </div>
    @endif

    <div class="grid gap-2 sm:grid-cols-2">
        <flux:select wire:model.live="phaseFilter" label="{{ __('Filtrar por fase') }}" size="sm">
            <option value="all">{{ __('Todas') }}</option>
            <option value="usuarios">{{ __('Usuarios') }}</option>
            <option value="clientes">{{ __('Clientes') }}</option>
            <option value="membresias">{{ __('Membresias') }}</option>
        </flux:select>

        <flux:select wire:model.live="stateFilter" label="{{ __('Filtrar por estado') }}" size="sm">
            <option value="all">{{ __('Todos') }}</option>
            <option value="valid">{{ __('Valid') }}</option>
            <option value="imported">{{ __('Imported') }}</option>
            <option value="skipped">{{ __('Skipped') }}</option>
            <option value="error">{{ __('Error') }}</option>
        </flux:select>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-800">
                <tr>
                    <th class="px-2 py-2 text-left">{{ __('Fila') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Fase') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Estado') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Contexto') }}</th>
                    <th class="px-2 py-2 text-left">{{ __('Errores') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-2 py-1.5 tabular-nums">{{ $row->fila_numero }}</td>
                        <td class="px-2 py-1.5">{{ $row->data_json['phase'] ?? 'general' }}</td>
                        <td class="px-2 py-1.5">{{ $row->estado }}</td>
                        <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                            {{ $row->data_json['nombre'] ?? $row->data_json['codigo'] ?? '--' }}
                            @if(!empty($row->data_json['dni']))
                                / DNI {{ $row->data_json['dni'] }}
                            @endif
                            @if(!empty($row->data_json['paquete']))
                                / {{ $row->data_json['paquete'] }}
                            @endif
                            @if(!empty($row->data_json['vendedor']))
                                / {{ $row->data_json['vendedor'] }}
                            @endif
                        </td>
                        <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                            @if(is_array($row->errores_json) && count($row->errores_json))
                                {{ implode('; ', $row->errores_json) }}
                            @elseif(is_array($row->data_json) && !empty($row->data_json['errores']))
                                {{ implode('; ', $row->data_json['errores']) }}
                            @elseif(!empty($row->data_json['info']))
                                {{ $row->data_json['info'] }}
                            @else
                                --
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-2 py-6 text-center text-zinc-500">{{ __('Sin filas almacenadas.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $rows->links() }}</div>
</div>
