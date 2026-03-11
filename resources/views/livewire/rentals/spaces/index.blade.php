<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Espacios para alquiler</h1>
        @can('rentals.create')
        <flux:button size="xs" wire:click="openCreateModal">Nuevo espacio</flux:button>
        @endcan
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Nombre</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Capacidad</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($spaces as $s)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $s->nombre }}</td>
                        <td class="px-4 py-2">{{ $s->capacidad ?? '—' }}</td>
                        <td class="px-4 py-2">
                            @can('rentals.update')
                            <button type="button" wire:click="toggleEstado({{ $s->id }})" class="rounded-full px-1.5 py-0.5 text-xs {{ $s->estado === 'activo' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-zinc-100 dark:bg-zinc-700' }}">
                                {{ ucfirst($s->estado) }}
                            </button>
                            @else
                            <span class="rounded-full px-1.5 py-0.5 text-xs {{ $s->estado === 'activo' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-zinc-100 dark:bg-zinc-700' }}">{{ ucfirst($s->estado) }}</span>
                            @endcan
                        </td>
                        <td class="px-4 py-2">
                            @can('rentals.update')
                            <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $s->id }})">Editar</flux:button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">No hay espacios</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $spaces->links() }}

    @canany(['rentals.create', 'rentals.update'])
    <flux:modal name="create-edit-space-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $spaceId ? 'Editar espacio' : 'Nuevo espacio' }}</h2>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="formData.nombre" required />
                    @error('formData.nombre')
                    <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Capacidad</flux:label>
                    <flux:input type="number" min="0" wire:model="formData.capacidad" />
                    @error('formData.capacidad')
                    <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea wire:model="formData.descripcion" rows="2" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="formData.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                </flux:field>

                <flux:field>
                    <flux:label>Color calendario</flux:label>
                    <select wire:model="formData.color_calendario" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2 text-sm">
                        @foreach(\App\Models\Core\RentableSpace::COLORES_CALENDARIO as $hex => $nombre)
                            <option value="{{ $hex }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <div class="mt-1 h-6 w-12 rounded border border-zinc-300 dark:border-zinc-600" style="background-color: {{ $formData['color_calendario'] ?? '#3B82F6' }}"></div>
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:modal.close>
                        <flux:button variant="ghost" type="button" wire:click="closeModal">Cancelar</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" wire:loading.attr="disabled">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
    @endcanany
</div>
