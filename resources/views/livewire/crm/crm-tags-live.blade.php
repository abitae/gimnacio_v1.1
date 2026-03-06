<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Etiquetas CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Gestiona etiquetas para leads y clientes</p>
        </div>
        @can('crm.create')
        <flux:button size="sm" variant="primary" wire:click="openCreate">Nueva etiqueta</flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar etiqueta..." class="max-w-xs" />

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse($tags as $tag)
            <li class="flex items-center justify-between gap-2 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-800">
                <div class="flex items-center gap-2">
                    <span class="w-4 h-4 rounded-full shrink-0" style="background: {{ $tag->color ?? '#6366f1' }}"></span>
                    <span class="font-medium">{{ $tag->nombre }}</span>
                </div>
                <div class="flex gap-1">
                    @can('crm.update')
                    <flux:button size="xs" variant="ghost" wire:click="openEdit({{ $tag->id }})">Editar</flux:button>
                    @endcan
                    @can('crm.delete')
                    <flux:button size="xs" variant="ghost" wire:click="deleteTag({{ $tag->id }})" wire:confirm="¿Eliminar esta etiqueta?">Eliminar</flux:button>
                    @endcan
                </div>
            </li>
            @empty
            <li class="p-8 text-center rounded-lg border border-dashed border-zinc-200 dark:border-zinc-600">
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">No hay etiquetas. Crea etiquetas para organizar leads y clientes.</p>
                @can('crm.create')
                <flux:button size="sm" variant="primary" wire:click="openCreate">Crear primera etiqueta</flux:button>
                @endcan
            </li>
            @endforelse
        </ul>
    </div>

    <flux:modal name="tag-form" wire:model="modalForm" focusable flyout variant="floating" class="md:w-lg">
        <div>
            <flux:heading size="lg">{{ $editingTagId ? 'Editar etiqueta' : 'Nueva etiqueta' }}</flux:heading>
            <form wire:submit="save" class="mt-4 space-y-3">
                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="nombre" required maxlength="60" />
                </flux:field>
                <flux:field>
                    <flux:label>Color (hex)</flux:label>
                    <div class="flex gap-2 items-center">
                        <flux:input type="color" wire:model="color" class="h-10 w-14 p-1 cursor-pointer" />
                        <flux:input wire:model="color" type="text" class="flex-1" placeholder="#6366f1" />
                    </div>
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
