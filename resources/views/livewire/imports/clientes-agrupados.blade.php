@php
    $sum = $resultadoPreview['summary'] ?? [];
    $phase = $resultadoPreview['phase_summaries']['clientes_agrupados'] ?? [];
    $selectedStateFilter = $stateFilter ?? 'all';
    $filteredRows = collect($resultadoPreview['row_results'] ?? [])->filter(function ($row) use ($selectedStateFilter) {
        return $selectedStateFilter === 'all' || (($row['estado'] ?? 'pending') === $selectedStateFilter);
    })->values();
@endphp

<div class="space-y-4 p-3">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Actualizar clientes agrupados') }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">
                {{ __('Carga especial desde Clientes_Agrupados.xlsx: contratos, pagos historicos y cuotas pendientes.') }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button :href="route('importaciones.historial')" variant="ghost" size="sm" wire:navigate>
                {{ __('Historial') }}
            </flux:button>
            <flux:button :href="route('importaciones.index')" variant="ghost" size="sm" wire:navigate>
                {{ __('Importacion legacy') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-2 sm:grid-cols-2">
            <flux:select wire:model="sucursalId" label="{{ __('Sucursal destino') }}" size="sm" required>
                <option value="">{{ __('-- Seleccionar --') }}</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </flux:select>

            <flux:input type="file" wire:model="archivo" label="{{ __('Archivo Clientes_Agrupados.xlsx') }}" size="sm" />
        </div>

        <flux:checkbox wire:model="stopOnError" label="{{ __('Detener ante el primer error') }}" />
        <div wire:loading wire:target="archivo" class="text-xs text-zinc-500">{{ __('Cargando archivo...') }}</div>

        <div class="flex flex-wrap gap-2">
            @can('importacion.crear')
                <flux:button type="button" variant="primary" size="sm" wire:click="validar" wire:loading.attr="disabled">
                    {{ __('Validar / vista previa') }}
                </flux:button>
                @if($importActual && $importActual->estado === 'preview')
                    <flux:button type="button" variant="filled" size="sm" wire:click="confirmarImportacion" wire:loading.attr="disabled">
                        {{ __('Confirmar actualizacion real') }}
                    </flux:button>
                @endif
            @else
                <p class="text-xs text-zinc-500">{{ __('No tienes permiso para ejecutar importaciones.') }}</p>
            @endcan
        </div>
    </div>

    @if($resultadoPreview)
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Informe general') }}</h2>
            <dl class="mt-2 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-5">
                <div><span class="text-zinc-500">{{ __('Total contratos') }}:</span> {{ $sum['total'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Validos') }}:</span> {{ $sum['validas'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $sum['errores'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Advertencias') }}:</span> {{ $sum['advertencias'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Procesados') }}:</span> {{ ($sum['importadas'] ?? 0) + ($sum['actualizadas'] ?? 0) }}</div>
            </dl>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Clientes') }}</h3>
                <dl class="mt-2 space-y-1">
                    <div><span class="text-zinc-500">{{ __('Creados') }}:</span> {{ $phase['clientes_creados'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Actualizados') }}:</span> {{ $phase['clientes_actualizados'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Membresias creadas') }}:</span> {{ $phase['membresias_creadas'] ?? 0 }}</div>
                </dl>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Contratos') }}</h3>
                <dl class="mt-2 space-y-1">
                    <div><span class="text-zinc-500">{{ __('Matriculas creadas') }}:</span> {{ $phase['matriculas_creadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Matriculas actualizadas') }}:</span> {{ $phase['matriculas_actualizadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Pagos historicos') }}:</span> {{ $phase['pagos_historicos'] ?? 0 }}</div>
                </dl>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 text-xs dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Cuotas e informe') }}</h3>
                <dl class="mt-2 space-y-1">
                    <div><span class="text-zinc-500">{{ __('Cuotas generadas') }}:</span> {{ $phase['cuotas_generadas'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Advertencias') }}:</span> {{ $phase['advertencias'] ?? 0 }}</div>
                    <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $phase['errores'] ?? 0 }}</div>
                </dl>
            </div>
        </div>

        <flux:select wire:model.live="stateFilter" label="{{ __('Filtrar por estado') }}" size="sm">
            <option value="all">{{ __('Todos') }}</option>
            <option value="valid">{{ __('Validos') }}</option>
            <option value="warning">{{ __('Advertencias') }}</option>
            <option value="imported">{{ __('Importados') }}</option>
            <option value="error">{{ __('Errores') }}</option>
        </flux:select>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-2 py-2 text-left">{{ __('Fila') }}</th>
                        <th class="px-2 py-2 text-left">{{ __('Estado') }}</th>
                        <th class="px-2 py-2 text-left">{{ __('Cliente / contrato') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('Precio') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('Pagado') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('Deuda') }}</th>
                        <th class="px-2 py-2 text-left">{{ __('Detalle') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($filteredRows as $r)
                        <tr>
                            <td class="px-2 py-1.5 tabular-nums">{{ $r['fila'] ?? '--' }}</td>
                            <td class="px-2 py-1.5">{{ $r['estado'] ?? '--' }}</td>
                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                                {{ $r['codigo'] ?? '--' }} / {{ $r['nombre'] ?? '--' }}
                                @if(!empty($r['paquete']))
                                    / {{ $r['paquete'] }}
                                @endif
                            </td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ number_format((float) ($r['precio'] ?? 0), 2) }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ number_format((float) ($r['pagado'] ?? 0), 2) }}</td>
                            <td class="px-2 py-1.5 text-right tabular-nums">{{ number_format((float) ($r['deuda'] ?? 0), 2) }}</td>
                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                                @if(!empty($r['errores']))
                                    {{ implode('; ', $r['errores']) }}
                                @elseif(!empty($r['warnings']))
                                    {{ implode('; ', $r['warnings']) }}
                                @elseif(!empty($r['info']))
                                    {{ $r['info'] }}
                                @else
                                    {{ __('Sin observaciones') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-2 py-6 text-center text-zinc-500">{{ __('No hay filas para el filtro actual.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
