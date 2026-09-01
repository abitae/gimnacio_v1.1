<div class="space-y-4">
    <x-crm.subnav />

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Mi Cartera CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Seguimiento de venta a clientes registrados</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar cliente..." class="min-w-[180px]" />
        @if($this->canViewAll)
        <flux:select wire:model.live="asesorFilter" size="sm" class="w-auto" aria-label="Filtrar por asesor">
            <option value="">Todos</option>
            <option value="me">Mi cartera</option>
            @foreach($this->vendedores as $v)
            <option value="{{ $v->id }}">{{ $v->name }}</option>
            @endforeach
        </flux:select>
        @endif
        @if($this->tags->isNotEmpty())
        <flux:select wire:model.live="tagFilter" size="sm" class="w-auto" aria-label="Filtrar por etiqueta">
            <option value="">Todas las etiquetas</option>
            @foreach($this->tags as $t)
            <option value="{{ $t->id }}">{{ $t->nombre }}</option>
            @endforeach
        </flux:select>
        @endif
        <flux:checkbox wire:model.live="soloConTareaVencida" label="Solo con tarea vencida" />
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Cliente</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Asesor CRM</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Próxima tarea</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Última actividad</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Oportunidades</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($clientes as $cliente)
                <tr>
                    <td class="px-4 py-2">
                        <a href="{{ route('clientes.perfil', $cliente->id) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">
                            {{ trim($cliente->nombres.' '.$cliente->apellidos) }}
                        </a>
                        <p class="text-xs text-zinc-500">{{ $cliente->telefono }}</p>
                        @if($cliente->crmTags->isNotEmpty())
                        <div class="flex flex-wrap gap-1 mt-1">
                            @foreach($cliente->crmTags as $tag)
                            <x-crm.tag-pill :color="$tag->color" :label="$tag->nombre" />
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $cliente->asesorCrm->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if($cliente->next_task)
                        <span class="{{ $cliente->next_task->fecha_hora_programada->isPast() ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
                            {{ $cliente->next_task->fecha_hora_programada->format('d/m/Y H:i') }}
                        </span>
                        @else
                        <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($cliente->last_activity)
                        {{ $cliente->last_activity->tipo_label }} · {{ $cliente->last_activity->fecha_hora->diffForHumans() }}
                        @else
                        <span class="text-zinc-400">Sin actividad</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($cliente->open_deals->isNotEmpty())
                        <span class="inline-flex items-center rounded-full bg-emerald-50 dark:bg-emerald-900/30 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                            {{ $cliente->open_deals->count() }} abierta(s)
                        </span>
                        @else
                        <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <x-ui.table-actions>
                            @can('crm.crear')
                            <flux:button size="xs" variant="ghost" wire:click="openActivityModal({{ $cliente->id }})">Actividad</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="openTaskModal({{ $cliente->id }})">Tarea</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="openDealModal({{ $cliente->id }})">Oportunidad</flux:button>
                            @endcan
                            <flux:button size="xs" variant="ghost" wire:click="openTagsModal({{ $cliente->id }})">Etiquetas</flux:button>
                            @can('crm.reasignar')
                            <flux:button size="xs" variant="ghost" wire:click="openReasignarModal({{ $cliente->id }})">Reasignar</flux:button>
                            @endcan
                        </x-ui.table-actions>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-empty-state message="No hay clientes en esta cartera con estos filtros" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">{{ $clientes->links() }}</div>
    </div>

    <flux:modal name="mi-cartera-activity" wire:model="modalActivity" focusable flyout variant="floating" class="md:w-lg">
        @if($modalActivity)
        <livewire:crm.activity-form-live :cliente-id="$activeClienteId" :activity-id="$editingActivityId" :key="'cartera-activity-'.$activeClienteId.'-'.($editingActivityId ?? 'new')" />
        @endif
    </flux:modal>

    <flux:modal name="mi-cartera-task" wire:model="modalTask" focusable flyout variant="floating" class="md:w-lg">
        @if($modalTask)
        <livewire:crm.task-form-live :cliente-id="$activeClienteId" :task-id="$editingTaskId" :key="'cartera-task-'.$activeClienteId.'-'.($editingTaskId ?? 'new')" />
        @endif
    </flux:modal>

    <flux:modal name="mi-cartera-deal" wire:model="modalDeal" focusable flyout variant="floating" class="md:w-lg">
        @if($modalDeal)
        <livewire:crm.deal-form-live :cliente-id="$activeClienteId" :deal-id="$editingDealId" :key="'cartera-deal-'.$activeClienteId.'-'.($editingDealId ?? 'new')" />
        @endif
    </flux:modal>

    <flux:modal name="mi-cartera-tags" wire:model="modalTags" focusable flyout variant="floating" class="md:w-lg">
        @if($modalTags)
        <livewire:crm.tag-picker-live entity-type="cliente" :cliente-id="$activeClienteId" :key="'cartera-tags-'.$activeClienteId" />
        @endif
    </flux:modal>

    <flux:modal name="mi-cartera-reasignar" wire:model="modalReasignar" focusable flyout variant="floating" class="md:w-md">
        @if($modalReasignar)
        <div class="space-y-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Reasignar asesor CRM</h2>
            <flux:select wire:model="reasignarNuevoAsesorId" label="Nuevo asesor">
                <option value="">Sin asignar</option>
                @foreach(\App\Models\User::orderBy('name')->get(['id','name']) as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </flux:select>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeReasignarModal">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="reasignar">Guardar</flux:button>
            </div>
        </div>
        @endif
    </flux:modal>
</div>

@script
<script>
    Livewire.on('activity-saved', () => { $wire.activitySaved(); });
    Livewire.on('task-saved', () => { $wire.taskSaved(); });
    Livewire.on('deal-saved', () => { $wire.dealSaved(); });
    Livewire.on('tags-saved', () => { $wire.tagsSaved(); });
</script>
@endscript
