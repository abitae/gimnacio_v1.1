@props(['roles'])

@php
    $roleLabel = fn ($name) => \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
@endphp

<div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nombre</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Guard</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Permisos</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Usuarios</th>
                <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse ($roles as $role)
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $roleLabel($role->name) }}</td>
                    <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $role->guard_name }}</td>
                    <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $role->permissions_count }} permisos</td>
                    <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $role->users_count ?? 0 }} usuarios</td>
                    <td class="px-4 py-2.5 text-right">
                        @can('roles.update')
                        <flux:button variant="ghost" size="xs" icon="pencil" wire:click="openEditModal({{ $role->id }})" aria-label="Editar" />
                        @endcan
                        @can('roles.delete')
                        @if (($role->users_count ?? 0) === 0)
                            <flux:button variant="ghost" size="xs" icon="trash" color="red" wire:click="openDeleteModal({{ $role->id }})" aria-label="Eliminar" />
                        @else
                            <flux:button variant="ghost" size="xs" icon="trash" color="red" disabled title="No se puede eliminar: tiene usuarios asignados" aria-label="Eliminar (deshabilitado)" />
                        @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No hay roles</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
