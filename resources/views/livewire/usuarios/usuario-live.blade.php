<div class="space-y-3 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Usuarios</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra usuarios y asignación de roles</p>
            </div>
            @can('usuarios.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                Nuevo usuario
            </flux:button>
            @endcan
        </div>

        <div class="flex gap-3 items-center justify-end">
            <div class="w-full">
            </div>
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="search" placeholder="Buscar..." class="w-full"
                    aria-label="Buscar usuarios" />
            </div>
            <div class="w-40">
                <select wire:model.live="roleFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Filtrar por rol">
                    <option value="">Todos los roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->name }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $role->name)) }}</option>
                    @endforeach
                </select>
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Email</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Rol</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($usuarios as $u)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $u->name }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $u->email }}</td>
                                <td class="px-4 py-2.5">
                                    @if ($u->roles->isNotEmpty())
                                        @php $role = $u->roles->first(); @endphp
                                        <span class="inline-flex rounded-full bg-purple-100 px-2 py-0.5 text-xs text-purple-800 dark:bg-purple-900/30 dark:text-purple-300">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $role->name)) }}</span>
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $u->estado === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ $u->estado ?? 'activo' }}</span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <div class="flex gap-2">
                                        @can('usuarios.update')
                                        <flux:button size="xs" variant="ghost" icon="pencil" wire:click="openEditModal({{ $u->id }})" aria-label="Editar">Editar</flux:button>
                                        @endcan
                                        @if ($u->id !== auth()->id())
                                            @can('usuarios.delete')
                                            <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="openDeleteModal({{ $u->id }})" aria-label="Eliminar">Eliminar</flux:button>
                                            @endcan
                                        @endif
                                    </div>
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
        </div>

        @if ($usuarios->hasPages())
            <div class="mt-4 flex justify-end">{{ $usuarios->links() }}</div>
        @endif
    </div>

    <!-- Modal Create/Edit -->
    <flux:modal name="usuario-form" wire:model="modalState.form" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $userId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="formData.name" placeholder="Nombre completo" />
                    <flux:error name="formData.name" />
                </flux:field>
                <flux:field>
                    <flux:label>Email</flux:label>
                    <flux:input type="email" wire:model="formData.email" placeholder="email@ejemplo.com" />
                    <flux:error name="formData.email" />
                </flux:field>
                <flux:field>
                    <flux:label>Contraseña {{ $userId ? '(dejar en blanco para no cambiar)' : '' }}</flux:label>
                    <flux:input type="password" wire:model="formData.password" placeholder="••••••••" />
                    <flux:error name="formData.password" />
                </flux:field>
                @if (!$userId)
                    <flux:field>
                        <flux:label>Confirmar contraseña</flux:label>
                        <flux:input type="password" wire:model="formData.password_confirmation" placeholder="••••••••" />
                    </flux:field>
                @endif
                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="formData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Rol</flux:label>
                    <select wire:model="formData.role"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        required>
                        <option value="">Seleccionar rol</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ \Illuminate\Support\Str::title(str_replace('_', ' ', $role->name)) }}</option>
                        @endforeach
                    </select>
                    <flux:error name="formData.role" />
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    @can('usuarios.delete')
    <!-- Modal Delete -->
    <flux:modal name="usuario-delete" wire:model="modalState.delete" focusable flyout variant="floating" class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Eliminar usuario</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                ¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.
            </p>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                <flux:button color="red" variant="primary" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
    @endcan
</div>
