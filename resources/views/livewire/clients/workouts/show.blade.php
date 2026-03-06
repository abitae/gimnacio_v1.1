<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Sesión {{ $workoutSession->fecha_hora->format('d/m/Y H:i') }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $cliente->nombres }} {{ $cliente->apellidos }} · {{ $workoutSession->estado_label }} @if($workoutSession->clientRoutineDay) · {{ $workoutSession->clientRoutineDay->nombre }} @endif</p>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('clientes.rutinas.sesiones.index', [$cliente, $workoutSession->clientRoutine]) }}" variant="ghost" size="xs" wire:navigate>Volver a sesiones</flux:button>
            @if($workoutSession->estado === 'iniciada')
                @can('ejercicios-rutinas.update')
                <flux:button size="xs" variant="primary" wire:click="completar" wire:confirm="¿Marcar esta sesión como completada?">Completar sesión</flux:button>
                @endcan
            @endif
        </div>
    </div>

    @if($workoutSession->notas)
        <flux:card class="p-3">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Notas</p>
            <p class="text-sm">{{ $workoutSession->notas }}</p>
        </flux:card>
    @endif

    @php
        $volumenTotal = 0;
    @endphp
    @foreach($workoutSession->sessionExercises as $se)
        <flux:card class="p-4">
            <h3 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">
                <a href="{{ route('ejercicios.show', $se->exercise) }}" wire:navigate class="hover:underline">{{ $se->exercise?->nombre ?? '—' }}</a>
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                            <th class="pb-2 pr-2">Set</th>
                            <th class="pb-2 pr-2">Peso</th>
                            <th class="pb-2 pr-2">Reps</th>
                            <th class="pb-2 pr-2">RPE</th>
                            <th class="pb-2 pr-2">Notas</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($se->sets as $set)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                <td class="py-1.5 pr-2">{{ $set->set_numero }}</td>
                                <td class="py-1.5 pr-2">{{ $set->peso !== null ? $set->peso . ' kg' : '—' }}</td>
                                <td class="py-1.5 pr-2">{{ $set->repeticiones ?? '—' }}</td>
                                <td class="py-1.5 pr-2">{{ $set->rpe ?? '—' }}</td>
                                <td class="py-1.5 pr-2">{{ $set->notas ?? '—' }}</td>
                            </tr>
                            @if($set->peso !== null && $set->repeticiones !== null)
                                @php $volumenTotal += (float) $set->peso * (int) $set->repeticiones; @endphp
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endforeach

    @if($volumenTotal > 0)
        <p class="text-sm text-zinc-500 dark:text-zinc-400">Volumen total (kg): <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($volumenTotal, 1) }}</span></p>
    @endif
</div>
