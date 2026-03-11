<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cuentas por cobrar</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Deudas pendientes de clientes</p>
    </div>
    <div class="flex gap-3 items-center">
        <div class="w-48">
            <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="Cliente..." />
        </div>
        <div class="w-32">
            <select wire:model.live="estadoFilter" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Todos</option>
                <option value="pendiente">Pendiente</option>
                <option value="parcial">Parcial</option>
                <option value="vencido">Vencido</option>
            </select>
        </div>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Origen</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Pagado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Saldo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($debts as $d)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-2.5 text-xs font-medium">{{ $d->cliente ? $d->cliente->nombres . ' ' . $d->cliente->apellidos : '-' }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $d->origen_tipo }} @if($d->venta) {{ $d->venta->numero_venta }} @endif</td>
                        <td class="px-4 py-2.5 text-xs">S/ {{ number_format($d->monto_total, 2) }}</td>
                        <td class="px-4 py-2.5 text-xs">S/ {{ number_format($d->monto_pagado, 2) }}</td>
                        <td class="px-4 py-2.5 text-xs font-medium text-amber-600 dark:text-amber-400">S/ {{ number_format($d->saldo_pendiente, 2) }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $d->fecha_vencimiento ? $d->fecha_vencimiento->format('d/m/Y') : '-' }}</td>
                        <td class="px-4 py-2.5 text-xs">
                            <span class="rounded-full px-1.5 py-0.5 text-xs {{ $d->estado === 'vencido' ? 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300' : ($d->estado === 'parcial' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-300') }}">
                                {{ ucfirst($d->estado) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay deudas pendientes</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-end">{{ $debts->links() }}</div>
</div>
