@props([
    'wireModel',
    'title' => '¿Confirmar?',
    'message' => 'Esta acción no se puede deshacer.',
    'confirmAction',
    'cancelAction' => null,
    'confirmLabel' => 'Eliminar',
    'confirmVariant' => 'danger',
])

<flux:modal name="{{ $wireModel }}-confirm" wire:model="{{ $wireModel }}" focusable class="md:w-sm">
    <flux:heading size="lg">{{ $title }}</flux:heading>
    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $message }}</p>
    <div class="flex justify-end gap-2 mt-4">
        <flux:button type="button" variant="ghost"
            wire:click="{{ $cancelAction ?: ('$set(\''.$wireModel.'\', false)') }}">Cancelar</flux:button>
        <flux:button type="button" variant="{{ $confirmVariant }}" wire:click="{{ $confirmAction }}">{{ $confirmLabel }}</flux:button>
    </div>
</flux:modal>
