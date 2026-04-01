@props(['cliente', 'hideActions' => false, 'saludLinkOnly' => false, 'dismissible' => true, 'minimized' => false, 'deudaTotal' => null])

@php
    $deudaTotal = (float) ($deudaTotal ?? $cliente->deuda_total);
    $estadoClienteBadge = match ($cliente->estado_cliente) {
        'activo' => 'green',
        'inactivo' => 'zinc',
        default => 'red',
    };
@endphp

<div class="rounded-lg border border-zinc-200 bg-white p-3 space-y-2.5 dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex items-center justify-between border-b border-zinc-200 pb-2 dark:border-zinc-700">
        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Perfil del Cliente</h3>
        <div class="flex items-center gap-1">
            <flux:button
                :icon="$minimized ? 'chevron-down' : 'chevron-up'"
                variant="ghost"
                size="xs"
                wire:click="togglePerfilClienteMinimizado"
                :aria-label="$minimized ? 'Expandir perfil' : 'Minimizar perfil'"
            />
            @if ($dismissible)
                <flux:button icon="x-mark" variant="ghost" size="xs" wire:click="clearClienteSelection" aria-label="Cerrar perfil" />
            @endif
        </div>
    </div>

    @if ($minimized)
        <div class="flex items-center gap-3">
            @if ($cliente->foto)
                <div class="flex-shrink-0">
                    <img
                        src="{{ asset('storage/' . $cliente->foto) }}"
                        alt="Foto del cliente"
                        class="h-12 w-12 rounded-full border border-zinc-200 object-cover dark:border-zinc-700"
                    >
                </div>
            @else
                <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1)) }}
                    </span>
                </div>
            @endif

            <div class="min-w-0 flex-1 space-y-1">
                <p class="truncate text-xs font-medium text-zinc-900 dark:text-zinc-100">
                    {{ $cliente->nombres }} {{ $cliente->apellidos }}
                </p>
                <p class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">
                    {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                </p>
                <div class="flex flex-wrap items-center gap-1.5">
                    <flux:badge :color="$estadoClienteBadge">{{ ucfirst($cliente->estado_cliente) }}</flux:badge>
                    @if ($deudaTotal > 0)
                        <flux:badge color="red" class="tabular-nums">S/ {{ number_format($deudaTotal, 2) }}</flux:badge>
                    @else
                        <flux:badge color="green">{{ __('Sin deuda') }}</flux:badge>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center gap-3">
            @if ($cliente->foto)
                <div class="flex-shrink-0">
                    <img
                        src="{{ asset('storage/' . $cliente->foto) }}"
                        alt="Foto del cliente"
                        class="h-32 w-32 rounded-full border border-zinc-200 object-cover dark:border-zinc-700"
                    >
                </div>
            @else
                <div class="flex-shrink-0">
                    <div class="flex h-32 w-32 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <span class="text-lg text-zinc-500 dark:text-zinc-400">
                            {{ strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1)) }}
                        </span>
                    </div>
                </div>
            @endif

            <div class="flex-1">
                <p class="mb-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Deuda total') }}</p>
                @if ($deudaTotal > 0)
                    <flux:badge color="red" class="text-sm font-bold tabular-nums">S/ {{ number_format($deudaTotal, 2) }}</flux:badge>
                @else
                    <flux:badge color="green">{{ __('Sin deuda') }}</flux:badge>
                @endif
            </div>
        </div>

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
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Estado') }}</p>
                <flux:badge :color="$estadoClienteBadge">{{ ucfirst($cliente->estado_cliente) }}</flux:badge>
            </div>

            <x-cliente.info-field label="Teléfono" :value="$cliente->telefono" />
            <x-cliente.info-field label="Email" :value="$cliente->email" />
            <x-cliente.info-field label="Dirección" :value="$cliente->direccion" />
            @if ($cliente->biotime_state)
                <x-cliente.info-field label="BioTime" value="Sincronizado" />
            @endif
        </div>

        @if (collect($cliente->health_summary ?? [])->filter()->isNotEmpty())
            <div class="border-t border-zinc-200 pt-2 dark:border-zinc-700">
                <p class="mb-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">Salud</p>
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
                    @if ($saludLinkOnly)
                        <flux:button href="{{ route('gestion-nutricional.salud', $cliente) }}" wire:navigate variant="ghost" size="xs" icon="pencil"
                            class="mt-1 h-auto min-h-0 px-0 py-0.5 text-xs text-violet-600 hover:underline dark:text-violet-400">
                            Ver/editar en Gestión Nutricional
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="xs" wire:click="openSaludModal({{ $cliente->id }})" class="mt-1">
                            <flux:icon name="pencil" class="h-3.5 w-3.5" /> Ver/editar en Gestión Nutricional
                        </flux:button>
                    @endif
                @endcan
            </div>
        @else
            @can('gestion-nutricional.update')
                <div class="border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    @if ($saludLinkOnly)
                        <flux:button href="{{ route('gestion-nutricional.salud', $cliente) }}" wire:navigate variant="ghost" size="xs" icon="heart"
                            class="h-auto min-h-0 px-0 py-0.5 text-xs text-violet-600 hover:underline dark:text-violet-400">
                            Salud / Nutrición
                        </flux:button>
                    @else
                        <flux:button variant="ghost" size="xs" wire:click="openSaludModal({{ $cliente->id }})">
                            <flux:icon name="heart" class="h-3.5 w-3.5" /> Salud / Nutrición
                        </flux:button>
                    @endif
                </div>
            @endcan
        @endif

        @can('gestion-nutricional.view')
            <div class="border-t border-zinc-200 pt-2 dark:border-zinc-700">
                <flux:button href="{{ route('gestion-nutricional.objetivos.index', ['cliente_id' => $cliente->id]) }}" wire:navigate variant="ghost" size="xs" icon="flag"
                    class="h-auto min-h-0 px-0 py-0.5 text-xs text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100">
                    Objetivos nutricionales
                </flux:button>
            </div>
        @endcan

        @if ($cliente->datos_emergencia)
            <div class="border-t border-zinc-200 pt-2 dark:border-zinc-700">
                <p class="mb-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">Emergencia</p>
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

        @if ($cliente->consentimientos)
            <div class="border-t border-zinc-200 pt-2 dark:border-zinc-700">
                <p class="mb-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">Consentimientos</p>
                <div class="space-y-0.5 text-xs">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Uso de imagen</span>
                        <span class="{{ $cliente->consentimientos['uso_imagen'] ?? false ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $cliente->consentimientos['uso_imagen'] ?? false ? '✓' : '✕' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">Tratamiento datos</span>
                        <span class="{{ $cliente->consentimientos['tratamiento_datos'] ?? false ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $cliente->consentimientos['tratamiento_datos'] ?? false ? '✓' : '✕' }}
                        </span>
                    </div>
                </div>
            </div>
        @endif

        @if (!$hideActions)
            <div class="space-y-1.5 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                <div class="flex gap-1.5">
                    @can('clientes.update')
                        <flux:button icon="photo" color="purple" variant="primary" size="xs"
                            wire:click="openClientePhotoModal({{ $cliente->id }})" class="flex-1" aria-label="Subir foto">
                            Foto
                        </flux:button>
                        <flux:button icon="pencil" color="blue" variant="primary" size="xs"
                            wire:click="openClienteEditModal({{ $cliente->id }})" class="flex-1" aria-label="Editar cliente">
                            Editar
                        </flux:button>
                    @endcan
                </div>
                @can('clientes.delete')
                    <flux:button icon="trash" color="red" variant="primary" size="xs"
                        wire:click="openClienteDeleteModal({{ $cliente->id }})" class="w-full" aria-label="Eliminar cliente">
                        Eliminar
                    </flux:button>
                @endcan
            </div>
        @endif
    @endif
</div>
