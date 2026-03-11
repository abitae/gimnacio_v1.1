<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $employee ? 'Editar empleado' : 'Nuevo empleado' }}</h1>
    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Nombres</flux:label>
                <flux:input wire:model="form.nombres" required />
            </flux:field>
            <flux:field>
                <flux:label>Apellidos</flux:label>
                <flux:input wire:model="form.apellidos" required />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Documento</flux:label>
            <flux:input wire:model="form.documento" required />
        </flux:field>
        <flux:field>
            <flux:label>Cargo</flux:label>
            <flux:input wire:model="form.cargo" />
        </flux:field>
        <flux:field>
            <flux:label>Área</flux:label>
            <flux:input wire:model="form.area" />
        </flux:field>
        <flux:field>
            <flux:label>Teléfono</flux:label>
            <flux:input wire:model="form.telefono" type="tel" />
        </flux:field>
        <flux:field>
            <flux:label>Fecha ingreso</flux:label>
            <flux:input type="date" wire:model="form.fecha_ingreso" />
        </flux:field>
        <flux:field>
            <flux:label>Estado</flux:label>
            <select wire:model="form.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </flux:field>
        <div class="flex gap-2">
            <flux:button variant="ghost" type="button" href="{{ route('employees.index') }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
