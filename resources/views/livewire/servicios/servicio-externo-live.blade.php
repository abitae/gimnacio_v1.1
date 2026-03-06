<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Servicios Externos</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra los servicios externos</p>
            </div>
            @can('servicios.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                Nuevo Servicio
            </flux:button>
            @endcan
        </div>

        <div class="flex gap-3 items-center justify-end">
            <div class="w-full">
            </div>
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="Buscar..." />
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium">Código</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Precio</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Duración</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @forelse ($servicios as $servicio)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-4 py-2.5 text-xs font-medium">{{ $servicio->codigo }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $servicio->nombre }}</td>
                                <td class="px-4 py-2.5 text-xs">S/ {{ number_format($servicio->precio, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $servicio->duracion_minutos ? $servicio->duracion_minutos . ' min' : '-' }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $servicio->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($servicio->estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    @can('servicios.update')
                                    <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $servicio->id }})">Editar</flux:button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-zinc-500">No hay servicios</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-end">{{ $servicios->links() }}</div>
    </div>

    <!-- Modal Create/Edit -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold">{{ $servicioId ? 'Editar' : 'Nuevo' }} Servicio</h2>
                
                <flux:field>
                    <flux:label>Código</flux:label>
                    <flux:input wire:model="formData.codigo" />
                </flux:field>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="formData.nombre" />
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="formData.descripcion" rows="3" />
                </flux:field>

                <flux:field>
                    <flux:label>Precio</flux:label>
                    <flux:input type="number" step="0.01" wire:model="formData.precio" />
                </flux:field>

                <flux:field>
                    <flux:label>Duración (minutos)</flux:label>
                    <flux:input type="number" wire:model="formData.duracion_minutos" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="formData.estado" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
