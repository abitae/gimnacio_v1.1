<div class="space-y-4 p-3">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Importación de datos') }}</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">
            {{ __('Archivos del sistema anterior: fila 1 título, fila 2 encabezados. Todos los registros se asocian a la sucursal elegida.') }}
        </p>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50/80 p-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100">
        <p class="font-medium">{{ __('Orden recomendado') }}</p>
        <ol class="mt-1 list-decimal pl-5">
            <li>{{ __('Vendedores (cualquier Excel con columna VENDEDOR)') }}</li>
            <li>{{ __('Clientes (Socios activos.xlsx)') }}</li>
            <li>{{ __('Membresías / matrículas (mismo archivo, solo filas tipo membresía)') }}</li>
            <li>{{ __('Cuotas (Deudas Cuotas Clientes.xlsx) — fuente principal de deuda cuotificada') }}</li>
            <li>{{ __('Deudas resumen (Deudas Clientes.xlsx) — solo si no hubo cuotas importadas para ese plan') }}</li>
        </ol>
        <p class="mt-2 font-medium">{{ __('Plantillas de columnas (solo encabezados)') }}</p>
        <ul class="mt-1 list-disc pl-5 space-y-0.5">
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'usuarios']) }}">{{ __('Vendedores (columna VENDEDOR)') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'clientes']) }}">{{ __('Clientes / socios activos') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'membresias_matriculas']) }}">{{ __('Membresías (mismo Excel)') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'deudas']) }}">{{ __('Deudas clientes') }}</a></li>
            <li><a class="underline hover:text-amber-900 dark:hover:text-amber-50" href="{{ route('importaciones.plantilla', ['tipo' => 'cuotas']) }}">{{ __('Cuotas') }}</a></li>
        </ul>
    </div>

    <div class="grid gap-3 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-2 sm:grid-cols-2">
            <div>
                <flux:select wire:model="sucursalId" label="{{ __('Sucursal destino de la importación') }}" size="sm" required>
                    <option value="">{{ __('— Seleccionar —') }}</option>
                    @foreach($sucursales as $s)
                        <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                    @endforeach
                </flux:select>
            </div>
            <div>
                <flux:select wire:model="tipo" label="{{ __('Tipo de importación') }}" size="sm">
                    @foreach($tiposImplementados as $key)
                        <option value="{{ $key }}">{{ $tipos[$key] ?? $key }}</option>
                    @endforeach
                </flux:select>
            </div>
        </div>

        @if($tipo === \App\Support\Imports\ImportType::CLIENTES)
            <flux:select wire:model="duplicateMode" label="{{ __('Si el cliente ya existe (mismo código o DNI)') }}" size="sm">
                <option value="omitir">{{ __('Omitir fila') }}</option>
                <option value="actualizar">{{ __('Actualizar datos') }}</option>
                <option value="crear_o_actualizar">{{ __('Crear o actualizar (recomendado)') }}</option>
            </flux:select>
        @endif

        <flux:checkbox wire:model="stopOnError" label="{{ __('Detener ante el primer error (transacción global)') }}" />

        <flux:input type="file" wire:model="archivo" label="{{ __('Archivo Excel') }}" size="sm" />
        <div wire:loading wire:target="archivo" class="text-xs text-zinc-500">{{ __('Cargando…') }}</div>

        <div class="flex flex-wrap gap-2">
            @can('importacion.crear')
            <flux:button type="button" variant="primary" size="sm" wire:click="validar" wire:loading.attr="disabled">
                {{ __('Validar / vista previa') }}
            </flux:button>
            @if($importActual && $importActual->estado === 'preview')
                <flux:button type="button" variant="filled" size="sm" wire:click="confirmarImportacion" wire:loading.attr="disabled">
                    {{ __('Confirmar importación real') }}
                </flux:button>
            @endif
            @else
            <p class="text-xs text-zinc-500">{{ __('No tienes permiso para ejecutar importaciones. Solo puedes revisar el historial.') }}</p>
            @endcan
            <flux:button :href="route('importaciones.historial')" variant="ghost" size="sm" wire:navigate>
                {{ __('Historial') }}
            </flux:button>
        </div>
    </div>

    @if($resultadoPreview)
        @php($sum = $resultadoPreview['summary'] ?? [])
        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Resumen') }}</h2>
            <dl class="mt-2 grid gap-2 text-xs sm:grid-cols-2 lg:grid-cols-4">
                <div><span class="text-zinc-500">{{ __('Total filas') }}:</span> {{ $sum['total'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Válidas') }}:</span> {{ $sum['validas'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Errores') }}:</span> {{ $sum['errores'] ?? 0 }}</div>
                <div><span class="text-zinc-500">{{ __('Omitidas') }}:</span> {{ $sum['omitidas'] ?? 0 }}</div>
                @if(isset($sum['importadas']))
                    <div><span class="text-zinc-500">{{ __('A crear') }}:</span> {{ $sum['importadas'] ?? 0 }}</div>
                @endif
                @if(isset($sum['actualizadas']))
                    <div><span class="text-zinc-500">{{ __('A actualizar') }}:</span> {{ $sum['actualizadas'] ?? 0 }}</div>
                @endif
            </dl>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-xs dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-2 py-2 text-left">{{ __('Fila') }}</th>
                        <th class="px-2 py-2 text-left">{{ __('Estado') }}</th>
                        <th class="px-2 py-2 text-left">{{ __('Detalle / errores') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach(($resultadoPreview['row_results'] ?? []) as $r)
                        <tr>
                            <td class="px-2 py-1.5 tabular-nums">{{ $r['fila'] ?? '—' }}</td>
                            <td class="px-2 py-1.5">{{ $r['estado'] ?? '—' }}</td>
                            <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                                @if(!empty($r['errores']))
                                    {{ implode('; ', $r['errores']) }}
                                @elseif(!empty($r['info']))
                                    {{ $r['info'] }}
                                @else
                                    {{ isset($r['codigo']) ? 'COD: '.$r['codigo'] : '' }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
