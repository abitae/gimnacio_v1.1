<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Membresías de Clientes</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Gestiona las membresías asignadas a los clientes</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('clientes.index') }}" wire:navigate>
                    <flux:button icon="plus" color="blue" variant="primary" size="xs" aria-label="Crear nuevo cliente">
                        Nuevo Cliente
                    </flux:button>
                </a>
            </div>
        </div>

        <!-- Flash Messages -->
        <div class="w-full">
        </div>

        <!-- Cliente Search -->
        <div class="space-y-2">
            <div class="flex items-center justify-between">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    Buscar Cliente
                </label>
            </div>
            <div class="relative">
                <div class="relative">
                    <flux:input icon="magnifying-glass" type="search" size="xs"
                        wire:model.live.debounce.500ms="clienteSearch" placeholder="Buscar por nombre, documento o email..."
                        class="w-full" aria-label="Buscar cliente" />
                    
                    @if ($isSearching)
                        <div class="absolute right-2 top-1/2 -translate-y-1/2">
                            <svg class="animate-spin h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                    @endif
                </div>
                
                @if ($clienteSearch && !$selectedCliente && !$isSearching)
                    @if ($clientes->count() > 0)
                        <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 max-h-60 overflow-y-auto">
                            @foreach ($clientes as $cliente)
                                <button type="button"
                                    wire:click="selectCliente({{ $cliente->id }})"
                                    class="w-full px-4 py-2 text-left text-xs hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:bg-zinc-50 dark:focus:bg-zinc-700 focus:outline-none transition-colors">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                    </div>
                                    <div class="text-zinc-500 dark:text-zinc-400">
                                        {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                                        @if ($cliente->email)
                                            <span class="ml-2">• {{ $cliente->email }}</span>
                                        @endif
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @elseif (strlen(trim($clienteSearch)) >= 2)
                        <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 p-4">
                            <p class="text-xs text-center text-zinc-500 dark:text-zinc-400">
                                No se encontraron clientes
                            </p>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Two Column Layout -->
        @if ($selectedCliente)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left Column: Cliente Information -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3 space-y-2.5">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-2">
                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Información del Cliente</h3>
                            <flux:button variant="ghost" size="xs" wire:click="clearClienteSelection" aria-label="Limpiar selección">
                                ✕
                            </flux:button>
                        </div>

                        <!-- Foto -->
                        @if ($selectedCliente->foto)
                            <div class="flex justify-center">
                                <img src="{{ asset('storage/' . $selectedCliente->foto) }}" alt="Foto del cliente"
                                    class="w-16 h-16 rounded-full object-cover border border-zinc-200 dark:border-zinc-700">
                            </div>
                        @else
                            <div class="flex justify-center">
                                <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                    <span class="text-lg text-zinc-500 dark:text-zinc-400">
                                        {{ strtoupper(substr($selectedCliente->nombres, 0, 1) . substr($selectedCliente->apellidos, 0, 1)) }}
                                    </span>
                                </div>
                            </div>
                        @endif

                        <!-- Información Básica -->
                        <div class="space-y-1.5">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre</p>
                                <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $selectedCliente->nombres }} {{ $selectedCliente->apellidos }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Documento</p>
                                <p class="text-xs text-zinc-900 dark:text-zinc-100">
                                    {{ $selectedCliente->tipo_documento }}: {{ $selectedCliente->numero_documento }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
                                <span
                                    class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium
                                    @if ($selectedCliente->estado_cliente === 'activo') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                                    @elseif($selectedCliente->estado_cliente === 'inactivo')
                                        bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                                    @else
                                        bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 @endif
                                ">
                                    {{ ucfirst($selectedCliente->estado_cliente) }}
                                </span>
                            </div>

                            <x-cliente.info-field label="Teléfono" :value="$selectedCliente->telefono" />
                            <x-cliente.info-field label="Email" :value="$selectedCliente->email" />
                            <x-cliente.info-field label="Dirección" :value="$selectedCliente->direccion" />
                            @if ($selectedCliente->biotime_state)
                                <x-cliente.info-field label="BioTime" value="Sincronizado" />
                            @endif
                        </div>

                        <!-- Datos de Salud -->
                        @if ($selectedCliente->datos_salud)
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Salud</p>
                                <div class="space-y-1 text-xs">
                                    @if ($selectedCliente->datos_salud['alergias'] ?? null)
                                        <div>
                                            <span class="text-zinc-500 dark:text-zinc-400">Alergias: </span>
                                            <span class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente->datos_salud['alergias'] }}</span>
                                        </div>
                                    @endif
                                    @if ($selectedCliente->datos_salud['medicamentos'] ?? null)
                                        <div>
                                            <span class="text-zinc-500 dark:text-zinc-400">Medicamentos: </span>
                                            <span class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente->datos_salud['medicamentos'] }}</span>
                                        </div>
                                    @endif
                                    @if ($selectedCliente->datos_salud['lesiones'] ?? null)
                                        <div>
                                            <span class="text-zinc-500 dark:text-zinc-400">Lesiones: </span>
                                            <span class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente->datos_salud['lesiones'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Contacto de Emergencia -->
                        @if ($selectedCliente->datos_emergencia)
                            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Emergencia</p>
                                <div class="space-y-1 text-xs">
                                    @if ($selectedCliente->datos_emergencia['nombre_contacto'] ?? null)
                                        <div>
                                            <span class="text-zinc-500 dark:text-zinc-400">Nombre: </span>
                                            <span class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente->datos_emergencia['nombre_contacto'] }}</span>
                                        </div>
                                    @endif
                                    @if ($selectedCliente->datos_emergencia['telefono_contacto'] ?? null)
                                        <div>
                                            <span class="text-zinc-500 dark:text-zinc-400">Teléfono: </span>
                                            <span class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente->datos_emergencia['telefono_contacto'] }}</span>
                                        </div>
                                    @endif
                                    @if ($selectedCliente->datos_emergencia['relacion'] ?? null)
                                        <div>
                                            <span class="text-zinc-500 dark:text-zinc-400">Relación: </span>
                                            <span class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente->datos_emergencia['relacion'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Right Column: Membresías -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Filters and Actions -->
                    <div class="flex gap-3 items-center justify-between">
                        <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal"
                            wire:loading.attr="disabled" wire:target="openCreateModal" aria-label="Nueva membresía">
                            <span wire:loading.remove wire:target="openCreateModal">Agregar Membresía</span>
                            <span wire:loading wire:target="openCreateModal">Cargando...</span>
                        </flux:button>

                        <div class="flex gap-3 items-center">
                            <div class="w-32">
                                <select wire:model.live="estadoFilter"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                    aria-label="Filtrar por estado">
                                    <option value="">Todos</option>
                                    <option value="activa">Activa</option>
                                    <option value="vencida">Vencida</option>
                                    <option value="cancelada">Cancelada</option>
                                    <option value="congelada">Congelada</option>
                                </select>
                            </div>
                            <div class="w-28">
                                <select wire:model.live="perPage"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                    aria-label="Elementos por página">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Membresías Table -->
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Membresía
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Fecha Inicio
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Fecha Fin
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Precio Final
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Saldo
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Estado
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                    @forelse ($membresias as $membresia)
                                        <tr>
                                            <td class="px-4 py-2.5 text-xs">
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $membresia->membresia->nombre ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $membresia->fecha_inicio->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $membresia->fecha_fin->format('d/m/Y') }}
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                                S/ {{ number_format($membresia->precio_final, 2) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-xs">
                                                @php
                                                    $ultimoPago = $membresia->pagos->sortByDesc('created_at')->first();
                                                    $saldoPendiente = $ultimoPago ? (float) $ultimoPago->saldo_pendiente : (float) $membresia->precio_final;
                                                @endphp
                                                <div class="space-y-0.5">
                                                    <div class="text-zinc-900 dark:text-zinc-100">
                                                        <span class="text-zinc-500 dark:text-zinc-400 text-[10px]">Monto a Pagar:</span>
                                                        <span class="font-medium"> S/ {{ number_format($membresia->precio_final, 2) }}</span>
                                                    </div>
                                                    <div class="text-zinc-900 dark:text-zinc-100">
                                                        <span class="text-zinc-500 dark:text-zinc-400 text-[10px]">Saldo Pendiente:</span>
                                                        <span class="font-medium {{ $saldoPendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                            S/ {{ number_format($saldoPendiente, 2) }}
                                                        </span>
                                                    </div>
                                                    @if ($ultimoPago && $ultimoPago->metodo_pago && $ultimoPago->metodo_pago !== 'efectivo')
                                                        <div class="text-zinc-500 dark:text-zinc-400 text-[10px]">
                                                            Último: {{ ucfirst($ultimoPago->metodo_pago) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs">
                                                <span
                                                    class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium
                                                @if ($membresia->estado === 'activa') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                                                @elseif($membresia->estado === 'vencida')
                                                    bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400
                                                @elseif($membresia->estado === 'cancelada')
                                                    bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                                                @else
                                                    bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400 @endif
                                            ">
                                                    {{ ucfirst($membresia->estado) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs">
                                                <div class="flex gap-1">
                                                    <flux:button variant="ghost" size="xs" icon="pencil"
                                                        wire:click="openEditModal({{ $membresia->id }})" aria-label="Editar">
                                                    </flux:button>
                                                    <flux:button variant="ghost" size="xs" icon="trash" color="red"
                                                        wire:click="openDeleteModal({{ $membresia->id }})" aria-label="Eliminar">
                                                    </flux:button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7"
                                                class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                                No se encontraron membresías para este cliente
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if ($membresias->hasPages())
                        <div class="mt-4 flex justify-end">
                            {{ $membresias->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8">
                <div class="flex flex-col items-center justify-center text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Busca y selecciona un cliente para ver sus membresías
                    </p>
                </div>
            </div>
        @endif
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $clienteMembresiaId ? 'Editar Membresía' : 'Nueva Membresía' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $clienteMembresiaId ? 'Modifica la información de la membresía' : 'Asigna una nueva membresía al cliente' }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Membresía <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="formData.membresia_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Selecciona una membresía</option>
                        @foreach ($membresiasActivas as $membresia)
                            <option value="{{ $membresia->id }}">{{ $membresia->nombre }} - S/
                                {{ number_format($membresia->precio_base, 2) }}</option>
                        @endforeach
                    </select>
                    <flux:error name="formData.membresia_id" />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <flux:input size="xs" wire:model="formData.fecha_inicio" label="Fecha Inicio" type="date"
                            required />
                        <flux:error name="formData.fecha_inicio" />
                    </div>

                    <div>
                        <flux:input size="xs" wire:model="formData.fecha_fin" label="Fecha Fin" type="date" required />
                        <flux:error name="formData.fecha_fin" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <flux:input size="xs" wire:model.live.number="formData.precio_lista" label="Precio Lista (S/)"
                            type="number" step="0.01" min="0" required />
                        <flux:error name="formData.precio_lista" />
                    </div>

                    <div>
                        <flux:input size="xs" wire:model.live.number="formData.descuento_monto" label="Descuento (S/)"
                            type="number" step="0.01" min="0" />
                        <flux:error name="formData.descuento_monto" />
                    </div>

                    <div>
                        <flux:input size="xs" wire:model.number="formData.precio_final" label="Precio Final (S/)"
                            type="number" step="0.01" min="0" readonly />
                        @error('formData.precio_final')
                            <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Estado
                        </label>
                        <select wire:model="formData.estado"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="activa">Activa</option>
                            <option value="vencida">Vencida</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="congelada">Congelada</option>
                        </select>
                        <flux:error name="formData.estado" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Canal de Venta
                        </label>
                        <select wire:model="formData.canal_venta"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="presencial">Presencial</option>
                            <option value="online">Online</option>
                            <option value="telefonico">Telefónico</option>
                            <option value="referido">Referido</option>
                        </select>
                        <flux:error name="formData.canal_venta" />
                    </div>
                </div>

                @if ($formData['estado'] === 'cancelada')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Motivo de Cancelación
                        </label>
                        <textarea wire:model="formData.motivo_cancelacion" rows="2"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                        <flux:error name="formData.motivo_cancelacion" />
                    </div>
                @endif
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
                        <span wire:loading.remove wire:target="save">{{ $clienteMembresiaId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

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

</div>
