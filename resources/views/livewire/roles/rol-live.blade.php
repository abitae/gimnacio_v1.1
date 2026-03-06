<div class="space-y-3 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Roles</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra roles y permisos</p>
            </div>
            @can('roles.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                Nuevo rol
            </flux:button>
            @endcan
        </div>

        <div class="flex gap-3 items-center justify-end">
            <div class="w-full">
            </div>
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="search" placeholder="Buscar..." class="w-full"
                    aria-label="Buscar roles" />
            </div>
            <div class="w-28">
                <select wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Elementos por página">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Guard</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Permisos</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Usuarios</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($roles as $role)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $role->name)) }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $role->guard_name }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $role->permissions_count }} permisos</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $role->users_count ?? 0 }} usuarios</td>
                                <td class="px-4 py-2.5 text-xs">
                                    <div class="flex gap-2">
                                        @can('roles.update')
                                        <flux:button size="xs" variant="ghost" icon="pencil" wire:click="openEditModal({{ $role->id }})" aria-label="Editar">Editar</flux:button>
                                        @endcan
                                        @can('roles.delete')
                                        @if (($role->users_count ?? 0) === 0)
                                            <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="openDeleteModal({{ $role->id }})" aria-label="Eliminar">Eliminar</flux:button>
                                        @else
                                            <flux:button size="xs" variant="ghost" color="red" icon="trash" disabled title="No se puede eliminar: tiene usuarios asignados">Eliminar</flux:button>
                                        @endif
                                        @endcan
                                    </div>
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
        </div>

        @if ($roles->hasPages())
            <div class="mt-4 flex justify-end">{{ $roles->links() }}</div>
        @endif
    </div>

    <!-- Modal Create/Edit -->
    <flux:modal name="rol-form" wire:model="modalState.form" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $roleId ? 'Editar rol' : 'Nuevo rol' }}</h2>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="formData.name" placeholder="ej: editor" />
                    <flux:error name="formData.name" />
                </flux:field>
                <flux:field>
                    <flux:label>Guard</flux:label>
                    <select wire:model="formData.guard_name"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="web">web</option>
                        <option value="api">api</option>
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Permisos</flux:label>
                    <div class="max-h-64 overflow-y-auto space-y-4 rounded border border-zinc-200 dark:border-zinc-600 p-3">
                        @php
                            $grouped = $permissions->groupBy(function ($p) {
                                $parts = explode('.', $p->name);
                                return $parts[0] ?? 'otros';
                            });
                        @endphp
                        @foreach ($grouped as $recurso => $perms)
                            <div class="space-y-1.5">
                                <p class="text-xs font-semibold text-zinc-600 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-600 pb-1">
                                    {{ \Illuminate\Support\Str::title(str_replace(['-', '_'], ' ', $recurso)) }}
                                </p>
                                @foreach ($perms as $perm)
                                    <label class="flex items-center gap-2">
                                        <flux:checkbox wire:model="formData.permissions" value="{{ $perm->name }}" />
                                        <span class="text-sm">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $perm->name)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endforeach
                        @if ($permissions->isEmpty())
                            <p class="text-xs text-zinc-500">No hay permisos creados.</p>
                        @endif
                    </div>
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    @can('roles.delete')
    <!-- Modal Delete -->
    <flux:modal name="rol-delete" wire:model="modalState.delete" focusable flyout variant="floating" class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Eliminar rol</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                ¿Estás seguro de que deseas eliminar este rol? Los usuarios perderán este rol. Esta acción no se puede deshacer.
            </p>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                <flux:button color="red" variant="primary" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
    @endcan
</div>
