<flux:modal name="rol-delete" wire:model="modalState.delete" focusable>
    <div class="space-y-4">
        <flux:heading size="lg">¿Eliminar rol?</flux:heading>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Esta acción no se puede deshacer. Los usuarios perderán este rol.</p>
        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" wire:click="closeModal">Cancelar</flux:button>
            <flux:button variant="primary" color="red" wire:click="delete">Eliminar</flux:button>
        </div>
    </div>
</flux:modal>
