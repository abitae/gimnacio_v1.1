<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Rutinas base</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Plantillas de rutinas para asignar a clientes</p>
            </div>
            @can('ejercicios-rutinas.create')
            <flux:button icon="plus" variant="primary" size="xs" wire:click="openCreateModal" wire:loading.attr="disabled">
                Nueva rutina base
            </flux:button>
            @endcan
        </div>

        <div class="flex flex-wrap gap-2 items-center">
            <flux:input type="search" placeholder="Buscar..." wire:model.live.debounce.300ms="search" class="w-48" size="xs" />
            <select wire:model.live="estadoFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="">Estado</option>
                <option value="borrador">Borrador</option>
                <option value="activa">Activa</option>
            </select>
            <select wire:model.live="perPage" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
            </select>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nombre</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Objetivo</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Nivel</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Días/sem</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($templates as $t)
                        <tr class="bg-white dark:bg-zinc-800">
                            <td class="px-4 py-2">
                                <a href="{{ route('rutinas-base.show', $t) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $t->nombre }}</a>
                            </td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">{{ $t->objetivo ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $t->nivel ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $t->frecuencia_dias_semana ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $t->estado_label }}</td>
                            <td class="px-4 py-2 text-right space-x-1">
                                @can('ejercicios-rutinas.update')
                                <flux:button href="{{ route('rutinas-base.builder', $t) }}" size="xs" variant="ghost" wire:navigate>Builder</flux:button>
                                <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $t->id }})">Editar</flux:button>
                                @endcan
                                @can('ejercicios-rutinas.create')
                                <flux:button size="xs" variant="ghost" wire:click="cloneTemplate({{ $t->id }})" wire:loading.attr="disabled">Clonar</flux:button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">No hay rutinas base.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $templates->links() }}
    </div>

    <!-- Modal Crear/Editar Rutina base -->
    <flux:modal name="template-form-modal" wire:model="modalState.create" focusable flyout variant="floating" class="md:max-w-2xl max-h-[90vh] overflow-y-auto">
        <form wire:submit="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $templateId ? 'Editar rutina base' : 'Nueva rutina base' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $templateId ? 'Modifica los datos de la plantilla' : 'Define la plantilla de la rutina' }}
                    </p>
                </div>

                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input size="xs" wire:model="form.nombre" placeholder="Ej. Rutina fuerza 4 días" />
                    <flux:error name="form.nombre" />
                </flux:field>

                <div class="grid grid-cols-2 gap-2">
                    <flux:field>
                        <flux:label>Objetivo</flux:label>
                        <flux:input size="xs" wire:model="form.objetivo" placeholder="Fuerza, hipertrofia..." />
                    </flux:field>
                    <flux:field>
                        <flux:label>Nivel</flux:label>
                        <flux:input size="xs" wire:model="form.nivel" placeholder="Principiante, Intermedio" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <flux:field>
                        <flux:label>Duración (semanas)</flux:label>
                        <flux:input size="xs" type="number" wire:model="form.duracion_semanas" placeholder="Opcional" min="1" />
                        <flux:error name="form.duracion_semanas" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Frecuencia (días/semana)</flux:label>
                        <flux:input size="xs" type="number" wire:model="form.frecuencia_dias_semana" placeholder="Ej. 4" min="1" max="7" />
                        <flux:error name="form.frecuencia_dias_semana" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Descripción</flux:label>
                    <flux:textarea size="xs" wire:model="form.descripcion" rows="2" />
                </flux:field>

                <flux:field>
                    <flux:label>Tags (separados por coma)</flux:label>
                    <flux:input size="xs" wire:model="tagsInput" placeholder="Ej. fuerza, full body" />
                </flux:field>

                <flux:field>
                    <flux:label>Estado</flux:label>
                    <flux:select size="xs" wire:model="form.estado">
                        @foreach(\App\Models\RoutineTemplate::ESTADOS as $k => $v)
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
                    <span wire:loading.remove wire:target="save">{{ $templateId ? 'Actualizar' : 'Crear' }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>
