@php
    $sum = $resultadoPreview['summary'] ?? [];
    $phaseSummaries = $resultadoPreview['phase_summaries'] ?? [];
    $rowResults = collect($resultadoPreview['row_results'] ?? []);
    $phaseOptions = $rowResults->pluck('phase')->filter()->unique()->sort()->values();
    $stateOptions = $rowResults->pluck('estado')->filter()->unique()->sort()->values();
    $filteredRows = $rowResults->filter(function ($row) use ($phaseFilter, $stateFilter) {
        $phaseOk = $phaseFilter === 'all' || (($row['phase'] ?? 'general') === $phaseFilter);
        $stateOk = $stateFilter === 'all' || (($row['estado'] ?? 'pending') === $stateFilter);

        return $phaseOk && $stateOk;
    })->values();
@endphp

<div class="space-y-6 p-4 lg:p-6">
    <section data-app-page-header class="rounded-xl p-6 shadow-lg">
        <div class="grid gap-6 lg:grid-cols-[1.35fr,0.9fr]">
            <div class="space-y-3 text-white">
                <div class="inline-flex w-fit rounded-full bg-white/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.24em]">
                    Carga inicial
                </div>
                <div class="space-y-2">
                    <h1 class="text-2xl font-semibold lg:text-3xl">Centro profesional de importacion Excel</h1>
                    <p class="max-w-3xl text-sm text-white/80">
                        Prepara la base inicial del sistema con plantillas estandar, analisis previo de columnas y vista previa detallada antes de procesar usuarios, clientes, membresias, matriculas y cuotas.
                    </p>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                @foreach($catalog as $type => $config)
                    <a
                        href="{{ route('importaciones.plantilla', ['tipo' => $type]) }}"
                        class="rounded-2xl border border-white/15 bg-white/10 p-4 text-sm text-white transition hover:bg-white/15"
                    >
                        <div class="font-semibold">{{ $config['label'] }}</div>
                        <p class="mt-1 text-xs text-white/75">{{ $config['description'] }}</p>
                        <div class="mt-3 text-xs font-medium text-white/90">Descargar plantilla</div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="grid gap-6 xl:grid-cols-[1.15fr,0.85fr]">
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Configuracion de carga</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Selecciona la sucursal, el tipo de carga y el archivo antes de analizar o validar.</p>
                </div>
                <flux:button :href="route('importaciones.historial')" variant="ghost" size="sm" wire:navigate>
                    Historial
                </flux:button>
            </div>

            <div class="mt-5 grid gap-4">
                <div class="grid gap-4 md:grid-cols-2">
                    <flux:select wire:model.live="sucursalId" label="Sucursal destino" size="sm" required>
                        <option value="">-- Seleccionar --</option>
                        @foreach($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="tipo" label="Tipo de carga inicial" size="sm">
                        @foreach($tiposImplementados as $key)
                            <option value="{{ $key }}">{{ $tipos[$key] ?? $key }}</option>
                        @endforeach
                    </flux:select>
                </div>

                @if($tipo === \App\Support\Imports\ImportType::CLIENTES)
                    <flux:select wire:model="duplicateMode" label="Si el cliente ya existe" size="sm">
                        <option value="omitir">Omitir fila</option>
                        <option value="actualizar">Actualizar datos</option>
                        <option value="crear_o_actualizar">Crear o actualizar</option>
                    </flux:select>
                @endif

                <flux:checkbox wire:model="stopOnError" label="Detener ante el primer error" />
                <flux:input type="file" wire:model="archivo" label="Archivo Excel" size="sm" />
                <div wire:loading wire:target="archivo" class="text-xs text-zinc-500">Cargando archivo...</div>

                <div class="flex flex-wrap gap-2">
                    <flux:button type="button" variant="subtle" size="sm" wire:click="analizarColumnas" wire:loading.attr="disabled">
                        Analizar columnas
                    </flux:button>

                    @can('importacion.crear')
                        <flux:button type="button" variant="primary" size="sm" wire:click="validar" wire:loading.attr="disabled">
                            Validar / vista previa
                        </flux:button>

                        @if($importActual && $importActual->estado === 'preview')
                            <flux:button type="button" variant="filled" size="sm" wire:click="confirmarImportacion" wire:loading.attr="disabled">
                                Confirmar importacion
                            </flux:button>
                        @endif
                    @else
                        <p class="text-xs text-zinc-500">No tienes permiso para ejecutar importaciones.</p>
                    @endcan
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedConfig['label'] ?? 'Plantilla seleccionada' }}</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $selectedConfig['description'] ?? 'Selecciona un tipo de carga para ver sus columnas esperadas.' }}</p>
                </div>
                @if($selectedConfig)
                    <a href="{{ route('importaciones.plantilla', ['tipo' => $tipo]) }}" class="text-sm font-medium text-red-600 hover:text-red-700 dark:text-red-400">
                        Descargar plantilla
                    </a>
                @endif
            </div>

            @if($selectedConfig)
                <div class="mt-4 rounded-xl border border-zinc-200 bg-zinc-50 p-4 text-sm dark:border-zinc-700 dark:bg-zinc-800/70">
                    <div class="text-xs font-semibold uppercase tracking-[0.2em] text-zinc-500 dark:text-zinc-400">Columnas esperadas</div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach(($selectedConfig['headers'] ?? []) as $header)
                            <span class="rounded-full border border-zinc-300 bg-white px-3 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-900">{{ $header }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    @if($columnAnalysis)
        <section class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Analisis previo de columnas</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">Revisa la fila detectada como encabezado, columnas faltantes y columnas extra antes de procesar.</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ ($columnAnalysis['is_ready'] ?? false) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' }}">
                    {{ ($columnAnalysis['is_ready'] ?? false) ? 'Listo para validar' : 'Requiere revision' }}
                </span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-4">
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Fila encabezado</div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $columnAnalysis['header_row'] ?? '--' }}</div>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Filas leidas</div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $columnAnalysis['total_rows'] ?? 0 }}</div>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Faltantes</div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ count($columnAnalysis['missing_headers'] ?? []) }}</div>
                </div>
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Extras</div>
                    <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ count($columnAnalysis['extra_headers'] ?? []) }}</div>
                </div>
            </div>

            <div class="mt-5 grid gap-4 xl:grid-cols-3">
                <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Encabezados detectados</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse(($columnAnalysis['detected_headers'] ?? []) as $header)
                            <span class="rounded-full border border-zinc-300 px-3 py-1 text-xs dark:border-zinc-600">{{ $header }}</span>
                        @empty
                            <p class="text-sm text-zinc-500">No se detecto una fila de encabezados compatible.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                    <h3 class="font-semibold text-amber-900 dark:text-amber-100">Columnas faltantes</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse(($columnAnalysis['missing_headers'] ?? []) as $header)
                            <span class="rounded-full border border-amber-300 bg-white px-3 py-1 text-xs text-amber-900 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">{{ $header }}</span>
                        @empty
                            <p class="text-sm text-emerald-700 dark:text-emerald-300">No faltan columnas requeridas.</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-sky-200 bg-sky-50/70 p-4 dark:border-sky-900 dark:bg-sky-950/20">
                    <h3 class="font-semibold text-sky-900 dark:text-sky-100">Columnas extra</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse(($columnAnalysis['extra_headers'] ?? []) as $header)
                            <span class="rounded-full border border-sky-300 bg-white px-3 py-1 text-xs text-sky-900 dark:border-sky-800 dark:bg-sky-950/40 dark:text-sky-100">{{ $header }}</span>
                        @empty
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">No se detectaron columnas extra.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            @if(!empty($columnAnalysis['sample_rows']))
                <div class="mt-5 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-3 py-2 text-left">Fila muestra</th>
                                <th class="px-3 py-2 text-left">Valores detectados</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach(($columnAnalysis['sample_rows'] ?? []) as $index => $sampleRow)
                                <tr>
                                    <td class="px-3 py-2 font-medium text-zinc-700 dark:text-zinc-300">{{ $index + 1 }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ implode(' | ', $sampleRow) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif

    @if($resultadoPreview)
        <section class="grid gap-4 xl:grid-cols-[0.95fr,1.05fr]">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Resumen de vista previa</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Total filas</div>
                        <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $sum['total'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Validas</div>
                        <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $sum['validas'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Errores</div>
                        <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $sum['errores'] ?? 0 }}</div>
                    </div>
                    <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-500">Importables</div>
                        <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ ($sum['importadas'] ?? 0) + ($sum['actualizadas'] ?? 0) }}</div>
                    </div>
                </div>

                @if($phaseSummaries !== [])
                    <div class="mt-5 space-y-3">
                        @foreach($phaseSummaries as $phase => $phaseSummary)
                            <div class="rounded-xl border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                                <div class="font-semibold text-zinc-900 capitalize dark:text-zinc-100">{{ str_replace('_', ' ', $phase) }}</div>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach($phaseSummary as $label => $value)
                                        <div class="text-zinc-600 dark:text-zinc-400">
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ str_replace('_', ' ', $label) }}:</span> {{ $value }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Detalle de filas</h2>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400">Filtra la vista previa para revisar incidencias antes de la importacion final.</p>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <flux:select wire:model.live="phaseFilter" label="Filtrar por fase" size="sm">
                        <option value="all">Todas</option>
                        @foreach($phaseOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model.live="stateFilter" label="Filtrar por estado" size="sm">
                        <option value="all">Todos</option>
                        @foreach($stateOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-3 py-2 text-left">Fila</th>
                                <th class="px-3 py-2 text-left">Fase</th>
                                <th class="px-3 py-2 text-left">Estado</th>
                                <th class="px-3 py-2 text-left">Contexto</th>
                                <th class="px-3 py-2 text-left">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse($filteredRows as $row)
                                <tr>
                                    <td class="px-3 py-2 tabular-nums">{{ $row['fila'] ?? '--' }}</td>
                                    <td class="px-3 py-2">{{ $row['phase'] ?? 'general' }}</td>
                                    <td class="px-3 py-2">{{ $row['estado'] ?? '--' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                        {{ $row['nombre'] ?? $row['codigo'] ?? '--' }}
                                        @if(!empty($row['dni']))
                                            / DNI {{ $row['dni'] }}
                                        @endif
                                        @if(!empty($row['paquete']))
                                            / {{ $row['paquete'] }}
                                        @endif
                                        @if(!empty($row['vendedor']))
                                            / {{ $row['vendedor'] }}
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">
                                        @if(!empty($row['errores']))
                                            {{ implode('; ', $row['errores']) }}
                                        @elseif(!empty($row['warnings']))
                                            {{ implode('; ', $row['warnings']) }}
                                        @elseif(!empty($row['info']))
                                            {{ $row['info'] }}
                                        @else
                                            Sin observaciones
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-3 py-6 text-center text-zinc-500">No hay filas para el filtro actual.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
</div>
