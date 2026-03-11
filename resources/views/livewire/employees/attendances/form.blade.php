<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Registrar asistencia</h1>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Empleado</flux:label>
            <select wire:model="form.employee_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2" required>
                <option value="">Seleccionar</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}">{{ $e->nombres }} {{ $e->apellidos }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Fecha</flux:label>
            <flux:input type="date" wire:model="form.fecha" required />
        </flux:field>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Hora ingreso</flux:label>
                <flux:input type="time" wire:model="form.hora_ingreso" />
            </flux:field>
            <flux:field>
                <flux:label>Hora salida</flux:label>
                <flux:input type="time" wire:model="form.hora_salida" />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="2" />
        </flux:field>
        <div class="flex gap-2">
            <flux:button variant="ghost" type="button" href="{{ route('employees.attendances.index') }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
