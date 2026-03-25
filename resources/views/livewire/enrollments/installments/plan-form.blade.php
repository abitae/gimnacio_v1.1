<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Crear plan de cuotas</h1>
    <p class="text-sm text-zinc-500">{{ $cliente->nombres }} {{ $cliente->apellidos }} — {{ $clienteMatricula->nombre }}</p>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Monto total (S/)</flux:label>
            <flux:input type="number" step="0.01" wire:model="form.monto_total" required />
        </flux:field>
        <flux:field>
            <flux:label>Número de cuotas</flux:label>
            <flux:input type="number" min="2" max="60" wire:model="form.numero_cuotas" required />
        </flux:field>
        <flux:field>
            <flux:label>Frecuencia</flux:label>
            <select wire:model="form.frecuencia" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                @foreach(\App\Models\Core\EnrollmentInstallmentPlan::FRECUENCIAS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Fecha inicio</flux:label>
            <flux:input type="date" wire:model="form.fecha_inicio" required />
        </flux:field>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="2" />
        </flux:field>
        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('clientes.cuotas', ['cliente' => $cliente->id, 'matricula' => $clienteMatricula->id]) }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Crear plan</flux:button>
        </div>
    </form>
</div>
