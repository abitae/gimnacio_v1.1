@props(['clase'])

<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3 space-y-2.5">
    <!-- Header -->
    <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-2">
        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Detalle de Clase</h3>
        <flux:button variant="ghost" size="xs" wire:click="$set('selectedClaseId', null)" aria-label="Cerrar detalle">
            ✕
        </flux:button>
    </div>

    <!-- Información Básica -->
    <div class="space-y-1.5">
        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Código</p>
            <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                {{ $clase->codigo }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre</p>
            <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                {{ $clase->nombre }}
            </p>
        </div>

        @if ($clase->descripcion)
            <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Descripción</p>
                <p class="text-xs text-zinc-900 dark:text-zinc-100 line-clamp-3">{{ $clase->descripcion }}</p>
            </div>
        @endif

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tipo</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $clase->tipo === 'sesion' ? 'Por Sesión' : 'Por Paquete' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Precio</p>
            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                S/ {{ number_format($clase->obtenerPrecio(), 2) }}
            </p>
        </div>

        @if ($clase->tipo === 'paquete' && $clase->sesiones_paquete)
            <div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Sesiones en el Paquete</p>
                <p class="text-xs text-zinc-900 dark:text-zinc-100">
                    {{ $clase->sesiones_paquete }} sesiones
                </p>
            </div>
        @endif

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Instructor</p>
            <p class="text-xs text-zinc-900 dark:text-zinc-100">
                {{ $clase->instructor->name ?? 'Sin instructor asignado' }}
            </p>
        </div>

        <div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
            <span
                class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $clase->estado === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' }}">
                {{ ucfirst($clase->estado) }}
            </span>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 space-y-1.5">
        @can('clases.update')
        <div class="flex gap-1.5">
            <flux:button icon="pencil" color="blue" variant="primary" size="xs"
                wire:click="openEditModal({{ $clase->id }})" class="flex-1" aria-label="Editar clase">
                Editar
            </flux:button>
        </div>
        @endcan
        @can('clases.delete')
        <flux:button icon="trash" color="red" variant="primary" size="xs"
            wire:click="openDeleteModal({{ $clase->id }})" class="w-full" aria-label="Eliminar clase">
            Eliminar
        </flux:button>
        @endcan
    </div>
</div>
