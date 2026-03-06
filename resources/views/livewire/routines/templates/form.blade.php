<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-3xl">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isCreate ? 'Crear rutina base' : 'Editar rutina base' }}</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $isCreate ? 'Define la plantilla de la rutina.' : 'Modifica los datos de la plantilla.' }}</p>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input wire:model="form.nombre" placeholder="Ej. Rutina fuerza 4 días" />
            <flux:error name="form.nombre" />
        </flux:field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Objetivo</flux:label>
                <flux:input wire:model="form.objetivo" placeholder="Fuerza, hipertrofia..." />
            </flux:field>
            <flux:field>
                <flux:label>Nivel</flux:label>
                <flux:input wire:model="form.nivel" placeholder="Principiante, Intermedio" />
            </flux:field>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Duración (semanas)</flux:label>
                <flux:input type="number" wire:model="form.duracion_semanas" placeholder="Opcional" min="1" />
                <flux:error name="form.duracion_semanas" />
            </flux:field>
            <flux:field>
                <flux:label>Frecuencia (días/semana)</flux:label>
                <flux:input type="number" wire:model="form.frecuencia_dias_semana" placeholder="Ej. 4" min="1" max="7" />
                <flux:error name="form.frecuencia_dias_semana" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Descripción</flux:label>
            <flux:textarea wire:model="form.descripcion" rows="3" />
        </flux:field>

        <flux:field>
            <flux:label>Tags (separados por coma)</flux:label>
            <flux:input wire:model="tagsInput" placeholder="Ej. fuerza, full body" />
        </flux:field>

        <flux:field>
            <flux:label>Estado</flux:label>
            <flux:select wire:model="form.estado">
                @foreach(\App\Models\RoutineTemplate::ESTADOS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </flux:select>
        </flux:field>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" size="sm">Guardar</flux:button>
            <flux:button href="{{ route('rutinas-base.index') }}" variant="ghost" size="sm" wire:navigate>Cancelar</flux:button>
            @if(!$isCreate && $templateId)
                <flux:button href="{{ route('rutinas-base.builder', $templateId) }}" variant="ghost" size="sm" wire:navigate>Ir al Builder</flux:button>
            @endif
        </div>
    </form>
</div>
