<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Categorías de Productos</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra las categorías de productos</p>
            </div>
            @can('categorias-productos.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                Nueva Categoría
            </flux:button>
            @endcan
        </div>

        <div class="flex gap-3 items-center justify-end">
            <div class="w-full">
            </div>
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="Buscar..." />
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs">
                    <option value="">Todos</option>
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Descripción</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Productos</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @forelse ($categorias as $categoria)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-4 py-2.5 text-xs font-medium">{{ $categoria->nombre }}</td>
                                <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $categoria->descripcion ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $categoria->productos_count }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $categoria->estado === 'activa' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($categoria->estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <div class="flex gap-2">
                                        @can('categorias-productos.update')
                                        <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $categoria->id }})">Editar</flux:button>
                                        @endcan
                                        @can('categorias-productos.delete')
                                        <flux:button size="xs" variant="ghost" color="red" wire:click="openDeleteModal({{ $categoria->id }})">Eliminar</flux:button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-xs text-zinc-500">No hay categorías</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-end">{{ $categorias->links() }}</div>
    </div>

    <!-- Modal Create/Edit -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold">{{ $categoriaId ? 'Editar' : 'Nueva' }} Categoría</h2>
                
                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="formData.nombre" />
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="formData.descripcion" rows="3" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="formData.estado" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2">
                        <option value="activa">Activa</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    @can('categorias-productos.delete')
    <!-- Modal Delete -->
    <flux:modal name="delete-modal" wire:model="modalState.delete" focusable flyout variant="floating" class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Eliminar Categoría</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                ¿Estás seguro de que deseas eliminar esta categoría? Esta acción no se puede deshacer.
            </p>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                <flux:button color="red" variant="primary" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
    @endcan
</div>
