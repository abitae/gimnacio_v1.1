<div class="space-y-4">
    <x-crm.subnav />

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Pipeline CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Leads por etapa · Arrastra una tarjeta a otra columna, o usa el menú ⋮ de cada lead</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('crm.editar')
            <flux:button icon="cog-6-tooth" variant="ghost" size="sm" wire:click="openManageStages">
                Gestionar etapas
            </flux:button>
            @endcan
            @can('crm.crear')
            <flux:button icon="plus" variant="primary" size="sm" wire:click="openNewLead"
                wire:loading.attr="disabled" wire:target="openNewLead">
                <span wire:loading.remove wire:target="openNewLead">Nuevo Lead</span>
                <span wire:loading wire:target="openNewLead">Abriendo...</span>
            </flux:button>
            @endcan
        </div>
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2 w-full mb-2">
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                <p class="text-[10px] uppercase text-zinc-500">Leads semana</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['leads_nuevos_semana'] }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                <p class="text-[10px] uppercase text-zinc-500">Convertidos</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['leads_convertidos_semana'] }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                <p class="text-[10px] uppercase text-zinc-500">Tasa conv.</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['tasa_conversion_semana'] }}%</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                <p class="text-[10px] uppercase text-zinc-500">Deals abiertos</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['deals_abiertos'] }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                <p class="text-[10px] uppercase text-zinc-500">Valor pipeline</p>
                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($summary['deals_valor_abierto'], 0) }}</p>
            </div>
            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 px-3 py-2">
                <p class="text-[10px] uppercase text-zinc-500">Tareas vencidas</p>
                <p class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ $summary['tareas_vencidas'] }}</p>
            </div>
        </div>
        <div class="min-w-[200px] flex-1">
            <flux:input icon="magnifying-glass" type="search" placeholder="Buscar por nombre, teléfono, email..."
                wire:model.live.debounce.300ms="search" class="w-full" />
        </div>
        <flux:select wire:model.live="assignedFilter" size="sm" class="w-auto" aria-label="Filtrar por asesor">
            <option value="">Todos los asesores</option>
            <option value="me">Mis leads</option>
            @foreach($this->users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </flux:select>
        @if($this->canales->isNotEmpty())
        <flux:select wire:model.live="canalFilter" size="sm" class="w-auto" aria-label="Filtrar por canal">
            <option value="">Todos los canales</option>
            @foreach($this->canales as $c)
            <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
        </flux:select>
        @endif
        <div class="flex items-center gap-1 ml-auto">
            <flux:button size="xs" variant="ghost" wire:click="collapseEmptyStages" title="Minimizar etapas sin leads">
                Colapsar vacías
            </flux:button>
            @if(count($collapsedStageIds) > 0)
            <flux:button size="xs" variant="ghost" wire:click="expandAllStages" title="Mostrar todas las etapas">
                Expandir todas
            </flux:button>
            @endif
        </div>
    </div>

    @php $canDragLeads = auth()->user()?->can('crm.editar') ?? false; @endphp
    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max" x-data="{ draggingId: null, overStageId: null }">
            @foreach($stages as $stage)
            @php
                $totalInStage = $stage->leads_count ?? 0;
                $showing = count($stage->leads ?? []);
                $hasMore = $totalInStage > $showing;
                $collapsed = $this->isStageCollapsed((int) $stage->id);
            @endphp
            <div wire:key="stage-col-{{ $stage->id }}"
                class="flex-shrink-0 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/50 overflow-hidden flex flex-col transition-[width] duration-200 {{ $collapsed ? 'w-12' : 'w-80' }}">
                @if($collapsed)
                <button type="button"
                    wire:click="toggleStageCollapse({{ $stage->id }})"
                    class="flex h-full min-h-[220px] flex-col items-center gap-2 px-1 py-3 text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800/80"
                    title="Expandir {{ $stage->nombre }}"
                    aria-expanded="false"
                    aria-label="Expandir etapa {{ $stage->nombre }}">
                    <flux:icon name="chevron-right" class="h-4 w-4 shrink-0" />
                    <span class="text-[11px] font-medium leading-none [writing-mode:vertical-rl] rotate-180 truncate max-h-40">{{ $stage->nombre }}</span>
                    <span class="mt-auto text-[10px] bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-full min-w-5 px-1.5 py-0.5 text-center">{{ $totalInStage }}</span>
                </button>
                @else
                <div class="px-3 py-2.5 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between shrink-0 gap-2">
                    <div class="flex items-center gap-1 min-w-0">
                        <button type="button"
                            wire:click="toggleStageCollapse({{ $stage->id }})"
                            class="shrink-0 rounded-md p-0.5 text-zinc-500 hover:bg-zinc-200 hover:text-zinc-800 dark:hover:bg-zinc-700 dark:hover:text-zinc-100"
                            title="Minimizar {{ $stage->nombre }}"
                            aria-expanded="true"
                            aria-label="Minimizar etapa {{ $stage->nombre }}">
                            <flux:icon name="chevron-down" class="h-4 w-4" />
                        </button>
                        <button type="button"
                            wire:click="toggleStageCollapse({{ $stage->id }})"
                            class="min-w-0 truncate text-left font-medium text-sm text-zinc-800 dark:text-zinc-200 hover:underline decoration-zinc-300 underline-offset-2"
                            title="Minimizar {{ $stage->nombre }}">
                            {{ $stage->nombre }}
                        </button>
                        @can('crm.editar')
                        <flux:button size="xs" variant="ghost" icon="pencil" class="shrink-0 opacity-60 hover:opacity-100"
                            wire:click="openEditStage({{ $stage->id }})" title="Editar etapa" />
                        @endcan
                    </div>
                    <span class="text-xs bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-full px-2 py-0.5 shrink-0">{{ $totalInStage }}</span>
                </div>
                @if($hasMore)
                <div class="px-3 py-1 border-b border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 dark:text-zinc-400 shrink-0">
                    Mostrando {{ $showing }} de {{ $totalInStage }}
                    <a href="{{ route('crm.leads.index', ['stage_id' => $stage->id]) }}" wire:navigate class="text-zinc-700 dark:text-zinc-300 hover:underline ml-1">Ver todos</a>
                </div>
                @endif
                <div class="p-2 min-h-[140px] max-h-[calc(100vh-300px)] overflow-y-auto space-y-2 flex-1 rounded-b-xl transition-colors"
                    @if($canDragLeads)
                    x-on:dragover.prevent="overStageId = {{ $stage->id }}"
                    x-on:dragleave="overStageId = (overStageId === {{ $stage->id }} ? null : overStageId)"
                    x-on:drop.prevent="if (draggingId) { $wire.moveToStage(draggingId, {{ $stage->id }}); } draggingId = null; overStageId = null;"
                    :class="overStageId === {{ $stage->id }} ? 'ring-2 ring-inset ring-accent bg-accent/5' : ''"
                    @endif>
                    @forelse($stage->leads as $lead)
                    @php $isMoving = $movingLeadId == $lead->id && $movingToStageId; @endphp
                    <div wire:key="lead-{{ $lead->id }}"
                        @if($canDragLeads)
                        draggable="true"
                        x-on:dragstart="draggingId = {{ $lead->id }}; $event.dataTransfer.effectAllowed = 'move';"
                        x-on:dragend="draggingId = null; overStageId = null;"
                        @endif
                        class="rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-sm hover:shadow transition cursor-pointer group relative {{ $canDragLeads ? 'active:cursor-grabbing' : '' }} {{ $isMoving ? 'opacity-70 pointer-events-none' : '' }}">
                        @if($isMoving)
                        <div class="absolute inset-0 flex items-center justify-center bg-white/80 dark:bg-zinc-800/80 rounded-lg z-10">
                            <flux:icon name="arrow-path" class="w-6 h-6 animate-spin text-zinc-500" />
                        </div>
                        @endif
                        <div class="p-3">
                            <div class="flex justify-between items-start gap-1">
                                <div class="min-w-0 flex-1" wire:click="openLeadDetail({{ $lead->id }})">
                                    @if($lead->codigo)
                                    <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400">{{ $lead->codigo }}</p>
                                    @endif
                                    <p class="font-medium text-sm text-zinc-900 dark:text-zinc-100 truncate">{{ $lead->nombre_completo }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $lead->telefono }}</p>
                                    @if($lead->email)
                                    <p class="text-xs text-zinc-500 truncate">{{ $lead->email }}</p>
                                    @endif
                                    @if($lead->assignedTo)
                                    <p class="text-xs text-zinc-400 mt-1">{{ $lead->assignedTo->name }}</p>
                                    @endif
                                </div>
                                <flux:dropdown align="right" class="opacity-0 group-hover:opacity-100 shrink-0">
                                    <flux:button size="xs" variant="ghost" icon="ellipsis-vertical" />
                                    <flux:menu>
                                        <flux:menu.item wire:click="openEditLead({{ $lead->id }})" icon="pencil">Editar</flux:menu.item>
                                        @if(!$lead->isConvertido())
                                        <flux:menu.item wire:click="openConvertModal({{ $lead->id }})" icon="user-plus">Convertir a cliente</flux:menu.item>
                                        @endif
                                        <flux:menu.separator />
                                        @foreach($stages as $s)
                                        @if($s->id !== $stage->id)
                                        <flux:menu.item wire:click="moveToStage({{ $lead->id }}, {{ $s->id }})" wire:loading.attr="disabled">{{ $s->nombre }}</flux:menu.item>
                                        @endif
                                        @endforeach
                                    </flux:menu>
                                </flux:dropdown>
                            </div>
                            {{-- Acción rápida: WhatsApp (mismo número que teléfono) --}}
                            @if($lead->whatsapp_url)
                            <div class="mt-2">
                                <a href="{{ $lead->whatsapp_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-0.5 text-xs text-green-600 dark:text-green-400 hover:underline"
                                    onclick="event.stopPropagation();">
                                    <flux:icon name="chat-bubble-left-right" class="w-3.5 h-3.5" /> WhatsApp
                                </a>
                            </div>
                            @endif
                            @if($lead->tags->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-2">
                                @foreach($lead->tags as $tag)
                                <x-crm.tag-pill :color="$tag->color" :label="$tag->nombre" />
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-8 px-2 text-center min-h-[120px] rounded-lg border border-dashed border-zinc-200 dark:border-zinc-600">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Sin leads en esta etapa</p>
                        @can('crm.crear')
                        <flux:button size="xs" variant="ghost" wire:click="openNewLead">Añadir lead</flux:button>
                        @endcan
                    </div>
                    @endforelse
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <flux:modal name="lead-form" wire:model="modalLead" focusable flyout variant="floating" class="md:w-lg">
        @if($modalLead)
        <livewire:crm.lead-form-live :lead-id="$editingLeadId" :key="'lead-form-'.($editingLeadId ?? 'new')" />
        @endif
    </flux:modal>

    <flux:modal name="convert-lead" wire:model="modalConvert" focusable flyout variant="floating" class="md:w-lg">
        @if($modalConvert && $selectedLeadId)
        <livewire:crm.convert-lead-live :lead-id="$selectedLeadId" :key="'convert-'.$selectedLeadId" />
        @endif
    </flux:modal>

    <x-ui.confirm-modal wire-model="confirmDeleteStage" title="Eliminar etapa"
        message="Esta acción no se puede deshacer. Solo puedes eliminar etapas sin leads."
        confirm-action="deleteStage" cancel-action="cancelDeleteStage" />

    <flux:modal name="manage-stages" wire:model="modalStages" focusable flyout variant="floating" class="md:w-lg">
        @if($modalStages)
        <div>
            @if($stageFormOpen)
            <flux:heading size="lg">{{ $editingStageId ? 'Editar etapa' : 'Nueva etapa' }}</flux:heading>
            <form wire:submit="saveStage" class="mt-4 space-y-3">
                <flux:field>
                    <flux:label>Nombre</flux:label>
                    <flux:input wire:model="stageNombre" required maxlength="80" />
                    <flux:error name="stageNombre" />
                </flux:field>
                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model.live="stageIsDefault" class="rounded border-zinc-300 dark:border-zinc-600" />
                        Etapa por defecto (nuevos leads)
                    </label>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model.live="stageIsWon" class="rounded border-zinc-300 dark:border-zinc-600" />
                        Cerrado ganado
                    </label>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model.live="stageIsLost" class="rounded border-zinc-300 dark:border-zinc-600" />
                        Cerrado perdido
                    </label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="backToStageList">Volver</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </form>
            @else
            <div class="flex items-center justify-between gap-2">
                <flux:heading size="lg">Gestionar etapas</flux:heading>
                @can('crm.crear')
                <flux:button size="sm" variant="primary" icon="plus" wire:click="openCreateStage">Nueva</flux:button>
                @endcan
            </div>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Ordena, edita o elimina las columnas del pipeline.</p>
            <ul class="mt-4 divide-y divide-zinc-200 dark:divide-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                @forelse($manageStages as $manageStage)
                <li wire:key="manage-stage-{{ $manageStage->id }}" class="flex items-center justify-between gap-2 p-3 bg-white dark:bg-zinc-900">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="font-medium text-sm text-zinc-900 dark:text-zinc-100">{{ $manageStage->nombre }}</span>
                            @if($manageStage->is_default)
                            <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200">Default</span>
                            @endif
                            @if($manageStage->is_won)
                            <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">Ganado</span>
                            @endif
                            @if($manageStage->is_lost)
                            <span class="text-[10px] uppercase tracking-wide px-1.5 py-0.5 rounded bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200">Perdido</span>
                            @endif
                        </div>
                        <p class="text-xs text-zinc-500 mt-0.5">{{ $manageStage->leads_count }} lead(s)</p>
                    </div>
                    <div class="flex items-center gap-0.5 shrink-0">
                        @can('crm.editar')
                        <flux:button size="xs" variant="ghost" icon="chevron-up" wire:click="moveStageUp({{ $manageStage->id }})" title="Subir" />
                        <flux:button size="xs" variant="ghost" icon="chevron-down" wire:click="moveStageDown({{ $manageStage->id }})" title="Bajar" />
                        <flux:button size="xs" variant="ghost" wire:click="openEditStage({{ $manageStage->id }})">Editar</flux:button>
                        @endcan
                        @can('crm.eliminar')
                        <flux:button size="xs" variant="ghost" wire:click="requestDeleteStage({{ $manageStage->id }})"
                            :disabled="$manageStage->leads_count > 0">Eliminar</flux:button>
                        @endcan
                    </div>
                </li>
                @empty
                <li class="p-6 text-center text-sm text-zinc-500">No hay etapas configuradas.</li>
                @endforelse
            </ul>
            <div class="flex justify-end pt-4">
                <flux:button type="button" variant="ghost" wire:click="closeManageStages">Cerrar</flux:button>
            </div>
            @endif
        </div>
        @endif
    </flux:modal>
</div>

@script
<script>
    Livewire.on('lead-saved', () => { $wire.leadSaved(); });
    Livewire.on('convert-done', (payload) => {
        const clienteId = payload?.clienteId ?? payload?.[0]?.clienteId ?? null;
        $wire.convertDone(clienteId);
    });
    Livewire.on('close-lead-modal', () => { $wire.closeLeadModal(); });
    Livewire.on('close-convert-modal', () => { $wire.closeConvertModal(); });
</script>
@endscript
