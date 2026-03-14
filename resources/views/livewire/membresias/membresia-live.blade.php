<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Membresías</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra los planes de membresía del gimnasio</p>
            </div>
            @can('membresias.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal"
                wire:loading.attr="disabled" wire:target="openCreateModal" aria-label="Crear nueva membresía">
                <span wire:loading.remove wire:target="openCreateModal">Nueva Membresía</span>
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
                    aria-label="Buscar membresías" />
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Filtrar por estado">
                    <option value="">Todos</option>
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
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
                                    Nombre
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Duración
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Precio
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Tipo Acceso
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                            @forelse ($membresias as $membresia)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer {{ $selectedMembresia && $selectedMembresia->id === $membresia->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                                    wire:click="selectMembresia({{ $membresia->id }})" role="button" tabindex="0"
                                    aria-label="Seleccionar membresía {{ $membresia->nombre }}"
                                    @keydown.enter="selectMembresia({{ $membresia->id }})"
                                    @keydown.space.prevent="selectMembresia({{ $membresia->id }})">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-xs">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $membresia->nombre }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $membresia->duracion_dias }} días
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                        S/ {{ number_format($membresia->precio_base, 2) }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                        @if ($membresia->tipo_acceso === 'ilimitado')
                                            Ilimitado
                                        @elseif ($membresia->tipo_acceso === 'limitado')
                                            {{ $membresia->max_visitas_dia ?? 0 }}/día
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 text-xs">
                                        <span
                                            class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium
                                        @if ($membresia->estado === 'activa') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                                        @else
                                            bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400 @endif
                                    ">
                                            {{ ucfirst($membresia->estado) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                        No hay membresías
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Detail Card -->
            <div class="lg:col-span-1">
                @if ($selectedMembresia)
                    <x-membresia.detail-card :membresia="$selectedMembresia" />
                @else
                    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                        <div class="flex flex-col items-center justify-center h-full min-h-[150px] text-center">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Selecciona una membresía para ver su
                                detalle</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end">
            {{ $membresias->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $membresiaId ? 'Editar Membresía' : 'Nueva Membresía' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $membresiaId ? 'Modifica la información de la membresía' : 'Completa los datos de la nueva membresía' }}
                    </p>
                </div>

                <div>
                    <flux:input size="xs" wire:model="formData.nombre" label="Nombre" required />
<flux:error name="formData.nombre" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Descripción
                    </label>
                    <textarea wire:model="formData.descripcion" rows="3"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
<flux:error name="formData.descripcion" />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <flux:input size="xs" wire:model.number="formData.duracion_dias" label="Duración (días)"
                            type="number" min="1" required />
<flux:error name="formData.duracion_dias" />
                    </div>

                    <div>
                        <flux:input size="xs" wire:model.number="formData.precio_base" label="Precio Base (S/)"
                            type="number" step="0.01" min="0" required />
<flux:error name="formData.precio_base" />
                    </div>
                </div>

                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Configuración de Cuotas</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="formData.permite_cuotas"
                                class="rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="ml-2 text-xs text-zinc-700 dark:text-zinc-300">Permitir pago en cuotas</span>
                        </label>
                        @if ($formData['permite_cuotas'])
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <flux:input size="xs" wire:model.number="formData.numero_cuotas_default"
                                        label="Número de Cuotas" type="number" min="2" max="60" />
                                    <flux:error name="formData.numero_cuotas_default" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        Frecuencia de Cuotas
                                    </label>
                                    <select wire:model="formData.frecuencia_cuotas_default"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                        <option value="semanal">Semanal</option>
                                        <option value="quincenal">Quincenal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                    <flux:error name="formData.frecuencia_cuotas_default" />
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <flux:input size="xs" wire:model.number="formData.cuota_inicial_monto"
                                        label="Cuota Inicial (S/)" type="number" step="0.01" min="0" />
                                    <flux:error name="formData.cuota_inicial_monto" />
                                </div>
                                <div>
                                    <flux:input size="xs" wire:model.number="formData.cuota_inicial_porcentaje"
                                        label="Cuota Inicial (%)" type="number" step="0.01" min="0" max="100" />
                                    <flux:error name="formData.cuota_inicial_porcentaje" />
                                </div>
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                Define una cuota inicial por monto o por porcentaje, no ambas.
                            </p>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Tipo de Acceso
                    </label>
                    <select wire:model.live="formData.tipo_acceso"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="ilimitado">Ilimitado</option>
                        <option value="limitado">Limitado</option>
                    </select>
<flux:error name="formData.tipo_acceso" />
                </div>

                @if ($formData['tipo_acceso'] === 'limitado')
                    <div>
                        <flux:input size="xs" wire:model.number="formData.max_visitas_dia"
                            label="Máximo de Visitas por Día" type="number" min="1" />
<flux:error name="formData.max_visitas_dia" />
                    </div>
                @endif

                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Configuración de
                        Congelación</h3>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model.live="formData.permite_congelacion"
                                class="rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="ml-2 text-xs text-zinc-700 dark:text-zinc-300">Permitir congelación</span>
                        </label>
                        @if ($formData['permite_congelacion'])
                            <div>
                                <flux:input size="xs" wire:model.number="formData.max_dias_congelacion"
                                    label="Máximo de Días de Congelación" type="number" min="1" />
<flux:error name="formData.max_dias_congelacion" />
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Estado
                    </label>
                    <select wire:model="formData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="activa">Activa</option>
                        <option value="inactiva">Inactiva</option>
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
                        <span wire:loading.remove wire:target="save">{{ $membresiaId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @can('membresias.delete')
    <!-- Delete Modal -->
    <flux:modal name="delete-modal" wire:model="modalState.delete" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Eliminar Membresía
            </h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                ¿Estás seguro de que deseas eliminar esta membresía? Esta acción no se puede deshacer.
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
