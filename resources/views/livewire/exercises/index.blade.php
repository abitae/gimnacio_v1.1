<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Ejercicios</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Catálogo de ejercicios</p>
            </div>
            @can('ejercicios-rutinas.create')
            <flux:button icon="plus" variant="primary" size="xs" wire:click="openCreateModal" wire:loading.attr="disabled" wire:target="openCreateModal">
                <span wire:loading.remove wire:target="openCreateModal">Nuevo ejercicio</span>
                <span wire:loading wire:target="openCreateModal">...</span>
            </flux:button>
            @endcan
        </div>

        <div class="flex flex-wrap gap-2 items-center">
            <flux:input type="search" placeholder="Buscar..." wire:model.live.debounce.300ms="search" class="w-48" size="xs" />
            <select wire:model.live="grupoMuscular" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs" size="xs">
                <option value="">Grupo muscular</option>
                @foreach($gruposMusculares as $g)
                    <option value="{{ $g }}">{{ $g }}</option>
                @endforeach
            </select>
            <select wire:model.live="tipo" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Tipo</option>
                @foreach(\App\Models\Exercise::TIPOS as $k => $v)
                    <option value="{{ $k }}">{{ $v }}</option>
                @endforeach
            </select>
            <select wire:model.live="nivel" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Nivel</option>
                @foreach($niveles as $n)
                    <option value="{{ $n }}">{{ $n }}</option>
                @endforeach
            </select>
            <select wire:model.live="equipamiento" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Equipamiento</option>
                @foreach($equipamientos as $e)
                    <option value="{{ $e }}">{{ $e }}</option>
                @endforeach
            </select>
            <select wire:model.live="estadoFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Estado</option>
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
            <select wire:model.live="perPage" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Grupo muscular</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tipo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nivel</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($exercises as $ex)
                        <tr class="bg-white dark:bg-zinc-800">
                            <td class="px-4 py-2">
                                <a href="{{ route('ejercicios.show', $ex) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $ex->nombre }}</a>
                            </td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $ex->grupo_muscular_principal ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $ex->tipo_label }}</td>
                            <td class="px-4 py-2">{{ $ex->nivel ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $ex->estado_label }}</td>
                            <td class="px-4 py-2 text-right">
                                @can('ejercicios-rutinas.update')
                                <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $ex->id }})" wire:loading.attr="disabled">Editar</flux:button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">No hay ejercicios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-2">
            {{ $exercises->links() }}
        </div>
    </div>

    <!-- Modal Crear/Editar Ejercicio -->
    <flux:modal name="exercise-form-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:max-w-2xl max-h-[90vh] overflow-y-auto">
        <form wire:submit="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $exerciseId ? 'Editar ejercicio' : 'Nuevo ejercicio' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $exerciseId ? 'Modifica los datos del ejercicio' : 'Completa los datos del nuevo ejercicio' }}
                    </p>
                </div>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input size="xs" wire:model="form.nombre" placeholder="Nombre del ejercicio" />
                    <flux:error name="form.nombre" />
                </flux:field>

                <div class="grid grid-cols-2 gap-2">
                    <flux:field>
                        <flux:label>Grupo muscular principal</flux:label>
                        <flux:input size="xs" wire:model="form.grupo_muscular_principal" placeholder="Ej. Pecho, Espalda" />
                        <flux:error name="form.grupo_muscular_principal" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Tipo</flux:label>
                        <flux:select size="xs" wire:model="form.tipo">
                            @foreach(\App\Models\Exercise::TIPOS as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="form.tipo" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Músculos secundarios (separados por coma)</flux:label>
                    <flux:input size="xs" wire:model="musculosSecundariosInput" placeholder="Ej. Tríceps, Hombros" />
                </flux:field>

                <div class="grid grid-cols-2 gap-2">
                    <flux:field>
                        <flux:label>Nivel</flux:label>
                        <flux:input size="xs" wire:model="form.nivel" placeholder="Principiante, Intermedio" />
                        <flux:error name="form.nivel" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Equipamiento</flux:label>
                        <flux:input size="xs" wire:model="form.equipamiento" placeholder="Máquina, Mancuernas..." />
                        <flux:error name="form.equipamiento" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Descripción técnica</flux:label>
                    <flux:textarea size="xs" wire:model="form.descripcion_tecnica" placeholder="Ejecución del ejercicio" rows="2" />
                    <flux:error name="form.descripcion_tecnica" />
                </flux:field>

                <flux:field>
                    <flux:label>Errores comunes</flux:label>
                    <flux:textarea size="xs" wire:model="form.errores_comunes" rows="2" />
                    <flux:error name="form.errores_comunes" />
                </flux:field>

                <flux:field>
                    <flux:label>Consejos de seguridad</flux:label>
                    <flux:textarea size="xs" wire:model="form.consejos_seguridad" rows="2" />
                    <flux:error name="form.consejos_seguridad" />
                </flux:field>

                <flux:field>
                    <flux:label>URL de video</flux:label>
                    <flux:input size="xs" wire:model="form.video_url" type="url" placeholder="https://..." />
                    <flux:error name="form.video_url" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <flux:select size="xs" wire:model="form.estado">
                        @foreach(\App\Models\Exercise::ESTADOS as $k => $v)
                            <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="form.estado" />
                </flux:field>
            </div>
            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" size="xs" type="button" wire:click="closeModal">Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled" wire:target="save">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="save" />
                    <span wire:loading.remove wire:target="save">{{ $exerciseId ? 'Actualizar' : 'Crear' }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
