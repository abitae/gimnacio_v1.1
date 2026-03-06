<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $exercise->nombre }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $exercise->tipo_label }} · {{ $exercise->estado_label }}</p>
        </div>
        @can('ejercicios-rutinas.update')
        <flux:button href="{{ route('ejercicios.index') }}?editar={{ $exercise->id }}" size="xs" variant="primary" wire:navigate>Editar</flux:button>
        @endcan
    </div>

    <div class="flex gap-1 border-b border-zinc-200 dark:border-zinc-700 mb-4">
        <button type="button" wire:click="$set('activeTab', 'detalle')" class="px-3 py-2 text-sm font-medium rounded-t-lg {{ $activeTab === 'detalle' ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">Detalle</button>
        <button type="button" wire:click="$set('activeTab', 'variaciones')" class="px-3 py-2 text-sm font-medium rounded-t-lg {{ $activeTab === 'variaciones' ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">Variaciones</button>
        <button type="button" wire:click="$set('activeTab', 'sustituciones')" class="px-3 py-2 text-sm font-medium rounded-t-lg {{ $activeTab === 'sustituciones' ? 'bg-zinc-200 dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-800' }}">Sustituciones</button>
    </div>

    @if($activeTab === 'detalle')
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Grupo muscular principal</p>
                <p class="font-medium">{{ $exercise->grupo_muscular_principal ?? '—' }}</p>
            </flux:card>
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Nivel</p>
                <p class="font-medium">{{ $exercise->nivel ?? '—' }}</p>
            </flux:card>
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Equipamiento</p>
                <p class="font-medium">{{ $exercise->equipamiento ?? '—' }}</p>
            </flux:card>
            @if($exercise->musculos_secundarios && count($exercise->musculos_secundarios) > 0)
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Músculos secundarios</p>
                <p class="font-medium">{{ implode(', ', $exercise->musculos_secundarios) }}</p>
            </flux:card>
            @endif
        </div>
        @if($exercise->descripcion_tecnica)
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Descripción técnica</p>
                <p class="whitespace-pre-wrap">{{ $exercise->descripcion_tecnica }}</p>
            </flux:card>
        @endif
        @if($exercise->errores_comunes)
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Errores comunes</p>
                <p class="whitespace-pre-wrap">{{ $exercise->errores_comunes }}</p>
            </flux:card>
        @endif
        @if($exercise->consejos_seguridad)
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Consejos de seguridad</p>
                <p class="whitespace-pre-wrap">{{ $exercise->consejos_seguridad }}</p>
            </flux:card>
        @endif
        @if($exercise->video_url)
            <flux:card class="p-4">
                <p class="text-zinc-500 dark:text-zinc-400 mb-1">Video</p>
                <a href="{{ $exercise->video_url }}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">{{ $exercise->video_url }}</a>
            </flux:card>
        @endif
    @endif

    @if($activeTab === 'variaciones')
        <div class="space-y-3">
            @can('ejercicios-rutinas.update')
            <div class="flex flex-wrap gap-2 items-end">
                <flux:field class="min-w-[200px]">
                    <flux:label>Agregar variación</flux:label>
                    <flux:select wire:model="relatedExerciseId" placeholder="Ejercicio">
                        <option value="">Seleccionar ejercicio</option>
                        @foreach(\App\Models\Exercise::where('id', '!=', $exercise->id)->where('estado', 'activo')->orderBy('nombre')->get() as $ex)
                            <option value="{{ $ex->id }}">{{ $ex->nombre }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:button size="sm" wire:click="addRelation('variation')" wire:loading.attr="disabled">Agregar</flux:button>
            </div>
            @endcan
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($exercise->variations as $variation)
                    <li class="flex items-center justify-between py-2">
                        <a href="{{ route('ejercicios.show', $variation) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $variation->nombre }}</a>
                        @can('ejercicios-rutinas.update')
                        <flux:button size="xs" variant="ghost" wire:click="removeRelation({{ $variation->id }}, 'variation')" wire:confirm="¿Quitar esta variación?">Quitar</flux:button>
                        @endcan
                    </li>
                @empty
                    <li class="py-2 text-zinc-500 dark:text-zinc-400">No hay variaciones definidas.</li>
                @endforelse
            </ul>
        </div>
    @endif

    @if($activeTab === 'sustituciones')
        <div class="space-y-3">
            @can('ejercicios-rutinas.update')
            <div class="flex flex-wrap gap-2 items-end">
                <flux:field class="min-w-[200px]">
                    <flux:label>Agregar sustituto</flux:label>
                    <flux:select wire:model="relatedExerciseId" placeholder="Ejercicio">
                        <option value="">Seleccionar ejercicio</option>
                        @foreach(\App\Models\Exercise::where('id', '!=', $exercise->id)->where('estado', 'activo')->orderBy('nombre')->get() as $ex)
                            <option value="{{ $ex->id }}">{{ $ex->nombre }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>
                <flux:button size="sm" wire:click="addRelation('substitution')" wire:loading.attr="disabled">Agregar</flux:button>
            </div>
            @endcan
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($exercise->substitutions as $sub)
                    <li class="flex items-center justify-between py-2">
                        <a href="{{ route('ejercicios.show', $sub) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $sub->nombre }}</a>
                        @can('ejercicios-rutinas.update')
                        <flux:button size="xs" variant="ghost" wire:click="removeRelation({{ $sub->id }}, 'substitution')" wire:confirm="¿Quitar este sustituto?">Quitar</flux:button>
                        @endcan
                    </li>
                @empty
                    <li class="py-2 text-zinc-500 dark:text-zinc-400">No hay sustitutos definidos.</li>
                @endforelse
            </ul>
        </div>
    @endif
</div>
