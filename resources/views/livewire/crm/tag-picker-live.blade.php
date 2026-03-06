<div>
    <flux:heading size="lg">Etiquetas</flux:heading>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Selecciona las etiquetas a asignar.</p>
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach($this->tags as $tag)
        <label class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm border cursor-pointer
            {{ in_array((string)$tag->id, $selectedTagIds, true)
                ? 'bg-zinc-800 text-white border-zinc-700 dark:bg-zinc-600 dark:border-zinc-500'
                : 'bg-zinc-100 dark:bg-zinc-800 border-zinc-200 dark:border-zinc-600' }}"
            style="{{ $tag->color ? 'border-color: '.$tag->color.';' : '' }}">
            <input type="checkbox" wire:click="toggleTag({{ $tag->id }})"
                {{ in_array((string)$tag->id, $selectedTagIds, true) ? 'checked' : '' }} class="rounded border-zinc-300">
            <span>{{ $tag->nombre }}</span>
        </label>
        @endforeach
    </div>
    @if($this->tags->isEmpty())
    <p class="text-sm text-zinc-500 mt-2">No hay etiquetas. Crea algunas desde Administración CRM.</p>
    @endif
    <div class="flex justify-end gap-2 mt-4 pt-2">
        <flux:button type="button" variant="ghost" wire:click="$dispatch('close-tags-modal')">Cancelar</flux:button>
        <flux:button wire:click="save" variant="primary">Guardar etiquetas</flux:button>
    </div>
</div>
