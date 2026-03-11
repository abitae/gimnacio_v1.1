<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Ingresos por alquileres</h1>
        <flux:button variant="ghost" size="xs" href="{{ route('rentals.calendar.index') }}" wire:navigate>Calendario</flux:button>
    </div>
    <div class="flex gap-4 items-center">
        <flux:input type="date" wire:model.live="fechaDesde" />
        <flux:input type="date" wire:model.live="fechaHasta" />
        <span class="text-sm font-medium">Total: S/ {{ number_format($totalIngresos, 2) }}</span>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Espacio</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Monto</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rentals as $r)
                    <tr>
                        <td class="px-4 py-2">{{ $r->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $r->rentableSpace->nombre }}</td>
                        <td class="px-4 py-2">{{ $r->cliente ? $r->cliente->nombres . ' ' . $r->cliente->apellidos : ($r->nombre_externo ?? '—') }}</td>
                        <td class="px-4 py-2">S/ {{ number_format((float)$r->precio - (float)$r->descuento, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">No hay registros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rentals->links() }}
</div>
