<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Clases</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra las clases del gimnasio</p>
            </div>
            @can('clases.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal"
                wire:loading.attr="disabled" wire:target="openCreateModal" aria-label="Crear nueva clase">
                <span wire:loading.remove wire:target="openCreateModal">Nueva Clase</span>
                <span wire:loading wire:target="openCreateModal">Cargando...</span>
            </flux:button>
            @endcan
        </div>

        <!-- Search and Filters -->
        <div class="flex gap-3 items-center justify-end">
            <div class="w-full">
            </div>
            <div class="w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="search" placeholder="Buscar..." class="w-full"
                    aria-label="Buscar clases" />
            </div>
            <div class="w-32">
                <select wire:model.live="tipoFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Filtrar por tipo">
                    <option value="">Todos</option>
                    <option value="sesion">Por Sesión</option>
                    <option value="paquete">Por Paquete</option>
                </select>
            </div>
            <div class="w-40">
                <select wire:model.live="instructorFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Filtrar por instructor">
                    <option value="">Todos los instructores</option>
                    @foreach ($instructores as $instructor)
                        <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Filtrar por estado">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
            <div class="w-28">
                <select wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Elementos por página">
                    <option value="10">10</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <!-- Table and Detail Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Table -->
            <div
                class="lg:col-span-2 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Código
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Nombre
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Tipo
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Precio
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Instructor
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                            @forelse ($clases as $clase)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer {{ $selectedClase && $selectedClase->id === $clase->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                                    wire:click="selectClase({{ $clase->id }})" role="button" tabindex="0"
                                    aria-label="Seleccionar clase {{ $clase->nombre }}"
                                    @keydown.enter="selectClase({{ $clase->id }})"
                                    @keydown.space.prevent="selectClase({{ $clase->id }})">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-xs">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $clase->codigo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                        {{ $clase->nombre }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $clase->tipo === 'sesion' ? 'Por Sesión' : 'Por Paquete' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                        S/ {{ number_format($clase->obtenerPrecio(), 2) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $clase->instructor->name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs">
                                        <span
                                            class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $clase->estado === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' }}">
                                            {{ ucfirst($clase->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                        No hay clases
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detail Card -->
            <div class="lg:col-span-1">
                @if ($selectedClase)
                    <x-clase.detail-card :clase="$selectedClase" />
                @else
                    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                        <div class="flex flex-col items-center justify-center h-full min-h-[150px] text-center">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Selecciona una clase para ver su
                                detalle</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end">
            {{ $clases->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $claseId ? 'Editar Clase' : 'Nueva Clase' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $claseId ? 'Modifica la información de la clase' : 'Completa los datos de la nueva clase' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model="formData.codigo" label="Código" required />
                    <flux:error name="formData.codigo" />

                    <flux:input size="xs" wire:model="formData.nombre" label="Nombre" required />
                    <flux:error name="formData.nombre" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Descripción
                    </label>
                    <textarea wire:model="formData.descripcion" rows="2"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                    <flux:error name="formData.descripcion" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Tipo
                    </label>
                    <select wire:model.live="formData.tipo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="sesion">Por Sesión</option>
                        <option value="paquete">Por Paquete</option>
                    </select>
                    <flux:error name="formData.tipo" />
                </div>

                @if ($formData['tipo'] === 'sesion')
                    <div>
                        <flux:input size="xs" wire:model.number="formData.precio_sesion" label="Precio por Sesión (S/)"
                            type="number" step="0.01" min="0" required />
                        <flux:error name="formData.precio_sesion" />
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <flux:input size="xs" wire:model.number="formData.precio_paquete"
                                label="Precio del Paquete (S/)" type="number" step="0.01" min="0" required />
                            <flux:error name="formData.precio_paquete" />
                        </div>
                        <div>
                            <flux:input size="xs" wire:model.number="formData.sesiones_paquete"
                                label="Sesiones en el Paquete" type="number" min="1" required />
                            <flux:error name="formData.sesiones_paquete" />
                        </div>
                    </div>
                @endif

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Instructor
                    </label>
                    <select wire:model="formData.instructor_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Sin instructor</option>
                        @foreach ($instructores as $instructor)
                            <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                        @endforeach
                    </select>
                    <flux:error name="formData.instructor_id" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Estado
                    </label>
                    <select wire:model="formData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                    <flux:error name="formData.estado" />
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" size="xs" wire:click="closeModal" type="button">
                        Cancelar
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled"
                    wire:target="save">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="save" />
                        <span wire:loading.remove wire:target="save">{{ $claseId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @can('clases.delete')
    <!-- Delete Modal -->
    <flux:modal name="delete-modal" wire:model="modalState.delete" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Eliminar Clase
            </h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                ¿Estás seguro de que deseas eliminar esta clase? Esta acción no se puede deshacer.
            </p>
        </div>

        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" size="xs" wire:click="closeModal" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            <flux:button variant="danger" size="xs" wire:click="delete" type="button"
                wire:loading.attr="disabled" wire:target="delete">
                <span class="inline-flex items-center gap-1.5">
                <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="delete" />
                <span wire:loading.remove wire:target="delete">Eliminar</span>
                <span wire:loading wire:target="delete">Eliminando...</span>
            </span>
            </flux:button>
        </div>
    </flux:modal>
    @endcan

</div>
