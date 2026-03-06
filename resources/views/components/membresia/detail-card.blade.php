@props(['membresia'])

<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3 space-y-2.5">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-2">
        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Detalle de Membresía</h3>
        <flux:button variant="ghost" size="xs" wire:click="$set('selectedMembresiaId', null)" aria-label="Cerrar detalle">
            ✕
        </flux:button>
    </div>

    <!-- Información Básica -->
    <div class="space-y-1.5">
        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre</p>
            <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                {{ $membresia->nombre }}
            </p>
        </div>

        <x-membresia.info-field label="Descripción" :value="$membresia->descripcion" />

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Duración</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $membresia->duracion_dias }} días
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Precio Base</p>
            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                S/ {{ number_format($membresia->precio_base, 2) }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tipo de Acceso</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                @if ($membresia->tipo_acceso === 'ilimitado')
                    Ilimitado
                @elseif ($membresia->tipo_acceso === 'limitado')
                    Limitado ({{ $membresia->max_visitas_dia ?? 0 }} visitas/día)
                @else
                    No especificado
                @endif
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
            <span
                class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $membresia->estado === 'activa' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' }}">
                {{ ucfirst($membresia->estado) }}
            </span>
        </div>
    </div>

    <!-- Configuración de Congelación -->
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
        <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Congelación</p>
        <div class="space-y-1 text-xs">
            <div class="flex items-center justify-between">
                <span class="text-zinc-500 dark:text-zinc-400">Permite congelación</span>
                <span
                    class="{{ $membresia->permite_congelacion ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                    {{ $membresia->permite_congelacion ? '✓' : '✗' }}
                </span>
            </div>
            @if ($membresia->permite_congelacion && $membresia->max_dias_congelacion)
                <div>
                    <span class="text-zinc-500 dark:text-zinc-400">Máximo días: </span>
                    <span class="text-zinc-900 dark:text-zinc-100">{{ $membresia->max_dias_congelacion }} días</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Estadísticas -->
    @php
        $clientesCount = $membresia->clienteMembresias()->count();
        $clientesActivosCount = $membresia->clienteMembresias()->where('estado', 'activa')->count();
    @endphp
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2">
        <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300 mb-1.5">Estadísticas</p>
        <div class="space-y-1 text-xs">
            <div>
                <span class="text-zinc-500 dark:text-zinc-400">Total clientes: </span>
                <span class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $clientesCount }}</span>
            </div>
            <div>
                <span class="text-zinc-500 dark:text-zinc-400">Clientes activos: </span>
                <span class="text-zinc-900 dark:text-zinc-100 font-medium">{{ $clientesActivosCount }}</span>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 space-y-1.5">
        @can('membresias.update')
        <div class="flex gap-1.5">
            <flux:button icon="pencil" color="blue" variant="primary" size="xs"
                wire:click="openEditModal({{ $membresia->id }})" class="flex-1" aria-label="Editar membresía">
                Editar
            </flux:button>
        </div>
        @endcan
        @can('membresias.delete')
        <flux:button icon="trash" color="red" variant="primary" size="xs"
            wire:click="openDeleteModal({{ $membresia->id }})" class="w-full" aria-label="Eliminar membresía">
            Eliminar
        </flux:button>
        @endcan
    </div>
</div>
