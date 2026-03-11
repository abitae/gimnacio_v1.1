<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Registrar seguimiento</h1>
    <p class="text-sm text-zinc-500">Objetivo: {{ \App\Models\Core\NutritionGoal::OBJETIVOS[$goal->objetivo] ?? $goal->objetivo }} — {{ $goal->cliente?->nombres }} {{ $goal->cliente?->apellidos }}</p>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Fecha</flux:label>
            <flux:input type="date" wire:model="form.fecha" required />
        </flux:field>
        <flux:field>
            <flux:label>Peso (kg)</flux:label>
            <flux:input type="number" step="0.01" wire:model="form.peso" placeholder="Opcional" />
        </flux:field>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="2" />
        </flux:field>
        <flux:field>
            <flux:label>Adherencia</flux:label>
            <flux:input wire:model="form.adherencia" placeholder="Opcional" />
        </flux:field>
        <flux:field>
            <flux:label>Progreso general</flux:label>
            <flux:textarea wire:model="form.progreso_general" rows="3" />
        </flux:field>
        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('gestion-nutricional.objetivos.show', $goal) }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
