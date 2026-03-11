<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $rental ? 'Editar reserva' : 'Nueva reserva' }}</h1>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Espacio</flux:label>
            <select wire:model="form.rentable_space_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2" required>
                <option value="">Seleccionar</option>
                @foreach($spaces as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Fecha</flux:label>
            <flux:input type="date" wire:model="form.fecha" required />
        </flux:field>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Hora inicio</flux:label>
                <flux:input type="time" wire:model="form.hora_inicio" required />
            </flux:field>
            <flux:field>
                <flux:label>Hora fin</flux:label>
                <flux:input type="time" wire:model="form.hora_fin" required />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Cliente (opcional)</flux:label>
            <select wire:model="form.cliente_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="">Sin cliente</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}">{{ $c->nombres }} {{ $c->apellidos }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Nombre externo (si no es cliente)</flux:label>
            <flux:input wire:model="form.nombre_externo" />
        </flux:field>
        <flux:field>
            <flux:label>Precio (S/)</flux:label>
            <flux:input type="number" step="0.01" wire:model="form.precio" required />
        </flux:field>
        <flux:field>
            <flux:label>Estado</flux:label>
            <select wire:model="form.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                @foreach(\App\Models\Core\Rental::ESTADOS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="2" />
        </flux:field>
        <div class="flex gap-2">
            <flux:button variant="ghost" type="button" href="{{ route('rentals.calendar.index') }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
