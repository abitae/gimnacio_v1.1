<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Analítica</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Selecciona el reporte que deseas consultar</p>
        @if (!empty($activeSucursalNombre))
            <p class="mt-2 inline-flex rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                Sucursal activa: {{ $activeSucursalNombre }}
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($reportes as $slug => $reporte)
            <a href="{{ route($reporte['route']) }}" wire:navigate
                class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                    <flux:icon :name="$reporte['icon']" class="size-6" />
                </span>
                <div>
                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $reporte['label'] }}</span>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $reporte['description'] }}</p>
                </div>
            </a>
        @empty
            <p class="col-span-full text-sm text-zinc-500 dark:text-zinc-400">No tienes reportes asignados. Contacta al administrador.</p>
        @endforelse
    </div>
</div>
