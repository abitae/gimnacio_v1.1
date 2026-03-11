<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cupones de descuento</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Gestiona cupones para POS, matrículas, membresías y clases</p>
        </div>
        @can('cupones.create')
        <flux:button icon="plus" color="purple" variant="primary" size="xs" href="{{ route('cupones.create') }}" wire:navigate>
            Nuevo cupón
        </flux:button>
        @endcan
    </div>
    <div class="flex gap-3 items-center">
        <div class="w-48">
            <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="Código o nombre..." />
        </div>
        <div class="w-32">
            <select wire:model.live="estadoFilter" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Todos</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </div>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Código</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Nombre</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Descuento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Vigencia</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Usos</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Aplica a</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($coupons as $c)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <td class="px-4 py-2.5 text-xs font-mono font-medium">{{ $c->codigo }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $c->nombre }}</td>
                        <td class="px-4 py-2.5 text-xs">S/ {{ number_format($c->valor_descuento, 2) }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $c->fecha_inicio->format('d/m/Y') }} - {{ $c->fecha_vencimiento->format('d/m/Y') }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ $c->cantidad_usada }}{{ $c->cantidad_max_usos ? ' / ' . $c->cantidad_max_usos : '' }}</td>
                        <td class="px-4 py-2.5 text-xs">{{ ucfirst($c->aplica_a) }}</td>
                        <td class="px-4 py-2.5 text-xs">
                            <span class="rounded-full px-1.5 py-0.5 text-xs {{ $c->estado === 'activo' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-zinc-100 dark:bg-zinc-700' }}">{{ ucfirst($c->estado) }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-xs">
                            <div class="flex gap-2">
                                <flux:button size="xs" variant="ghost" href="{{ route('cupones.show', $c) }}" wire:navigate>Ver</flux:button>
                                @can('cupones.update')
                                <flux:button size="xs" variant="ghost" href="{{ route('cupones.edit', $c) }}" wire:navigate>Editar</flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay cupones</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex justify-end">{{ $coupons->links() }}</div>
</div>
