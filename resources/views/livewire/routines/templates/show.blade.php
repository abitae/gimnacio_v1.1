<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $template->nombre }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $template->estado_label }} · {{ $template->objetivo ?: '—' }} · {{ $template->nivel ?: '—' }}</p>
        </div>
        @can('ejercicios-rutinas.update')
        <div class="flex gap-2">
            <flux:button href="{{ route('rutinas-base.builder', $template) }}" size="xs" variant="primary" wire:navigate>Builder</flux:button>
            <flux:button href="{{ route('rutinas-base.index') }}?editar={{ $template->id }}" size="xs" variant="ghost" wire:navigate>Editar</flux:button>
        </div>
        @endcan
    </div>

    @if($template->descripcion)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $template->descripcion }}</p>
    @endif

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-sm">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500 dark:text-zinc-400">Duración</span>
            <p class="font-medium">{{ $template->duracion_semanas ? $template->duracion_semanas . ' semanas' : '—' }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500 dark:text-zinc-400">Días/semana</span>
            <p class="font-medium">{{ $template->frecuencia_dias_semana ?? '—' }}</p>
        </div>
    </div>

    <div class="space-y-4">
        <h2 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">Días de la rutina</h2>
        @foreach($template->days as $day)
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
                @if($day->exercises->isEmpty())
                    <p class="text-zinc-500 dark:text-zinc-400 text-sm">Sin ejercicios en este día.</p>
                @endif
            </flux:card>
        @endforeach
        @if($template->days->isEmpty())
            <p class="text-zinc-500 dark:text-zinc-400">No hay días configurados. Usa el Builder para agregar días y ejercicios.</p>
        @endif
    </div>
</div>
