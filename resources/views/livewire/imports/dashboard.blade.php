@php
    $sum = $resultadoPreview['summary'] ?? [];
    $phaseSummaries = $resultadoPreview['phase_summaries'] ?? [];
    $selectedPhaseFilter = $phaseFilter ?? 'all';
    $selectedStateFilter = $stateFilter ?? 'all';
    $filteredRows = collect($resultadoPreview['row_results'] ?? [])->filter(function ($row) use ($selectedPhaseFilter, $selectedStateFilter) {
        $phaseOk = $selectedPhaseFilter === 'all' || (($row['phase'] ?? 'general') === $selectedPhaseFilter);
        $stateOk = $selectedStateFilter === 'all' || (($row['estado'] ?? 'pending') === $selectedStateFilter);

        return $phaseOk && $stateOk;
    })->values();
@endphp

<div class="space-y-4 p-3">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Importacion de datos') }}</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">
            {{ __('Importacion legacy por sucursal en orden fijo: usuarios, membresias, clientes, matriculas y deudas.') }}
        </p>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
        <p class="font-medium">{{ __('Orden recomendado') }}</p>
        <ol class="mt-1 list-decimal pl-5">
            <li>{{ __('Usuarios / vendedores') }}</li>
            <li>{{ __('Membresias y matriculas (Socios activos.xlsx)') }}</li>
            <li>{{ __('Clientes (Socios activos.xlsx)') }}</li>
            <li>{{ __('Deudas resumen (Deudas Clientes.xlsx)') }}</li>
        </ol>
        <p class="mt-2 font-medium">{{ __('Plantillas de columnas') }}</p>
        <ul class="mt-1 list-disc pl-5 space-y-0.5">
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'usuarios']) }}">{{ __('Usuarios / vendedores') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'membresias_matriculas']) }}">{{ __('Membresias y matriculas') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'clientes']) }}">{{ __('Clientes') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'deudas']) }}">{{ __('Deudas clientes') }}</a></li>
        </ul>
    </div>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-2 sm:grid-cols-2">
            <flux:select wire:model="sucursalId" label="{{ __('Sucursal destino') }}" size="sm" required>
                <option value="">{{ __('-- Seleccionar --') }}</option>
                @foreach($sucursales as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model="tipo" label="{{ __('Tipo de importacion') }}" size="sm">
                @foreach($tiposImplementados as $key)
                    <option value="{{ $key }}">{{ $tipos[$key] ?? $key }}</option>
                @endforeach
            </flux:select>
        </div>

        @if($tipo === \App\Support\Imports\ImportType::CLIENTES)
            <flux:select wire:model="duplicateMode" label="{{ __('Si el cliente ya existe') }}" size="sm">
                <option value="omitir">{{ __('Omitir fila') }}</option>
                <option value="actualizar">{{ __('Actualizar datos') }}</option>
                <option value="crear_o_actualizar">{{ __('Crear o actualizar (recomendado)') }}</option>
            </flux:select>
        @endif

        <flux:checkbox wire:model="stopOnError" label="{{ __('Detener ante el primer error') }}" />
        <flux:input type="file" wire:model="archivo" label="{{ __('Archivo Excel') }}" size="sm" />
        <div wire:loading wire:target="archivo" class="text-xs text-zinc-500">{{ __('Cargando...') }}</div>

        <div class="flex flex-wrap gap-2">
            @can('importacion.crear')
                <flux:button type="button" variant="primary" size="sm" wire:click="validar" wire:loading.attr="disabled">
                    {{ __('Validar / vista previa') }}
                </flux:button>
                @if($importActual && $importActual->estado === 'preview')
                    <flux:button type="button" variant="filled" size="sm" wire:click="confirmarImportacion" wire:loading.attr="disabled">
                        {{ __('Confirmar importacion real') }}
                    </flux:button>
                @endif
            @else
                <p class="text-xs text-zinc-500">{{ __('No tienes permiso para ejecutar importaciones.') }}</p>
            @endcan

            <flux:button :href="route('importaciones.historial')" variant="ghost" size="sm" wire:navigate>
                {{ __('Historial') }}
            </flux:button>
        </div>
    </div>

    @if($resultadoPreview)
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Resumen general') }}</h2>
            <dl class="mt-2 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-4">
                <div><span class="text-zinc-500">{{ __('Total filas') }}:</span> {{ $sum['total'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Validas') }}:</span> {{ $sum['validas'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $sum['errores'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Omitidas') }}:</span> {{ $sum['omitidas'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('A crear') }}:</span> {{ $sum['importadas'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('A actualizar') }}:</span> {{ $sum['actualizadas'] ?? 0 }}</div>
            </dl>
        </div>

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
                        <div><span class="text-zinc-500">{{ __('Matriculas a crear') }}:</span> {{ $phaseSummaries['membresias']['importadas'] ?? 0 }}</div>
                        <div><span class="text-zinc-500">{{ __('Omitidas') }}:</span> {{ $phaseSummaries['membresias']['omitidas'] ?? 0 }}</div>
                        <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $phaseSummaries['membresias']['errores'] ?? 0 }}</div>
                    </dl>
                </div>
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
                        <th class="px-2 py-2 text-left">{{ __('Detalle / errores') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($filteredRows as $r)
                        <tr>
                            <td class="px-2 py-1.5 tabular-nums">{{ $r['fila'] ?? '--' }}</td>
                            <td class="px-2 py-1.5">{{ $r['phase'] ?? 'general' }}</td>
                            <td class="px-2 py-1.5">{{ $r['estado'] ?? '--' }}</td>
                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                                {{ $r['nombre'] ?? $r['codigo'] ?? '--' }}
                                @if(!empty($r['dni']))
                                    / DNI {{ $r['dni'] }}
                                @endif
                                @if(!empty($r['paquete']))
                                    / {{ $r['paquete'] }}
                                @endif
                                @if(!empty($r['vendedor']))
                                    / {{ $r['vendedor'] }}
                                @endif
                            </td>
                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                                @if(!empty($r['errores']))
                                    {{ implode('; ', $r['errores']) }}
                                @elseif(!empty($r['info']))
                                    {{ $r['info'] }}
                                @else
                                    {{ __('Sin observaciones') }}
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-6 text-center text-zinc-500">{{ __('No hay filas para el filtro actual.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
