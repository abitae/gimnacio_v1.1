<div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Publicidad app') }}</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ __('Imágenes que se muestran en el inicio de la app del cliente. Se publican en la sucursal activa.') }}
                </p>
            </div>
            @can('publicidad_app.crear')
                <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                    {{ __('Nueva publicidad') }}
                </flux:button>
            @endcan
        </div>

        <div class="flex items-center justify-end gap-3">
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="{{ __('Buscar…') }}" />
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="">{{ __('Todos') }}</option>
                    <option value="activo">{{ __('Activo') }}</option>
                    <option value="inactivo">{{ __('Inactivo') }}</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Imagen') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Título') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Orden') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Estado') }}</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Acciones') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($items as $item)
                            <tr wire:key="pub-{{ $item->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2.5">
                                    <img src="{{ asset('storage/'.$item->imagen) }}" alt="{{ $item->titulo }}"
                                        class="h-12 w-20 rounded-md border border-zinc-200 object-cover dark:border-zinc-600">
                                </td>
                                <td class="px-4 py-2.5 text-xs font-medium text-zinc-900 dark:text-zinc-100">{{ $item->titulo }}</td>
                                <td class="px-4 py-2.5 text-xs tabular-nums text-zinc-600 dark:text-zinc-400">{{ $item->orden }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $item->estado === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300' }}">
                                        {{ $item->estado === 'activo' ? __('Activo') : __('Inactivo') }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <x-ui.table-actions align="left">
                                        @can('publicidad_app.editar')
                                            <flux:button size="xs" variant="ghost" icon="{{ $item->estado === 'activo' ? 'pause' : 'play' }}" wire:click="toggleEstado({{ $item->id }})" title="{{ $item->estado === 'activo' ? __('Desactivar') : __('Activar') }}" />
                                            <flux:button size="xs" variant="ghost" icon="pencil" wire:click="openEditModal({{ $item->id }})" aria-label="{{ __('Editar') }}" />
                                        @endcan
                                        @can('publicidad_app.eliminar')
                                            <flux:button size="xs" variant="ghost" icon="trash" color="red" wire:click="openDeleteModal({{ $item->id }})" aria-label="{{ __('Eliminar') }}" />
                                        @endcan
                                    </x-ui.table-actions>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ __('Aún no hay publicidades. Crea una para mostrarla en el inicio de la app.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-end">{{ $items->links() }}</div>
    </div>

    <flux:modal name="publicidad-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $publicidadId ? __('Editar publicidad') : __('Nueva publicidad') }}
                </h2>

                <flux:field>
                    <flux:label>{{ __('Título') }}</flux:label>
                    <flux:input wire:model="formData.titulo" placeholder="{{ __('Promo de verano') }}" />
                    @error('formData.titulo')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ $publicidadId ? __('Imagen (opcional para reemplazar)') : __('Imagen') }}</flux:label>
                    <flux:input type="file" wire:model="imagen" accept="image/jpeg,image/jpg,image/png,image/webp" />
                    <p class="mt-1 text-[11px] text-zinc-500">{{ __('JPG, PNG o WEBP. Máximo 4 MB. Recomendado 16:9.') }}</p>
                    @error('imagen')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                    @if ($currentImagen)
                        <img src="{{ asset('storage/'.$currentImagen) }}" alt="" class="mt-2 h-24 w-full rounded-lg object-cover">
                    @endif
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Enlace (opcional)') }}</flux:label>
                    <flux:input wire:model="formData.enlace_url" placeholder="https://" />
                    @error('formData.enlace_url')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>{{ __('Orden') }}</flux:label>
                        <flux:input type="number" wire:model="formData.orden" min="0" />
                    </flux:field>
                    <flux:field>
                        <flux:label>{{ __('Estado') }}</flux:label>
                        <select wire:model="formData.estado" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                            <option value="activo">{{ __('Activo') }}</option>
                            <option value="inactivo">{{ __('Inactivo') }}</option>
                        </select>
                    </flux:field>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" type="button" wire:click="closeModal">{{ __('Cancelar') }}</flux:button>
                    <flux:button type="submit">{{ __('Guardar') }}</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    @can('publicidad_app.eliminar')
        <flux:modal name="publicidad-delete-modal" wire:model="modalState.delete" focusable flyout variant="floating" class="md:w-lg">
            <div class="p-4">
                <h2 class="mb-4 text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Eliminar publicidad') }}</h2>
                <p class="mb-4 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ __('Se quitará del inicio de la app y se eliminará la imagen.') }}
                </p>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeModal">{{ __('Cancelar') }}</flux:button>
                    <flux:button color="red" variant="primary" wire:click="delete">{{ __('Eliminar') }}</flux:button>
                </div>
            </div>
        </flux:modal>
    @endcan
</div>
