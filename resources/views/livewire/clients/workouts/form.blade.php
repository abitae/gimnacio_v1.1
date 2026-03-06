<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Registrar sesión</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
        </div>
        <flux:button href="{{ route('clientes.rutinas.sesiones.index', [$cliente, $clientRoutine]) }}" variant="ghost" size="xs" wire:navigate>Volver a sesiones</flux:button>
    </div>

    <flux:field>
        <flux:label>Día de la rutina</flux:label>
        <flux:select wire:model.live="client_routine_day_id" placeholder="Seleccionar día">
            <option value="">Seleccionar día</option>
            @foreach($clientRoutine->days as $day)
                <option value="{{ $day->id }}">{{ $day->nombre }}</option>
            @endforeach
        </flux:select>
        <flux:error name="client_routine_day_id" />
    </flux:field>

    @if($client_routine_day_id)
        <flux:field>
            <flux:label>Notas (opcional)</flux:label>
            <flux:textarea wire:model="notas" rows="2" />
        </flux:field>

        @foreach($exercises as $exIndex => $ex)
            <flux:card class="p-4">
                <h3 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">{{ $ex['exercise_nombre'] }}</h3>
                <div class="space-y-2">
                    @foreach($ex['sets'] as $setIndex => $set)
                        <div class="flex flex-wrap gap-2 items-center">
                            <span class="text-xs text-zinc-500 w-8">Set {{ $setIndex + 1 }}</span>
                            <flux:input type="number" step="0.01" wire:model="exercises.{{ $exIndex }}.sets.{{ $setIndex }}.peso" placeholder="Peso" size="xs" class="w-20" />
                            <flux:input type="number" wire:model="exercises.{{ $exIndex }}.sets.{{ $setIndex }}.repeticiones" placeholder="Reps" size="xs" class="w-16" />
                            <flux:input type="number" step="0.1" wire:model="exercises.{{ $exIndex }}.sets.{{ $setIndex }}.rpe" placeholder="RPE" size="xs" class="w-14" />
                            <flux:input wire:model="exercises.{{ $exIndex }}.sets.{{ $setIndex }}.notas" placeholder="Notas" size="xs" class="flex-1 min-w-[80px]" />
                            @if(count($ex['sets']) > 1)
                                <flux:button size="xs" variant="ghost" wire:click="removeSet({{ $exIndex }}, {{ $setIndex }})">Quitar</flux:button>
                            @endif
                        </div>
                    @endforeach
                    <flux:button size="xs" variant="ghost" wire:click="addSet({{ $exIndex }})">+ Set</flux:button>
                </div>
            </flux:card>
        @endforeach

        <div class="flex gap-2">
            <flux:button variant="primary" size="sm" wire:click="guardar" wire:loading.attr="disabled">Guardar sesión</flux:button>
        </div>
    @endif
</div>
