<div>
    <flux:heading size="lg">Convertir lead a cliente</flux:heading>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Documento (DNI/CE) es obligatorio para convertir.</p>
    <form wire:submit="convert" class="mt-4 space-y-3">
        <div class="grid grid-cols-2 gap-3">
            <flux:field>
                <flux:label>Tipo documento <span class="text-red-500">*</span></flux:label>
                <select wire:model="tipo_documento" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2" required>
                    <option value="DNI">DNI</option>
                    <option value="CE">CE</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Número documento <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="numero_documento" required />
                <flux:error name="numero_documento" />
            </flux:field>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <flux:field>
                <flux:label>Nombres <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="nombres" required />
                <flux:error name="nombres" />
            </flux:field>
            <flux:field>
                <flux:label>Apellidos <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="apellidos" required />
                <flux:error name="apellidos" />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Teléfono</flux:label>
            <flux:input wire:model="telefono" type="tel" />
        </flux:field>
        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" />
        </flux:field>
        <flux:field>
            <flux:label>Dirección</flux:label>
            <flux:textarea wire:model="direccion" rows="2" placeholder="Opcional" />
        </flux:field>
        <flux:field>
            <flux:checkbox wire:model="activar_membresia" label="Activar membresía al convertir" />
        </flux:field>
        @if($activar_membresia)
        <flux:field>
            <flux:label>Membresía <span class="text-red-500">*</span></flux:label>
            <select wire:model="membresia_id" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                <option value="">Seleccionar</option>
                @foreach($this->membresias as $m)
                <option value="{{ $m->id }}">{{ $m->nombre }} — S/ {{ number_format($m->precio_base, 2) }}</option>
                @endforeach
            </select>
            <flux:error name="membresia_id" />
        </flux:field>
        <flux:field>
            <flux:label>Monto pago inicial</flux:label>
            <flux:input wire:model="pago_monto" type="number" step="0.01" min="0" placeholder="0.00" />
        </flux:field>
        @endif
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" wire:click="$dispatch('close-convert-modal')">Cancelar</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="convert">
                <span wire:loading.remove wire:target="convert">Convertir a cliente</span>
                <span wire:loading wire:target="convert">Convirtiendo...</span>
            </flux:button>
        </div>
    </form>
</div>
