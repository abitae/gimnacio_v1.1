@props(['cliente', 'hideActions' => false])

<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3 space-y-2.5">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-2">
        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Perfil del Cliente</h3>
        <flux:button icon="x-mark" variant="ghost" size="xs" wire:click="clearClienteSelection" aria-label="Cerrar perfil">
        </flux:button>
    </div>

    <!-- Foto y Deuda -->
    <div class="flex items-center gap-3">
        @if ($cliente->foto)
            <div class="flex-shrink-0">
                <img src="{{ asset('storage/' . $cliente->foto) }}" alt="Foto del cliente"
                    class="w-32 h-32 rounded-full object-cover border border-zinc-200 dark:border-zinc-700">
            </div>
        @else
            <div class="flex-shrink-0">
                <div class="w-32 h-32 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                    <span class="text-lg text-zinc-500 dark:text-zinc-400">
                        {{ strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1)) }}
                    </span>
                </div>
            </div>
        @endif
        
        <!-- Deuda Total -->
        @php
            $deudaTotal = $cliente->deuda_total;
        @endphp
        <div class="flex-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Deuda Total</p>
            @if ($deudaTotal > 0)
                <p class="text-lg font-bold text-red-600 dark:text-red-400">
                    S/ {{ number_format($deudaTotal, 2) }}
                </p>
            @else
                <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                    Sin deuda
                </p>
            @endif
        </div>
    </div>

    <!-- Información Básica -->
    <div class="space-y-1.5">
        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre</p>
            <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                {{ $cliente->nombres }} {{ $cliente->apellidos }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Documento</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
            <span
                class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium
                @if ($cliente->estado_cliente === 'activo') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                @elseif($cliente->estado_cliente === 'inactivo')
                    bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                @else
                    bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 @endif
            ">
                {{ ucfirst($cliente->estado_cliente) }}
            </span>
        </div>

        <x-cliente.info-field label="Teléfono" :value="$cliente->telefono" />
        <x-cliente.info-field label="Email" :value="$cliente->email" />
        <x-cliente.info-field label="Dirección" :value="$cliente->direccion" />
        @if ($cliente->biotime_state)
            <x-cliente.info-field label="BioTime" value="Sincronizado" />
        @endif
    </div>

    <!-- Datos de Salud (resumen legado; editar en Gestión Nutricional) -->
    @if (collect($cliente->health_summary ?? [])->filter()->isNotEmpty())
        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Salud</p>
            <div class="space-y-1 text-xs">
                @if ($cliente->health_summary['enfermedades'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Enfermedades: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->health_summary['enfermedades'] }}</span>
                    </div>
                @endif
                @if ($cliente->health_summary['alergias'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Alergias: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->health_summary['alergias'] }}</span>
                    </div>
                @endif
                @if ($cliente->health_summary['medicacion'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Medicación: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->health_summary['medicacion'] }}</span>
                    </div>
                @endif
                @if ($cliente->health_summary['lesiones'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Lesiones: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->health_summary['lesiones'] }}</span>
                    </div>
                @endif
            </div>
            @can('gestion-nutricional.update')
            <flux:button variant="ghost" size="xs" wire:click="openSaludModal({{ $cliente->id }})" class="mt-1">
                <flux:icon name="pencil" class="w-3.5 h-3.5" /> Ver/editar en Gestión Nutricional
            </flux:button>
            @endcan
        </div>
    @else
        @can('gestion-nutricional.update')
        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
            <flux:button variant="ghost" size="xs" wire:click="openSaludModal({{ $cliente->id }})">
                <flux:icon name="heart" class="w-3.5 h-3.5" /> Salud / Nutrición
            </flux:button>
        </div>
        @endcan
    @endif

    <!-- Objetivos nutricionales -->
    @can('gestion-nutricional.view')
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
        <a href="{{ route('gestion-nutricional.objetivos.index', ['cliente_id' => $cliente->id]) }}" wire:navigate class="inline-flex items-center gap-1 text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">
            <flux:icon name="flag" class="w-3.5 h-3.5" /> Objetivos nutricionales
        </a>
    </div>
    @endcan

    <!-- Contacto de Emergencia -->
    @if ($cliente->datos_emergencia)
        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Emergencia</p>
            <div class="space-y-1 text-xs">
                @if ($cliente->datos_emergencia['nombre_contacto'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Nombre: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->datos_emergencia['nombre_contacto'] }}</span>
                    </div>
                @endif
                @if ($cliente->datos_emergencia['telefono_contacto'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Teléfono: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->datos_emergencia['telefono_contacto'] }}</span>
                    </div>
                @endif
                @if ($cliente->datos_emergencia['relacion'] ?? null)
                    <div>
                        <span class="text-zinc-500 dark:text-zinc-400">Relación: </span>
                        <span class="text-zinc-900 dark:text-zinc-100">{{ $cliente->datos_emergencia['relacion'] }}</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Consentimientos -->
    @if ($cliente->consentimientos)
        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Consentimientos</p>
            <div class="space-y-0.5 text-xs">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Uso de imagen</span>
                    <span
                        class="{{ $cliente->consentimientos['uso_imagen'] ?? false ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $cliente->consentimientos['uso_imagen'] ?? false ? '✓' : '✗' }}
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">Tratamiento datos</span>
                    <span
                        class="{{ $cliente->consentimientos['tratamiento_datos'] ?? false ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $cliente->consentimientos['tratamiento_datos'] ?? false ? '✓' : '✗' }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    <!-- Botones de Acción -->
    @if (!$hideActions)
        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 space-y-1.5">
            <div class="flex gap-1.5">
                @can('clientes.update')
                <flux:button icon="photo" color="purple" variant="primary" size="xs"
                    wire:click="openPhotoModal({{ $cliente->id }})" class="flex-1" aria-label="Subir foto">
                    Foto
                </flux:button>
                <flux:button icon="pencil" color="blue" variant="primary" size="xs"
                    wire:click="openEditModal({{ $cliente->id }})" class="flex-1" aria-label="Editar cliente">
                    Editar
                </flux:button>
                @endcan
            </div>
            @can('clientes.delete')
            <flux:button icon="trash" color="red" variant="primary" size="xs"
                wire:click="openDeleteModal({{ $cliente->id }})" class="w-full" aria-label="Eliminar cliente">
                Eliminar
            </flux:button>
            @endcan
        </div>
    @endif
</div>

