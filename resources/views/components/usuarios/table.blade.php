@props(['usuarios', 'roles'])

@php
    $roleLabel = fn ($name) => \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
@endphp

<div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nombre</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Email</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Roles</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($usuarios as $u)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $u->name }}</td>
                    <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $u->email }}</td>
                    <td class="px-4 py-2.5">
                        @foreach ($u->roles as $role)
                            <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">{{ $roleLabel($role->name) }}</span>
                        @endforeach
                        @if ($u->roles->isEmpty())
                            <span class="text-zinc-400">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-2.5">
                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $u->estado === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ $u->estado ?? 'activo' }}</span>
                    </td>
                    <td class="px-4 py-2.5 text-right">
                        @can('usuarios.update')
                        <flux:button variant="ghost" size="xs" icon="pencil" wire:click="openEditModal({{ $u->id }})" aria-label="Editar" />
                        @endcan
                        @if ($u->id !== auth()->id())
                            @can('usuarios.delete')
                            <flux:button variant="ghost" size="xs" icon="trash" color="red" wire:click="openDeleteModal({{ $u->id }})" aria-label="Eliminar" />
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No hay usuarios</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
