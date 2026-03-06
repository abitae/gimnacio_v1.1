<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $clientRoutine->routineTemplate?->nombre ?? 'Rutina' }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $cliente->nombres }} {{ $cliente->apellidos }} · {{ $clientRoutine->estado_label }} · Desde {{ $clientRoutine->fecha_inicio->format('d/m/Y') }}</p>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('clientes.rutinas.index', $cliente) }}" variant="ghost" size="xs" wire:navigate>Volver a rutinas</flux:button>
            <flux:button href="{{ route('clientes.rutinas.sesiones.index', [$cliente, $clientRoutine]) }}" variant="ghost" size="xs" wire:navigate>Sesiones</flux:button>
            @can('ejercicios-rutinas.update')
            <flux:button href="{{ route('clientes.rutinas.sesiones.create', [$cliente, $clientRoutine]) }}" variant="primary" size="xs" wire:navigate>Registrar sesión</flux:button>
            @endcan
        </div>
    </div>

    @if($clientRoutine->objetivo_personal)
        <flux:card class="p-3">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Objetivo personal</p>
            <p class="text-sm">{{ $clientRoutine->objetivo_personal }}</p>
        </flux:card>
    @endif

    @foreach($clientRoutine->days as $day)
        <flux:card class="p-4">
            <h3 class="font-medium text-zinc-900 dark:text-zinc-100 mb-2">{{ $day->nombre }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                            <th class="pb-2 pr-2">#</th>
                            <th class="pb-2 pr-2">Ejercicio</th>
                            <th class="pb-2 pr-2">Series</th>
                            <th class="pb-2 pr-2">Reps</th>
                            <th class="pb-2 pr-2">Descanso</th>
                            <th class="pb-2 pr-2">Método</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($day->exercises as $ex)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                                <td class="py-1.5 pr-2">{{ $ex->orden }}</td>
                                <td class="py-1.5 pr-2">
                                    <a href="{{ route('ejercicios.show', $ex->exercise) }}" wire:navigate class="text-zinc-900 dark:text-zinc-100 hover:underline">{{ $ex->exercise?->nombre ?? '—' }}</a>
                                </td>
                                <td class="py-1.5 pr-2">{{ $ex->series }}</td>
                                <td class="py-1.5 pr-2">{{ $ex->repeticiones ?? '—' }}</td>
                                <td class="py-1.5 pr-2">{{ $ex->descanso_segundos ? $ex->descanso_segundos . ' s' : '—' }}</td>
                                <td class="py-1.5 pr-2">{{ $ex->metodo_label }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </flux:card>
    @endforeach
</div>
