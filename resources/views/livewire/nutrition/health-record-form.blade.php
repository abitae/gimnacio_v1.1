<div class="space-y-3">
    @if($cliente)
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Cliente: {{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
    @endif
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Enfermedades</flux:label>
            <flux:textarea wire:model="form.enfermedades" rows="2" placeholder="Enfermedades conocidas" />
        </flux:field>
        <flux:field>
            <flux:label>Alergias</flux:label>
            <flux:textarea wire:model="form.alergias" rows="2" placeholder="Alergias" />
        </flux:field>
        <flux:field>
            <flux:label>Medicación</flux:label>
            <flux:textarea wire:model="form.medicacion" rows="2" placeholder="Medicación actual" />
        </flux:field>
        <flux:field>
            <flux:label>Restricciones médicas</flux:label>
            <flux:textarea wire:model="form.restricciones_medicas" rows="2" />
        </flux:field>
        <flux:field>
            <flux:label>Lesiones</flux:label>
            <flux:textarea wire:model="form.lesiones" rows="2" placeholder="Lesiones o limitaciones" />
        </flux:field>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="2" />
        </flux:field>
        <div class="flex justify-end gap-2 pt-2">
            <flux:button variant="ghost" type="button" wire:click="$dispatch('close-salud-modal')">Cancelar</flux:button>
            <flux:button type="submit" wire:loading.attr="disabled">Guardar</flux:button>
        </div>
    </form>
</div>
