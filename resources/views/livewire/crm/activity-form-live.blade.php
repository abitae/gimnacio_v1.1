<div>
    <flux:heading size="lg">{{ $activityId ? 'Editar actividad' : 'Nueva actividad' }}</flux:heading>
    <form wire:submit="save" class="mt-4 space-y-3">
        <flux:field>
            <flux:label>Tipo <span class="text-red-500">*</span></flux:label>
            <select wire:model="tipo" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2" required>
                @foreach($this->tipos as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <flux:error name="tipo" />
        </flux:field>
        <flux:field>
            <flux:label>Fecha y hora <span class="text-red-500">*</span></flux:label>
            <flux:input wire:model="fecha_hora" type="datetime-local" />
            <flux:error name="fecha_hora" />
        </flux:field>
        <flux:field>
            <flux:label>Resultado</flux:label>
            <flux:input wire:model="resultado" />
        </flux:field>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="observaciones" rows="3" />
        </flux:field>
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" wire:click="$dispatch('close-activity-modal')">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Guardar</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </flux:button>
        </div>
    </form>
</div>
