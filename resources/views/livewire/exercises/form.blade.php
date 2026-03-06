<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-3xl">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $isCreate ? 'Crear ejercicio' : 'Editar ejercicio' }}</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $isCreate ? 'Completa los datos del nuevo ejercicio.' : 'Modifica los datos del ejercicio.' }}</p>
    </div>

    <form wire:submit="save" class="space-y-4">
        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input wire:model="form.nombre" placeholder="Nombre del ejercicio" />
            <flux:error name="form.nombre" />
        </flux:field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Grupo muscular principal</flux:label>
                <flux:input wire:model="form.grupo_muscular_principal" placeholder="Ej. Pecho, Espalda" />
                <flux:error name="form.grupo_muscular_principal" />
            </flux:field>
            <flux:field>
                <flux:label>Tipo</flux:label>
                <flux:select wire:model="form.tipo">
                    @foreach(\App\Models\Exercise::TIPOS as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="form.tipo" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Músculos secundarios (separados por coma)</flux:label>
            <flux:input wire:model="musculosSecundariosInput" placeholder="Ej. Tríceps, Hombros" />
        </flux:field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Nivel</flux:label>
                <flux:input wire:model="form.nivel" placeholder="Principiante, Intermedio, Avanzado" />
                <flux:error name="form.nivel" />
            </flux:field>
            <flux:field>
                <flux:label>Equipamiento</flux:label>
                <flux:input wire:model="form.equipamiento" placeholder="Máquina, Mancuernas, Barra..." />
                <flux:error name="form.equipamiento" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>Descripción técnica</flux:label>
            <flux:textarea wire:model="form.descripcion_tecnica" placeholder="Ejecución del ejercicio" rows="3" />
            <flux:error name="form.descripcion_tecnica" />
        </flux:field>

        <flux:field>
            <flux:label>Errores comunes</flux:label>
            <flux:textarea wire:model="form.errores_comunes" rows="2" />
            <flux:error name="form.errores_comunes" />
        </flux:field>

        <flux:field>
            <flux:label>Consejos de seguridad</flux:label>
            <flux:textarea wire:model="form.consejos_seguridad" rows="2" />
            <flux:error name="form.consejos_seguridad" />
        </flux:field>

        <flux:field>
            <flux:label>URL de video</flux:label>
            <flux:input wire:model="form.video_url" type="url" placeholder="https://..." />
            <flux:error name="form.video_url" />
        </flux:field>

        <flux:field>
            <flux:label>Estado</flux:label>
            <flux:select wire:model="form.estado">
                @foreach(\App\Models\Exercise::ESTADOS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </flux:select>
            <flux:error name="form.estado" />
        </flux:field>

        <div class="flex gap-2">
            <flux:button type="submit" variant="primary" size="sm">Guardar</flux:button>
            @if($isCreate)
                <flux:button href="{{ route('ejercicios.index') }}" variant="ghost" size="sm" wire:navigate>Cancelar</flux:button>
            @else
                <flux:button href="{{ route('ejercicios.show', $exerciseId) }}" variant="ghost" size="sm" wire:navigate>Cancelar</flux:button>
            @endif
        </div>
    </form>
</div>
