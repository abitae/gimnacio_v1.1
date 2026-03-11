<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Métodos de pago</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Configura los métodos de pago para ventas, matrículas y cobros</p>
            </div>
            @can('payment-methods.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                Nuevo método
            </flux:button>
            @endcan
        </div>

        <div class="flex gap-3 items-center justify-end">
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="Buscar..." />
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
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Descripción</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Nº operación</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Entidad</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($paymentMethods as $method)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2.5 text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ $method->nombre }}</td>
                                <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($method->descripcion, 40) ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    @if($method->requiere_numero_operacion)
                                        <span class="text-amber-600 dark:text-amber-400">Sí</span>
                                    @else
                                        <span class="text-zinc-400">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    @if($method->requiere_entidad)
                                        <span class="text-amber-600 dark:text-amber-400">Sí</span>
                                    @else
                                        <span class="text-zinc-400">No</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $method->estado === 'activo' ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-300' }}">
                                        {{ $method->estado === 'activo' ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <div class="flex gap-2">
                                        @can('payment-methods.update')
                                        <flux:button size="xs" variant="ghost" wire:click="toggleEstado({{ $method->id }})">
                                            {{ $method->estado === 'activo' ? 'Desactivar' : 'Activar' }}
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $method->id }})">Editar</flux:button>
                                        @endcan
                                        @can('payment-methods.delete')
                                        <flux:button size="xs" variant="ghost" color="red" wire:click="openDeleteModal({{ $method->id }})">Eliminar</flux:button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay métodos de pago</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-end">{{ $paymentMethods->links() }}</div>
    </div>

    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $paymentMethodId ? 'Editar' : 'Nuevo' }} método de pago</h2>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="formData.nombre" placeholder="Ej: Efectivo, Yape" />
                    @error('formData.nombre')
                    <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="formData.descripcion" rows="2" placeholder="Opcional" />
                </flux:field>

                <div class="flex gap-4">
                    <flux:field class="flex items-center gap-2">
                        <flux:checkbox wire:model="formData.requiere_numero_operacion" />
                        <flux:label>Requiere número de operación</flux:label>
                    </flux:field>
                    <flux:field class="flex items-center gap-2">
                        <flux:checkbox wire:model="formData.requiere_entidad" />
                        <flux:label>Requiere entidad financiera</flux:label>
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="formData.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" type="button" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    @can('payment-methods.delete')
    <flux:modal name="delete-modal" wire:model="modalState.delete" focusable flyout variant="floating" class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Eliminar método de pago</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
                ¿Estás seguro de que deseas eliminar este método de pago? Se usará borrado lógico y no se perderán historiales.
            </p>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                <flux:button color="red" variant="primary" wire:click="delete">Eliminar</flux:button>
            </div>
        </div>
    </flux:modal>
    @endcan
</div>
