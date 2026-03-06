@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Productos</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra el catálogo de productos</p>
            </div>
            @can('productos.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal">
                Nuevo Producto
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
                <select wire:model.live="categoriaFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs">
                    <option value="">Todas las categorías</option>
                    @foreach ($categorias as $categoria)
                        <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter" class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium">Imagen</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Código</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Nombre</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Categoría</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Precio</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Stock</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200">
                        @forelse ($productos as $producto)
                            <tr class="hover:bg-zinc-50">
                                <td class="px-4 py-2.5">
                                    @if ($producto->imagen)
                                        <img src="{{ Storage::url($producto->imagen) }}" alt="{{ $producto->nombre }}" class="w-12 h-12 object-cover rounded">
                                    @else
                                        <div class="w-12 h-12 bg-zinc-200 dark:bg-zinc-700 rounded flex items-center justify-center">
                                            <span class="text-xs text-zinc-400">Sin imagen</span>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-2.5 text-xs font-medium">{{ $producto->codigo }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $producto->nombre }}</td>
                                <td class="px-4 py-2.5 text-xs">{{ $producto->categoria->nombre ?? '-' }}</td>
                                <td class="px-4 py-2.5 text-xs">S/ {{ number_format($producto->precio_venta, 2) }}</td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="{{ $producto->stockBajo() ? 'text-red-600' : '' }}">
                                        {{ $producto->stock_actual }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $producto->estado === 'activo' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ ucfirst($producto->estado) }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-xs">
                                    <div class="flex gap-2">
                                        @can('productos.update')
                                        <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $producto->id }})">Editar</flux:button>
                                        <flux:button size="xs" variant="ghost" wire:click="openImageModal({{ $producto->id }})">Imagen</flux:button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-8 text-center text-xs text-zinc-500">No hay productos</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex justify-end">{{ $productos->links() }}</div>
    </div>

    <!-- Modal Create/Edit -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold">{{ $productoId ? 'Editar' : 'Nuevo' }} Producto</h2>
                
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
                    <flux:label>Categoría</flux:label>
                    <select wire:model="formData.categoria_id" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2">
                        <option value="">Sin categoría</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </flux:field>

                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Precio Venta</flux:label>
                        <flux:input type="number" step="0.01" wire:model="formData.precio_venta" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Precio Compra</flux:label>
                        <flux:input type="number" step="0.01" wire:model="formData.precio_compra" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <flux:field>
                        <flux:label>Stock Actual</flux:label>
                        <flux:input type="number" wire:model="formData.stock_actual" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Stock Mínimo</flux:label>
                        <flux:input type="number" wire:model="formData.stock_minimo" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Unidad de Medida</flux:label>
                    <flux:input wire:model="formData.unidad_medida" />
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

    <!-- Modal Imagen -->
    <flux:modal name="image-modal" wire:model="modalState.image" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="uploadImage">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold">Subir Imagen del Producto</h2>
                
                @if ($currentImagen)
                    <div class="mb-4">
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-2">Imagen actual:</p>
                        <img src="{{ Storage::url($currentImagen) }}" alt="Imagen actual" class="w-full h-48 object-cover rounded-lg">
                    </div>
                @endif

                <flux:field>
                    <flux:label>Seleccionar Imagen</flux:label>
                    <flux:input type="file" wire:model="imagen" accept="image/jpeg,image/jpg,image/png,image/webp" />
                    <flux:error name="imagen" />
                    @if ($imagen)
                        <p class="mt-1 text-xs text-zinc-500">Archivo seleccionado: {{ $imagen->getClientOriginalName() }}</p>
                    @endif
                </flux:field>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="uploadImage">Subir Imagen</span>
                        <span wire:loading wire:target="uploadImage">Subiendo...</span>
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>
</div>
