<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $space ? 'Editar espacio' : 'Nuevo espacio' }}</h1>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input wire:model="form.nombre" required />
        </flux:field>
        <flux:field>
            <flux:label>Capacidad</flux:label>
            <flux:input type="number" min="0" wire:model="form.capacidad" />
        </flux:field>
        <flux:field>
            <flux:label>Descripción</flux:label>
            <flux:textarea wire:model="form.descripcion" rows="2" />
        </flux:field>
        <flux:field>
            <flux:label>Estado</flux:label>
            <select wire:model="form.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Color calendario</flux:label>
            <select wire:model="form.color_calendario" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                @foreach(\App\Models\Core\RentableSpace::COLORES_CALENDARIO as $hex => $nombre)
                    <option value="{{ $hex }}">{{ $nombre }}</option>
                @endforeach
            </select>
            <div class="mt-1 h-6 w-12 rounded border border-zinc-300 dark:border-zinc-600" style="background-color: {{ $form['color_calendario'] ?? '#3B82F6' }}"></div>
        </flux:field>
        <div class="flex gap-2">
            <flux:button variant="ghost" type="button" href="{{ route('rentals.spaces.index') }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
