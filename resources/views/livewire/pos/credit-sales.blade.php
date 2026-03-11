<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Ventas a crédito</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Historial de ventas realizadas a crédito</p>
    </div>
    <div class="w-48">
        <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="Buscar..." />
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Nº Venta</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Anticipo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Saldo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($ventas as $v)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-2.5 text-xs font-medium">{{ $v->numero_venta }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $v->cliente ? $v->cliente->nombres . ' ' . $v->cliente->apellidos : '-' }}</td>
                        <td class="px-4 py-2.5 text-xs">S/ {{ number_format($v->total, 2) }}</td>
                        <td class="px-4 py-2.5 text-xs">S/ {{ number_format($v->monto_inicial ?? 0, 2) }}</td>
                        <td class="px-4 py-2.5 text-xs text-amber-600">S/ {{ number_format(($v->total ?? 0) - ($v->monto_inicial ?? 0), 2) }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $v->fecha_vencimiento_deuda ? $v->fecha_vencimiento_deuda->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $v->fecha_venta->format('d/m/Y H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay ventas a crédito</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-end">{{ $ventas->links() }}</div>
</div>
