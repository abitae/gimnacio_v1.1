<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Builder: {{ $template->nombre }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Agrega días y ejercicios a la rutina base</p>
        </div>
        <flux:button href="{{ route('rutinas-base.show', $template) }}" variant="ghost" size="xs" wire:navigate>Ver rutina</flux:button>
    </div>

    <div class="flex gap-2">
        <flux:button size="sm" wire:click="addDay" wire:loading.attr="disabled">+ Agregar día</flux:button>
    </div>

    @foreach($days as $dayIndex => $day)
        @php $dayId = $day['id']; @endphp
        <flux:card class="p-4">
            <div class="flex items-center justify-between gap-2 mb-3">
                <flux:input
                    wire:model.blur="days.{{ $dayIndex }}.nombre"
                    wire:blur="saveDayName({{ $dayIndex }})"
                    placeholder="Nombre del día"
                    class="max-w-xs"
                    size="xs"
                />
                <flux:button size="xs" variant="ghost" wire:click="removeDay({{ $dayId }})" wire:confirm="¿Eliminar este día y sus ejercicios?">Eliminar día</flux:button>
            </div>

            <div class="mb-3 flex flex-wrap gap-2 items-end rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 bg-zinc-50 dark:bg-zinc-900/50">
                <div class="min-w-[180px]">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Ejercicio</label>
                    <select wire:model="newExercise.{{ $dayId }}.exercise_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm">
                        <option value="">Seleccionar</option>
                        @foreach($exercisesForSelect as $ex)
                            <option value="{{ $ex->id }}">{{ $ex->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="w-16">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Series</label>
                    <flux:input type="number" wire:model="newExercise.{{ $dayId }}.series" min="1" size="xs" />
                </div>
                <div class="w-20">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Reps</label>
                    <flux:input wire:model="newExercise.{{ $dayId }}.repeticiones" placeholder="8-12" size="xs" />
                </div>
                <div class="w-20">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Descanso (s)</label>
                    <flux:input type="number" wire:model="newExercise.{{ $dayId }}.descanso_segundos" size="xs" />
                </div>
                <div class="w-28">
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">Método</label>
                    <select wire:model="newExercise.{{ $dayId }}.metodo" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm">
                        @foreach(\App\Models\RoutineTemplateDayExercise::METODOS as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <flux:button size="sm" wire:click="addExerciseToDay({{ $dayId }})" wire:loading.attr="disabled">Agregar</flux:button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-zinc-500 dark:text-zinc-400 border-b border-zinc-200 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-8"></th>
                            <th class="pb-2 pr-2">Ejercicio</th>
                            <th class="pb-2 pr-2 w-16">Series</th>
                            <th class="pb-2 pr-2 w-20">Reps</th>
                            <th class="pb-2 pr-2 w-16">Descanso</th>
                            <th class="pb-2 pr-2 w-24">Método</th>
                            <th class="pb-2 text-right w-20">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($day['exercises'] as $exIndex => $ex)
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50 align-top">
                                <td class="py-1.5 pr-2">
                                    <div class="flex flex-col gap-0.5">
                                        <button type="button" wire:click="moveExerciseUp({{ $dayId }}, {{ $ex['orden'] }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" title="Subir">↑</button>
                                        <button type="button" wire:click="moveExerciseDown({{ $dayId }}, {{ $ex['orden'] }})" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" title="Bajar">↓</button>
                                    </div>
                                </td>
                                <td class="py-1.5 pr-2">{{ $ex['exercise_nombre'] }}</td>
                                <td class="py-1.5 pr-2">
                                    <flux:input type="number" wire:model.blur="days.{{ $dayIndex }}.exercises.{{ $exIndex }}.series" wire:blur="saveExerciseField({{ $dayIndex }}, {{ $exIndex }}, 'series')" size="xs" min="1" class="w-14" />
                                </td>
                                <td class="py-1.5 pr-2">
                                    <flux:input wire:model.blur="days.{{ $dayIndex }}.exercises.{{ $exIndex }}.repeticiones" wire:blur="saveExerciseField({{ $dayIndex }}, {{ $exIndex }}, 'repeticiones')" size="xs" class="w-16" />
                                </td>
                                <td class="py-1.5 pr-2">
                                    <flux:input type="number" wire:model.blur="days.{{ $dayIndex }}.exercises.{{ $exIndex }}.descanso_segundos" wire:blur="saveExerciseField({{ $dayIndex }}, {{ $exIndex }}, 'descanso_segundos')" size="xs" class="w-14" />
                                </td>
                                <td class="py-1.5 pr-2">{{ $ex['metodo'] ? (\App\Models\RoutineTemplateDayExercise::METODOS[$ex['metodo']] ?? $ex['metodo']) : '—' }}</td>
                                <td class="py-1.5 text-right">
                                    <flux:button size="xs" variant="ghost" wire:click="removeExerciseFromDay({{ $ex['id'] }})" wire:confirm="¿Quitar este ejercicio del día?">Quitar</flux:button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(empty($day['exercises']))
                <p class="text-zinc-500 dark:text-zinc-400 text-sm mt-2">Sin ejercicios. Agrega uno arriba.</p>
            @endif
        </flux:card>
    @endforeach

    @if(empty($days))
        <p class="text-zinc-500 dark:text-zinc-400">No hay días. Haz clic en "Agregar día" para empezar.</p>
    @endif
</div>
