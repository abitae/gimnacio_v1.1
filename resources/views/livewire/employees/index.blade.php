<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Personal</h1>
        @can('employees.create')
        <flux:button size="xs" href="{{ route('employees.create') }}" wire:navigate>Nuevo empleado</flux:button>
        @endcan
    </div>
    <div class="flex gap-2">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar..." />
        <select wire:model.live="estadoFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-32">
            <option value="">Todos</option>
            <option value="activo">Activo</option>
            <option value="inactivo">Inactivo</option>
        </select>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Nombre</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Documento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cargo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($employees as $e)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $e->nombre_completo }}</td>
                        <td class="px-4 py-2">{{ $e->documento }}</td>
                        <td class="px-4 py-2">{{ $e->cargo ?? '—' }}</td>
                        <td class="px-4 py-2"><span class="rounded-full px-1.5 py-0.5 text-xs {{ $e->estado === 'activo' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-zinc-100 dark:bg-zinc-700' }}">{{ ucfirst($e->estado) }}</span></td>
                        <td class="px-4 py-2">
                            <flux:button size="xs" variant="ghost" href="{{ route('employees.show', $e) }}" wire:navigate>Ver</flux:button>
                            @can('employees.update')
                            <flux:button size="xs" variant="ghost" href="{{ route('employees.edit', $e) }}" wire:navigate>Editar</flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500">No hay empleados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $employees->links() }}
</div>
