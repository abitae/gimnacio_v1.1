<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Clientes</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Administra los clientes del gimnasio</p>
            </div>
            @can('clientes.create')
            <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal"
                wire:loading.attr="disabled" wire:target="openCreateModal" aria-label="Crear nuevo cliente">
                <span wire:loading.remove wire:target="openCreateModal">Nuevo Cliente</span>
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
                    aria-label="Buscar clientes" />
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="Filtrar por estado">
                    <option value="">Todos</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="suspendido">Suspendido</option>
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



        <!-- Table and Profile Card -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- Table -->
            <div
                class="lg:col-span-2 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Documento
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Nombre
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Teléfono
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    Estado
                                </th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                    BioTime
                                </th>

                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                            @forelse ($clientes as $cliente)
                                <tr wire:key="cliente-{{ $cliente->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer {{ $selectedCliente && $selectedCliente->id === $cliente->id ? 'bg-blue-50 dark:bg-blue-900/20' : '' }}"
                                    wire:click="selectCliente({{ $cliente->id }})" role="button" tabindex="0"
                                    aria-label="Seleccionar cliente {{ $cliente->nombres }} {{ $cliente->apellidos }}"
                                    @keydown.enter="selectCliente({{ $cliente->id }})"
                                    @keydown.space.prevent="selectCliente({{ $cliente->id }})">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-xs">
                                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                        {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                        {{ $cliente->telefono ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-xs">
                                        <span
                                            class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $cliente->estado_cliente === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($cliente->estado_cliente === 'inactivo' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400') }}">
                                            {{ ucfirst($cliente->estado_cliente) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs text-center space-x-1">
                                        @php($biotimeState = $cliente->biotime_state_bool)
                                        <span class="inline-flex items-center" title="Sincronización BioTime activa/inactiva">
                                            <flux:icon
                                                name="{{ $biotimeState ? 'check-circle' : 'x-circle' }}"
                                                class="size-6"
                                                style="color: {{ $biotimeState ? '#65a30d' : '#dc2626' }};"
                                            />
                                            <span class="sr-only">
                                                {{ $biotimeState ? 'BioTime activo' : 'BioTime inactivo' }}
                                            </span>
                                        </span>
                                        @php($biotimeUpdate = $cliente->biotime_update_bool)
                                        <span class="inline-flex items-center" title="Actualización pendiente de BioTime">
                                            <flux:icon
                                                name="{{ $biotimeUpdate ? 'arrow-path' : 'x-circle' }}"
                                                class="size-6"
                                                style="color: {{ $biotimeUpdate ? '#65a30d' : '#dc2626' }};"
                                            />
                                            <span class="sr-only">
                                                {{ $biotimeUpdate ? 'Necesita actualizar en BioTime' : 'Sin actualización pendiente' }}
                                            </span>
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6"
                                        class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                        No hay clientes
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Profile Card -->
            <div class="lg:col-span-1 space-y-2">
                @if ($selectedCliente)
                    <x-cliente.profile-card :cliente="$selectedCliente" />
                    @can('crm.view')
                    <a href="{{ route('crm.clientes.etiquetas', $selectedCliente->id) }}" wire:navigate
                        class="inline-flex items-center gap-1 text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">
                        <flux:icon name="tag" class="w-4 h-4" /> Etiquetas CRM
                    </a>
                    @endcan
                @else
                    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                        <div class="flex flex-col items-center justify-center h-full min-h-[150px] text-center">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Selecciona un cliente para ver su
                                perfil</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-4 flex justify-end">
            {{ $clientes->links() }}
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $clienteId ? 'Editar Cliente' : 'Nuevo Cliente' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $clienteId ? 'Modifica la información del cliente' : 'Completa los datos del nuevo cliente' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-2">

                    <flux:select label="Tipo de documento" size="xs" wire:model="formData.tipo_documento"
                        placeholder="Tipo de documento">
                        <flux:select.option value="DNI">DNI</flux:select.option>
                        <flux:select.option value="CE">Carnet de extranjería</flux:select.option>
                    </flux:select>


                    <div>
                        <flux:input size="xs" wire:model="formData.numero_documento" label="Número de Documento"
                            required />
                        <flux:error name="formData.numero_documento" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model="formData.nombres" label="Nombres" required />
                    <flux:error name="formData.nombres" />

                    <flux:input size="xs" wire:model="formData.apellidos" label="Apellidos" required />
                    <flux:error name="formData.apellidos" />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model="formData.telefono" label="Teléfono" type="tel" />
                    <flux:error name="formData.telefono" />

                    <flux:input size="xs" wire:model="formData.email" label="Email" type="email" />
                    <flux:error name="formData.email" />
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <flux:select label="Sexo" size="xs" wire:model="formData.sexo" placeholder="Seleccione sexo">
                        <flux:select.option value="masculino">Masculino</flux:select.option>
                        <flux:select.option value="femenino">Femenino</flux:select.option>
                    </flux:select>
                    <flux:error name="formData.sexo" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Dirección
                    </label>
                    <textarea wire:model="formData.direccion" rows="2"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                    <flux:error name="formData.direccion" />
                </div>

                <!-- Datos de Salud -->
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Datos de Salud</h3>
                    <div class="space-y-2">
                        <flux:input size="xs" wire:model="formData.datos_salud.alergias" label="Alergias"
                            placeholder="Ej: Ninguna, Polen, etc." />
                        <flux:input size="xs" wire:model="formData.datos_salud.medicamentos"
                            label="Medicamentos" placeholder="Ej: Ninguno, Antihistamínico, etc." />
                        <flux:input size="xs" wire:model="formData.datos_salud.lesiones" label="Lesiones"
                            placeholder="Ej: Ninguna, Rodilla izquierda, etc." />
                    </div>
                </div>

                <!-- Datos de Emergencia -->
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Contacto de Emergencia</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <flux:input size="xs" wire:model="formData.datos_emergencia.nombre" label="Nombre" />
                        <flux:input size="xs" wire:model="formData.datos_emergencia.telefono" label="Teléfono"
                            type="tel" />
                        <flux:input size="xs" wire:model="formData.datos_emergencia.relacion" label="Relación"
                            placeholder="Ej: Esposa, Hermano, etc." />
                    </div>
                </div>

                <!-- Consentimientos -->
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Consentimientos</h3>
                    <div class="space-y-1.5">
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="formData.consentimientos.uso_imagen"
                                class="rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="ml-2 text-xs text-zinc-700 dark:text-zinc-300">Uso de imagen</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" wire:model="formData.consentimientos.tratamiento_datos"
                                class="rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800">
                            <span class="ml-2 text-xs text-zinc-700 dark:text-zinc-300">Tratamiento de datos</span>
                        </label>
                    </div>
                </div>

                @can('clientes.update')
                <!-- Botón para agregar foto -->
                <div class="flex justify-center">
                    <flux:button variant="ghost" size="xs" type="button"
                        wire:click="openPhotoModal({{ $clienteId ?? 'null' }})" class="text-xs">
                        <span>📷 Agregar Foto</span>
                    </flux:button>
                </div>
                @endcan
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
                        <span wire:loading.remove wire:target="save">{{ $clienteId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Photo Upload Modal -->
    <flux:modal name="photo-modal" wire:model="modalState.photo" focusable class="md:w-lg">
        <div x-data="photoUploadManager({{ $photoClienteId ?? 'null' }})" x-init="init()"
            @keydown.escape.window="if ($wire.modalState.photo) $wire.closeModal()">
            <form wire:submit.prevent="uploadPhoto" @submit.prevent="handleSubmit">
                <div class="space-y-4 p-4">
                    <!-- Header -->
                    <div>
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                            {{ $currentPhoto ? 'Cambiar Foto del Cliente' : 'Subir Foto del Cliente' }}
                        </h2>
                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                            Selecciona una imagen o captura desde la cámara web
                        </p>
                        <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-500">
                            Formatos: JPEG, PNG, WEBP • Tamaño máximo: 2MB
                        </p>
                    </div>

                    <!-- Foto actual del cliente -->
                    @if ($currentPhoto)
                        <div x-ref="currentPhotoContainer"
                            class="flex flex-col items-center space-y-2 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700">
                            <p class="mb-1 text-xs font-medium text-zinc-700 dark:text-zinc-300">Foto actual:</p>
                            <div class="relative group">
                                <img src="{{ asset('storage/' . $currentPhoto) }}" alt="Foto actual del cliente"
                                    class="max-h-48 w-auto rounded-lg border border-zinc-200 shadow-sm dark:border-zinc-700 transition-opacity"
                                    :class="hasNewPhoto ? 'opacity-50' : 'opacity-100'">
                                <div x-show="hasNewPhoto"
                                    class="absolute inset-0 flex items-center justify-center bg-zinc-900/50 rounded-lg">
                                    <span class="text-xs text-white font-medium">Será reemplazada</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Tabs para alternar entre subir archivo y tomar foto -->
                    <div class="flex border-b border-zinc-200 dark:border-zinc-700" role="tablist">
                        <button type="button" role="tab" @click="switchTab('upload')"
                            :class="activeTab === 'upload'
                                ?
                                'border-b-2 border-purple-500 text-purple-600 dark:text-purple-400 font-semibold' :
                                'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                            class="flex-1 px-3 py-2 text-xs font-medium text-center focus:outline-none transition-colors">
                            <span class="flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                Subir Archivo
                            </span>
                        </button>
                        <button type="button" role="tab" @click="switchTab('camera')"
                            :class="activeTab === 'camera'
                                ?
                                'border-b-2 border-purple-500 text-purple-600 dark:text-purple-400 font-semibold' :
                                'text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100'"
                            class="flex-1 px-3 py-2 text-xs font-medium text-center focus:outline-none transition-colors">
                            <span class="flex items-center justify-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Tomar Foto
                            </span>
                        </button>
                    </div>

                    <!-- Tab: Subir Archivo -->
                    <div x-show="activeTab === 'upload'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100" class="space-y-3">
                        <!-- Drag & Drop Zone -->
                        <div @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop($event)"
                            :class="isDragging
                                ?
                                'border-purple-500 bg-purple-50 dark:bg-purple-900/20' :
                                'border-zinc-300 dark:border-zinc-600'"
                            class="relative border-2 border-dashed rounded-lg p-6 transition-colors bg-zinc-50 dark:bg-zinc-900/50">
                            <input type="file" wire:model.live="foto"
                                accept="image/jpeg,image/jpg,image/png,image/webp" x-ref="fileInput"
                                @change="handleFileSelect($event)" class="hidden">

                            <div class="text-center">
                                <svg class="mx-auto h-12 w-12 text-zinc-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4 flex text-sm leading-6 text-zinc-600 dark:text-zinc-400">
                                    <label for="file-upload"
                                        class="relative cursor-pointer rounded-md font-semibold text-purple-600 dark:text-purple-400 focus-within:outline-none focus-within:ring-2 focus-within:ring-purple-500">
                                        <span>Haz clic para seleccionar</span>
                                        <input type="file" id="file-upload" wire:model.live="foto"
                                            accept="image/jpeg,image/jpg,image/png,image/webp"
                                            @change="handleFileSelect($event)" class="sr-only">
                                    </label>
                                    <p class="pl-1">o arrastra y suelta</p>
                                </div>
                                <p class="text-xs leading-5 text-zinc-500 dark:text-zinc-500 mt-1">
                                    PNG, JPG, WEBP hasta 2MB
                                </p>
                            </div>
                        </div>

                        <!-- Error de validación -->
                        <flux:error name="foto" />

                        <!-- Información del archivo seleccionado -->
                        <div x-show="selectedFile" x-transition
                            class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-medium text-blue-900 dark:text-blue-100"
                                        x-text="selectedFile && selectedFile.name ? selectedFile.name : 'Archivo seleccionado'"></p>
                                    <p class="text-xs text-blue-700 dark:text-blue-300 mt-0.5"
                                        x-show="selectedFile && selectedFile.size != null"
                                        x-text="selectedFile && selectedFile.size != null ? formatFileSize(selectedFile.size) : ''"></p>
                                </div>
                                <button type="button" @click="clearFile()"
                                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-200">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Vista previa de imagen -->
                        <div x-show="previewUrl" x-transition
                            class="flex flex-col items-center space-y-2 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700">
                            <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Vista previa:</p>
                            <div class="relative group">
                                <img :src="previewUrl" alt="Vista previa"
                                    class="max-h-64 w-auto rounded-lg border border-zinc-200 shadow-sm dark:border-zinc-700">
                                <div
                                    class="absolute inset-0 bg-zinc-900/0 group-hover:bg-zinc-900/20 rounded-lg transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <button type="button" @click="clearFile()"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Vista previa de Livewire (cuando está disponible) -->
                        @if ($foto && method_exists($foto, 'temporaryUrl'))
                            <div class="flex flex-col items-center space-y-2 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700"
                                x-init="hidePreview()">
                                <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Vista previa:</p>
                                <img src="{{ $foto->temporaryUrl() }}" alt="Vista previa"
                                    class="max-h-64 w-auto rounded-lg border border-zinc-200 shadow-sm dark:border-zinc-700">
                            </div>
                        @endif
                    </div>

                    <!-- Tab: Tomar Foto -->
                    <div x-show="activeTab === 'camera'" x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-95"
                        x-transition:enter-end="opacity-100 transform scale-100" class="space-y-3">
                        <!-- Aviso: se pedirá permiso de cámara -->
                        <div x-show="cameraSupported && !streamActive && !cameraError && !capturedImage" x-transition
                            class="p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800">
                            <p class="text-xs text-blue-800 dark:text-blue-200 flex items-start gap-2">
                                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                                <span>Al hacer clic en <strong>Activar cámara</strong> el navegador te pedirá permiso para usar la cámara. Acepta para poder tomar la foto.</span>
                            </p>
                        </div>

                        <!-- Mensaje si la cámara no está soportada o no es HTTPS -->
                        <div x-show="!cameraSupported" x-transition
                            class="p-4 rounded-lg bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0"
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                                <div class="flex-1">
                                    <p class="text-xs font-semibold text-yellow-800 dark:text-yellow-300 mb-1">
                                        Cámara no disponible
                                    </p>
                                    <p class="text-xs text-yellow-700 dark:text-yellow-400"
                                        x-text="cameraError || 'Usa HTTPS o localhost y un navegador moderno (Chrome, Firefox, Edge).'">
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Permiso denegado: cómo habilitar de nuevo -->
                        <div x-show="cameraSupported && cameraPermissionDenied" x-transition
                            class="p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800">
                            <p class="text-xs font-semibold text-amber-800 dark:text-amber-200 mb-1">Permiso de cámara denegado</p>
                            <p class="text-xs text-amber-700 dark:text-amber-300 mb-2">
                                Para tomar la foto hay que permitir el acceso a la cámara. Haz lo siguiente y vuelve a pulsar <strong>Activar cámara</strong>:
                            </p>
                            <ul class="text-xs text-amber-700 dark:text-amber-300 list-disc list-inside space-y-0.5">
                                <li>Chrome/Edge: clic en el icono de candado o información en la barra de direcciones → Permisos → Cámara → Permitir.</li>
                                <li>Firefox: clic en el icono de candado → Limpiar cookies y permisos del sitio, o en Ajustes → Privacidad → Permisos.</li>
                            </ul>
                            <button type="button" class="mt-3 inline-flex items-center justify-center rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-purple-500"
                                @click="cameraError = ''; cameraPermissionDenied = false; startCamera();">
                                Intentar de nuevo
                            </button>
                        </div>

                        <!-- Video para vista previa de cámara (espejo para selfie) -->
                        <div x-show="cameraSupported && !capturedImage" class="space-y-3">
                            <div
                                class="relative rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-700 bg-zinc-900 aspect-video min-h-[200px]">
                                <!-- Vista espejo para que se vea natural al usuario -->
                                <video x-ref="video" x-show="streamActive" autoplay playsinline muted
                                    class="w-full h-full object-cover scale-x-[-1]"></video>

                                <!-- Flash breve al capturar -->
                                <div x-ref="flashOverlay" class="absolute inset-0 bg-white pointer-events-none opacity-0 transition-opacity duration-75"
                                    :class="{ 'opacity-100': flashActive }"></div>

                                <!-- Estado inicial: icono y texto (sin depender de overlay para el botón) -->
                                <div x-show="!streamActive && !isLoading"
                                    class="absolute inset-0 flex flex-col items-center justify-center p-6 bg-zinc-50 dark:bg-zinc-900">
                                    <svg class="w-14 h-14 text-zinc-400 mb-3 flex-shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400 text-center mb-1">
                                        Se pedirá permiso para usar la cámara
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-500 text-center mb-4">
                                        Acepta en el navegador cuando aparezca el aviso
                                    </p>
                                    <button type="button"
                                        @click.stop="requestCameraPermission()"
                                        :disabled="isLoading"
                                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none">
                                        <span x-show="!isLoading">Activar cámara</span>
                                        <span x-show="isLoading">Solicitando permiso...</span>
                                    </button>
                                </div>

                                <div x-show="isLoading"
                                    class="absolute inset-0 flex items-center justify-center bg-zinc-900/70">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="animate-spin rounded-full h-10 w-10 border-2 border-white border-t-transparent"></div>
                                        <p class="text-sm text-white font-medium">Solicitando permiso de cámara...</p>
                                        <p class="text-xs text-white/80">Acepta en el navegador si aparece el aviso</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Botón Activar cámara también fuera del cuadro (por si el overlay falla) -->
                            <div x-show="cameraSupported && !streamActive && !capturedImage && !cameraPermissionDenied"
                                class="flex justify-center">
                                <button type="button"
                                    @click.stop="requestCameraPermission()"
                                    :disabled="isLoading"
                                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-purple-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none">
                                    <span x-show="!isLoading">Activar cámara</span>
                                    <span x-show="isLoading">Solicitando permiso...</span>
                                </button>
                            </div>

                            <!-- Error genérico (no mostrar si ya mostramos el panel de permiso denegado) -->
                            <div x-show="cameraError && !cameraPermissionDenied" x-transition
                                class="p-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                                <p class="text-xs text-red-600 dark:text-red-400 flex items-center gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span x-text="cameraError"></span>
                                </p>
                            </div>

                            <div x-show="streamActive" x-transition class="flex gap-2 justify-center flex-wrap">
                                <flux:button variant="primary" size="sm" type="button"
                                    @click="capturePhoto()" x-bind:disabled="isCapturing">
                                    <span class="flex items-center gap-2" x-show="!isCapturing">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                                clip-rule="evenodd" />
                                        </svg>
                                        Capturar foto
                                    </span>
                                    <span class="flex items-center gap-2" x-show="isCapturing">
                                        <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Guardando...
                                    </span>
                                </flux:button>
                                <flux:button variant="ghost" size="sm" type="button" @click="stopCamera()">
                                    Detener cámara
                                </flux:button>
                            </div>
                        </div>

                        <!-- Canvas oculto para captura -->
                        <canvas x-ref="canvas" class="hidden"></canvas>

                        <!-- Vista previa de foto capturada (sin espejo, como se guardará) -->
                        <div x-show="capturedImage || $wire.capturedPhotoUrl" x-transition
                            class="space-y-3 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-900/50 border border-zinc-200 dark:border-zinc-700">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Foto capturada</p>
                                <div class="flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd" />
                                    </svg>
                                    <span>Lista para guardar</span>
                                </div>
                            </div>
                            <div class="relative group aspect-video flex justify-center bg-zinc-200 dark:bg-zinc-800 rounded-lg overflow-hidden">
                                <img :src="capturedImage || $wire.capturedPhotoUrl" alt="Foto capturada"
                                    class="max-h-64 w-auto object-contain rounded-lg border-2 border-green-500 shadow-sm"
                                    x-on:error="$el.src = capturedImage">
                                <div
                                    class="absolute inset-0 bg-zinc-900/0 group-hover:bg-zinc-900/20 rounded-lg transition-colors flex items-center justify-center opacity-0 group-hover:opacity-100">
                                    <button type="button" @click="retakePhoto()"
                                        class="px-3 py-1.5 text-xs font-medium text-white bg-red-600 rounded-lg hover:bg-red-700">
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                            <div class="flex gap-2 justify-center">
                                <flux:button variant="ghost" size="sm" type="button" @click="retakePhoto()">
                                    Tomar otra foto
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                    <flux:modal.close>
                        <flux:button variant="ghost" size="sm" wire:click="closeModal" type="button"
                            @click="resetAll()" wire:loading.attr="disabled">
                            Cancelar
                        </flux:button>
                    </flux:modal.close>
                    <flux:button variant="primary" size="sm" type="submit" wire:loading.attr="disabled"
                        wire:target="uploadPhoto" x-bind:disabled="!hasFile">
                        <span wire:loading.remove wire:target="uploadPhoto" class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            Subir Foto
                        </span>
                        <span wire:loading wire:target="uploadPhoto" class="flex items-center gap-2">
                            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                    stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            Subiendo...
                        </span>
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    @can('clientes.delete')
    <!-- Delete Modal -->
    <flux:modal name="delete-modal" wire:model="modalState.delete" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Eliminar Cliente
            </h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                ¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.
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

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('photoUploadManager', (photoId) => ({
            activeTab: 'upload',
            previewUrl: '',
            selectedFile: null,
            isDragging: false,
            streamActive: false,
            capturedImage: '',
            cameraError: '',
            stream: null,
            cameraSupported: false,
            isLoading: false,
            isCapturing: false,
            flashActive: false,
            cameraPermissionDenied: false,
            hasNewPhoto: false,

            get hasFile() {
                return this.previewUrl || this.capturedImage || this.$wire.capturedPhotoUrl ||
                    this.selectedFile || this.$wire.foto;
            },

            init() {
                this.checkCameraSupport();

                // Observar cambios en el modal
                this.$watch('$wire.modalState.photo', (value) => {
                    if (!value) {
                        this.resetAll();
                    }
                });

                // Detener cámara al cambiar de tab
                this.$watch('activeTab', (newTab) => {
                    if (newTab !== 'camera' && this.streamActive) {
                        this.stopCamera();
                    }
                });

                // Observar cambios en la foto de Livewire
                this.$watch('$wire.foto', (value) => {
                    if (value) {
                        this.hasNewPhoto = true;
                        if (!this.selectedFile) {
                            // Si Livewire tiene un archivo pero no lo tenemos localmente, actualizar
                            this.selectedFile = {
                                name: 'Archivo seleccionado'
                            };
                        }
                    }
                });
            },

            switchTab(tab) {
                if (this.activeTab !== tab) {
                    this.activeTab = tab;
                    if (tab === 'upload') {
                        this.stopCamera();
                    }
                }
            },

            formatFileSize(bytes) {
                if (bytes == null || bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB'];
                const i = Math.min(2, Math.floor(Math.log(bytes) / Math.log(k)));
                return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
            },

            clearFile() {
                this.selectedFile = null;
                this.previewUrl = '';
                this.hasNewPhoto = false;
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
                @this.set('foto', null);
            },

            handleDrop(event) {
                this.isDragging = false;
                const files = event.dataTransfer.files;
                if (files.length > 0) {
                    this.processFile(files[0], event);
                }
            },

            processFile(file, event) {
                if (!file) return;

                // Validaciones
                const maxSize = 2 * 1024 * 1024; // 2MB
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

                if (!allowedTypes.includes(file.type)) {
                    this.showError(
                        'Tipo de archivo no permitido. Solo se permiten: JPEG, PNG o WEBP.');
                    return;
                }

                if (file.size > maxSize) {
                    this.showError('El archivo es demasiado grande. El tamaño máximo es 2MB.');
                    return;
                }

                this.selectedFile = file;

                // Crear vista previa
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                    this.hasNewPhoto = true;
                };
                reader.onerror = () => {
                    this.showError('Error al leer el archivo.');
                    this.clearFile();
                };
                reader.readAsDataURL(file);

                // Actualizar input de Livewire si es necesario
                if (event && event.target) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    if (this.$refs.fileInput) {
                        this.$refs.fileInput.files = dataTransfer.files;
                        this.$refs.fileInput.dispatchEvent(new Event('change', {
                            bubbles: true
                        }));
                    }
                }
            },

            handleFileSelect(event) {
                const file = event.target.files[0];
                this.processFile(file, event);
            },

            showError(message) {
                // Usar Livewire para mostrar el error
                @this.call('$dispatch', 'show-error', {
                    message
                });
            },

            checkCameraSupport() {
                const host = window.location.hostname || '';
                const isLocal = ['localhost', '127.0.0.1'].includes(host) ||
                    host.endsWith('.test') || host.endsWith('.local');
                const isSecure = window.isSecureContext || (window.location.protocol === 'https:') || isLocal;

                if (!isSecure) {
                    this.cameraSupported = false;
                    this.cameraError = 'La cámara requiere HTTPS o ejecutarse en localhost.';
                    return;
                }

                if (navigator.mediaDevices?.getUserMedia) {
                    this.cameraSupported = true;
                    this.cameraError = '';
                } else {
                    const legacy = navigator.getUserMedia || navigator.webkitGetUserMedia ||
                        navigator.mozGetUserMedia || navigator.msGetUserMedia;
                    this.cameraSupported = !!legacy;
                    this.cameraError = this.cameraSupported ? '' : 'Tu navegador no soporta la cámara. Usa Chrome, Firefox o Edge.';
                }
            },

            /** Punto de entrada: el usuario hace clic en "Activar cámara". Llamar getUserMedia en el mismo tick del clic. */
            requestCameraPermission() {
                this.cameraPermissionDenied = false;
                this.cameraError = '';
                if (!navigator.mediaDevices?.getUserMedia) {
                    this.cameraError = 'Tu navegador no soporta acceso a la cámara.';
                    return;
                }
                this.isLoading = true;
                const constraints = { video: { facingMode: 'user' } };
                const promise = navigator.mediaDevices.getUserMedia(constraints);
                this.startCamera(promise);
            },

            async startCamera(getUserMediaPromise) {
                try {
                    this.stream = getUserMediaPromise ? await getUserMediaPromise : null;
                    if (!this.stream) {
                        this.cameraError = 'No se pudo obtener el permiso.';
                        return;
                    }
                    const video = this.$refs.video;
                    if (video) {
                        video.srcObject = this.stream;
                        video.play().catch(() => {});
                        this.streamActive = true;
                    } else {
                        setTimeout(() => {
                            const v = this.$refs.video;
                            if (v && this.stream) {
                                v.srcObject = this.stream;
                                v.play().catch(() => {});
                                this.streamActive = true;
                            }
                        }, 150);
                    }
                } catch (err) {
                    console.error('Error al acceder a la cámara:', err);
                    this.cameraPermissionDenied = err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError';
                    const msgs = {
                        'NotAllowedError': 'Permiso de cámara denegado. Acepta el permiso en el navegador.',
                        'PermissionDeniedError': 'Permiso de cámara denegado. Acepta el permiso en el navegador.',
                        'NotFoundError': 'No se encontró ninguna cámara.',
                        'DevicesNotFoundError': 'No se encontró ninguna cámara.',
                        'NotReadableError': 'La cámara está en uso por otra aplicación.',
                        'TrackStartError': 'No se pudo iniciar la cámara.',
                        'OverconstrainedError': 'La cámara no cumple los requisitos.',
                        'ConstraintNotSatisfiedError': 'Configuración no soportada.'
                    };
                    this.cameraError = msgs[err.name] || ('Error: ' + (err.message || 'desconocido'));
                    this.streamActive = false;
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                        this.stream = null;
                    }
                } finally {
                    this.isLoading = false;
                }
            },

            stopCamera() {
                if (this.stream?.getTracks) {
                    this.stream.getTracks().forEach(track => track.stop());
                } else if (this.stream?.stop) {
                    this.stream.stop();
                }
                this.stream = null;

                if (this.$refs.video) {
                    this.$refs.video.srcObject = null;
                }
                this.streamActive = false;
            },

            async capturePhoto() {
                if (!this.streamActive || !this.$refs.video || !this.$refs.canvas || this.isCapturing) return;

                const video = this.$refs.video;
                const canvas = this.$refs.canvas;
                const ctx = canvas.getContext('2d');
                const w = video.videoWidth;
                const h = video.videoHeight;

                canvas.width = w;
                canvas.height = h;
                // Deshacer espejo: dibujar volteado para que la foto guardada sea correcta
                ctx.translate(w, 0);
                ctx.scale(-1, 1);
                ctx.drawImage(video, 0, 0, w, h);
                ctx.setTransform(1, 0, 0, 1, 0, 0);

                const dataUrl = canvas.toDataURL('image/jpeg', 0.9);
                this.isCapturing = true;

                this.flashActive = true;
                setTimeout(() => { this.flashActive = false; }, 120);

                try {
                    await @this.call('capturePhoto', dataUrl);
                    this.capturedImage = this.$wire.capturedPhotoUrl || dataUrl;
                    this.hasNewPhoto = true;
                    this.stopCamera();
                } catch (e) {
                    console.error('Error al guardar foto:', e);
                } finally {
                    this.isCapturing = false;
                }
            },

            retakePhoto() {
                this.capturedImage = '';
                this.hasNewPhoto = false;
                @this.call('clearCapturedPhoto');
                this.cameraError = '';
                this.requestCameraPermission();
            },

            handleSubmit() {
                // La foto capturada ya se envía automáticamente en capturePhoto()
                // Este método solo se usa para validar antes de submit
            },

            resetAll() {
                this.stopCamera();
                this.clearFile();
                this.capturedImage = '';
                this.cameraError = '';
                this.cameraPermissionDenied = false;
                this.activeTab = 'upload';
                this.hasNewPhoto = false;
                this.isLoading = false;
                this.isCapturing = false;
                this.flashActive = false;
                @this.set('capturedPhotoUrl', null);
            }
        }));
    });
</script>
