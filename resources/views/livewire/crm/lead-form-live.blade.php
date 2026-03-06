<div>
    <flux:heading size="lg">{{ $leadId ? 'Editar lead' : 'Nuevo lead' }}</flux:heading>
    <form wire:submit="save" class="mt-4 space-y-3">
        <flux:field>
            <flux:label>Teléfono <span class="text-red-500">*</span></flux:label>
            <flux:input wire:model="telefono" type="tel" placeholder="Ej. 987654321" />
            <flux:error name="telefono" />
        </flux:field>
        <flux:field>
            <flux:label>WhatsApp</flux:label>
            <flux:input wire:model="whatsapp" type="tel" placeholder="Opcional" />
        </flux:field>
        <div class="grid grid-cols-2 gap-3">
            <flux:field>
                <flux:label>Nombres</flux:label>
                <flux:input wire:model="nombres" />
            </flux:field>
            <flux:field>
                <flux:label>Apellidos</flux:label>
                <flux:input wire:model="apellidos" />
            </flux:field>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <flux:field>
                <flux:label>Tipo doc.</flux:label>
                <select wire:model="tipo_documento" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                    <option value="">—</option>
                    <option value="DNI">DNI</option>
                    <option value="CE">CE</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Número doc.</flux:label>
                <flux:input wire:model="numero_documento" />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" />
        </flux:field>
        <flux:field>
            <flux:label>Dirección</flux:label>
            <flux:textarea wire:model="direccion" rows="2" placeholder="Opcional" />
        </flux:field>
        <flux:field>
            <flux:label>Etapa</flux:label>
            <select wire:model="stage_id" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2" required>
                @foreach($this->stages as $s)
                <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </select>
            <flux:error name="stage_id" />
        </flux:field>
        <flux:field>
            <flux:label>Asignado a</flux:label>
            <select wire:model="assigned_to" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                <option value="">—</option>
                @foreach($this->users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Canal origen</flux:label>
            <flux:input wire:model="canal_origen" placeholder="Web, Facebook, Referido..." />
        </flux:field>
        <flux:field>
            <flux:label>Notas</flux:label>
            <flux:textarea wire:model="notas" rows="2" />
        </flux:field>
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" wire:click="$dispatch('close-lead-modal')">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Guardar</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </flux:button>
        </div>
    </form>
</div>
