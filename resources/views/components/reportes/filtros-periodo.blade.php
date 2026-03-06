@props(['fechaDesde' => '', 'fechaHasta' => '', 'mostrarRango' => true])
<div class="rounded-xl border border-indigo-200/60 dark:border-indigo-800/60 bg-gradient-to-br from-indigo-50/80 to-white dark:from-indigo-950/30 dark:to-zinc-900/80 p-4 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 mb-3">Filtros de período</p>
    <div class="flex flex-wrap items-end gap-4">
        <div class="w-44">
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Fecha inicio</label>
            <input type="date" wire:model.live="fechaDesde"
                class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition" />
        </div>
        <div class="w-44">
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">Fecha fin</label>
            <input type="date" wire:model.live="fechaHasta"
                class="w-full rounded-lg border border-indigo-200 dark:border-indigo-700 bg-white dark:bg-zinc-800 px-3 py-2 text-sm text-zinc-900 dark:text-zinc-100 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:focus:ring-indigo-400 dark:focus:border-indigo-400 transition" />
        </div>
        @if($mostrarRango && $fechaDesde && $fechaHasta)
            <p class="text-xs text-zinc-500 dark:text-zinc-400 self-center">
                Rango: {{ \Carbon\Carbon::parse($fechaDesde)->translatedFormat('d M Y') }} — {{ \Carbon\Carbon::parse($fechaHasta)->translatedFormat('d M Y') }}
            </p>
        @endif
    </div>
</div>
