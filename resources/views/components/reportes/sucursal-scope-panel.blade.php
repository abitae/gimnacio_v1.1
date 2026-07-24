@props([
    'etiqueta' => '',
    'puedeElegir' => false,
    'sucursales' => null,
])

@php
    $sucursales = $sucursales ?? collect();
@endphp

<div class="rounded-lg border border-sky-200 bg-sky-50/80 px-4 py-3 dark:border-sky-900/50 dark:bg-sky-950/20">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-sky-700 dark:text-sky-300">Alcance del reporte</p>
            <p class="mt-1 inline-flex rounded-full bg-white/80 px-3 py-1 text-sm font-medium text-sky-900 dark:bg-sky-900/40 dark:text-sky-100">
                Sucursal: {{ $etiqueta }}
            </p>
            @if (($reporteModoSucursal ?? 'active') === 'consolidated')
                <p class="mt-1 text-xs text-sky-700/90 dark:text-sky-300/90">Vista consolidada de solo lectura (super admin).</p>
            @endif
        </div>

        @if ($puedeElegir)
            <div class="grid gap-2 sm:grid-cols-2 lg:min-w-[28rem]">
                <div>
                    <label class="mb-1 block text-xs font-medium text-sky-800 dark:text-sky-200">Modo</label>
                    <select wire:model.live="reporteModoSucursal"
                        class="w-full rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-sky-800 dark:bg-zinc-900 dark:text-zinc-100">
                        <option value="active">Sucursal activa (sesión)</option>
                        <option value="specific">Otra sucursal asignada</option>
                        <option value="consolidated">Consolidado (todas mis sedes)</option>
                    </select>
                </div>
                @if (($reporteModoSucursal ?? 'active') === 'specific')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-sky-800 dark:text-sky-200">Sucursal</label>
                        <select wire:model.live="reporteSucursalId"
                            class="w-full rounded-lg border border-sky-200 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-sky-800 dark:bg-zinc-900 dark:text-zinc-100">
                            <option value="">Seleccionar…</option>
                            @foreach ($sucursales as $sucursal)
                                <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
