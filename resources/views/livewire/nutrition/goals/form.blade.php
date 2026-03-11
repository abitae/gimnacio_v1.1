<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-2xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $goal ? 'Editar objetivo' : 'Nuevo objetivo nutricional' }}</h1>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Cliente</flux:label>
            <select wire:model="form.cliente_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2" required>
                <option value="">Seleccionar</option>
                @foreach($clientes as $c)
                    <option value="{{ $c->id }}">{{ $c->nombres }} {{ $c->apellidos }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Trainer responsable</flux:label>
            <select wire:model="form.trainer_user_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2" required>
                @foreach($trainers as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Objetivo</flux:label>
            <select wire:model="form.objetivo" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                @foreach(\App\Models\Core\NutritionGoal::OBJETIVOS as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </flux:field>
        @if($form['objetivo'] === 'personalizado')
        <flux:field>
            <flux:label>Objetivo personalizado</flux:label>
            <flux:input wire:model="form.objetivo_personalizado" />
        </flux:field>
        @endif
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Fecha inicio</flux:label>
                <flux:input type="date" wire:model="form.fecha_inicio" required />
            </flux:field>
            <flux:field>
                <flux:label>Fecha objetivo</flux:label>
                <flux:input type="date" wire:model="form.fecha_objetivo" />
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="3" />
        </flux:field>
        <flux:field>
            <flux:label>Estado</flux:label>
            <select wire:model="form.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="activo">Activo</option>
                <option value="cumplido">Cumplido</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </flux:field>
        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('gestion-nutricional.objetivos.index') }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
