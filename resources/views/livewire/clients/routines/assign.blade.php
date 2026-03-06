<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-2xl">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Asignar rutina a cliente</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Busca al cliente por documento y elige una rutina base activa.</p>
    </div>

    <div class="flex flex-wrap gap-2 items-end">
        <flux:field>
            <flux:label>Tipo documento</flux:label>
            <flux:select wire:model="tipo_documento">
                <option value="DNI">DNI</option>
                <option value="CE">CE</option>
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Número documento</flux:label>
            <flux:input wire:model="numero_documento" placeholder="Ej. 12345678" />
        </flux:field>
        <flux:button type="button" variant="primary" size="sm" wire:click="buscarCliente" wire:loading.attr="disabled">Buscar</flux:button>
    </div>
    <flux:error name="numero_documento" />

    @if($cliente)
        <flux:card class="p-3">
            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $cliente->tipo_documento }} {{ $cliente->numero_documento }}</p>
        </flux:card>

        <form wire:submit="asignar" class="space-y-4">
            <flux:field>
                <flux:label>Rutina base</flux:label>
                <flux:select wire:model="routine_template_id" placeholder="Seleccionar rutina">
                    <option value="">Seleccionar rutina</option>
                    @foreach($templates as $t)
                        <option value="{{ $t->id }}">{{ $t->nombre }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="routine_template_id" />
            </flux:field>
            <flux:field>
                <flux:label>Fecha inicio</flux:label>
                <flux:input type="date" wire:model="fecha_inicio" />
                <flux:error name="fecha_inicio" />
            </flux:field>
            <flux:field>
                <flux:label>Objetivo personal (opcional)</flux:label>
                <flux:textarea wire:model="objetivo_personal" rows="2" />
            </flux:field>
            <flux:field>
                <flux:label>Restricciones (opcional)</flux:label>
                <flux:textarea wire:model="restricciones" rows="2" />
            </flux:field>
            <flux:button type="submit" variant="primary" size="sm">Asignar rutina</flux:button>
        </form>
    @endif
</div>
