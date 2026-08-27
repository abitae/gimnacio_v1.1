<div>
    <flux:heading size="lg">{{ $taskId ? 'Editar tarea' : 'Nueva tarea' }}</flux:heading>
    <form wire:submit="save" class="mt-4 space-y-3">
        <flux:field>
            <flux:label>Tipo <span class="text-red-500">*</span></flux:label>
            <flux:select wire:model="tipo" required>
                @foreach($this->tipos as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Fecha y hora programada <span class="text-red-500">*</span></flux:label>
            <flux:input wire:model="fecha_hora_programada" type="datetime-local" />
            <flux:error name="fecha_hora_programada" />
        </flux:field>
        <flux:field>
            <flux:label>Prioridad</flux:label>
            <flux:select wire:model="prioridad">
                @foreach($this->prioridades as $key => $label)
                <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Asignado a</flux:label>
            <flux:select wire:model="assigned_to">
                @foreach($this->users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Notas</flux:label>
            <flux:textarea wire:model="notas" rows="2" />
        </flux:field>
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" wire:click="$dispatch('close-task-modal')">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Guardar</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </flux:button>
        </div>
    </form>
</div>
