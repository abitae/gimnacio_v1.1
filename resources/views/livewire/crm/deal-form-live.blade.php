<div>
    <flux:heading size="lg">{{ $dealId ? 'Editar oportunidad' : 'Nueva oportunidad' }}</flux:heading>
    @if($dealId && !$showMarkLost)
    <div class="flex gap-2 mt-2">
        <flux:button size="xs" variant="primary" wire:click="markWon">Marcar ganada</flux:button>
        <flux:button size="xs" variant="danger" wire:click="$set('showMarkLost', true)">Marcar perdida</flux:button>
    </div>
    @endif
    @if($showMarkLost)
    <div class="mt-3 p-3 rounded-lg border border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-950/30">
        <flux:label>Motivo de pérdida <span class="text-red-500">*</span></flux:label>
        <select wire:model="motivo_perdida_id" class="flux-input rounded-lg w-full mt-1 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2" required>
            <option value="">Seleccionar</option>
            @foreach($this->lossReasons as $r)
            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
            @endforeach
        </select>
        <flux:label class="mt-2">Observación</flux:label>
        <flux:textarea wire:model="observacion_perdida" rows="2" class="mt-1" />
        <div class="flex gap-2 mt-2">
            <flux:button size="xs" wire:click="markLost">Confirmar perdida</flux:button>
            <flux:button size="xs" variant="ghost" wire:click="$set('showMarkLost', false)">Cancelar</flux:button>
        </div>
    </div>
    @else
    <form wire:submit="save" class="mt-4 space-y-3">
        <flux:field>
            <flux:label>Membresía sugerida</flux:label>
            <select wire:model="membresia_id" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                <option value="">—</option>
                @foreach($this->membresias as $m)
                <option value="{{ $m->id }}">{{ $m->nombre }} — S/ {{ number_format($m->precio_base, 2) }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Precio objetivo <span class="text-red-500">*</span></flux:label>
            <flux:input wire:model="precio_objetivo" type="number" step="0.01" min="0" />
            <flux:error name="precio_objetivo" />
        </flux:field>
        <flux:field>
            <flux:label>Descuento sugerido</flux:label>
            <flux:input wire:model="descuento_sugerido" type="number" step="0.01" min="0" />
        </flux:field>
        <flux:field>
            <flux:label>Probabilidad (%)</flux:label>
            <flux:input wire:model="probabilidad" type="number" min="0" max="100" />
        </flux:field>
        <flux:field>
            <flux:label>Fecha estimada cierre</flux:label>
            <flux:input wire:model="fecha_estimada_cierre" type="date" />
        </flux:field>
        <flux:field>
            <flux:label>Motivo de interés</flux:label>
            <flux:textarea wire:model="motivo_interes" rows="2" />
        </flux:field>
        <flux:field>
            <flux:label>Objeciones</flux:label>
            <flux:textarea wire:model="objeciones" rows="2" />
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
            <flux:label>Notas</flux:label>
            <flux:textarea wire:model="notas" rows="2" />
        </flux:field>
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" wire:click="$dispatch('close-deal-modal')">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Guardar</span>
                <span wire:loading wire:target="save">Guardando...</span>
            </flux:button>
        </div>
    </form>
    @endif
</div>
