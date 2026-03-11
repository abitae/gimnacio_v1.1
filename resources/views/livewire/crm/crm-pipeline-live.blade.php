<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Pipeline CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Leads por etapa · Arrastra o mueve entre columnas</p>
        </div>
        @can('crm.create')
        <flux:button icon="plus" variant="primary" size="sm" wire:click="openNewLead"
            wire:loading.attr="disabled" wire:target="openNewLead">
            <span wire:loading.remove wire:target="openNewLead">Nuevo Lead</span>
            <span wire:loading wire:target="openNewLead">Abriendo...</span>
        </flux:button>
        @endcan
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <div class="min-w-[200px] flex-1">
            <flux:input icon="magnifying-glass" type="search" placeholder="Buscar por nombre, teléfono, email..."
                wire:model.live.debounce.300ms="search" class="w-full" />
        </div>
        <select wire:model.live="assignedFilter"
            class="rounded-lg border border-zinc-300 bg-white dark:bg-zinc-800 dark:border-zinc-600 px-3 py-1.5 text-sm">
            <option value="">Todos los asesores</option>
            <option value="me">Mis leads</option>
            @foreach($this->users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        @if($this->canales->isNotEmpty())
        <select wire:model.live="canalFilter"
            class="rounded-lg border border-zinc-300 bg-white dark:bg-zinc-800 dark:border-zinc-600 px-3 py-1.5 text-sm">
            <option value="">Todos los canales</option>
            @foreach($this->canales as $c)
            <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
        </select>
        @endif
    </div>

    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4 min-w-max">
            @foreach($stages as $stage)
            @php
                $totalInStage = $stage->leads_count ?? 0;
                $showing = count($stage->leads ?? []);
                $hasMore = $totalInStage > $showing;
            @endphp
            <div class="flex-shrink-0 w-80 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/50 overflow-hidden flex flex-col">
                <div class="px-3 py-2.5 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between shrink-0">
                    <span class="font-medium text-sm text-zinc-800 dark:text-zinc-200">{{ $stage->nombre }}</span>
                    <span class="text-xs bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded-full px-2 py-0.5">{{ $totalInStage }}</span>
                </div>
                @if($hasMore)
                <div class="px-3 py-1 border-b border-zinc-100 dark:border-zinc-800 text-xs text-zinc-500 dark:text-zinc-400 shrink-0">
                    Mostrando {{ $showing }} de {{ $totalInStage }}
                    <a href="{{ route('crm.leads.index', ['stage_id' => $stage->id]) }}" wire:navigate class="text-zinc-700 dark:text-zinc-300 hover:underline ml-1">Ver todos</a>
                </div>
                @endif
                <div class="p-2 min-h-[140px] max-h-[calc(100vh-300px)] overflow-y-auto space-y-2 flex-1">
                    @forelse($stage->leads as $lead)
                    @php $isMoving = $movingLeadId == $lead->id && $movingToStageId; @endphp
                    <div wire:key="lead-{{ $lead->id }}"
                        class="rounded-lg border border-zinc-200 dark:border-zinc-600 bg-white dark:bg-zinc-800 shadow-sm hover:shadow transition cursor-pointer group relative {{ $isMoving ? 'opacity-70 pointer-events-none' : '' }}">
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
                                <span class="text-xs px-1.5 py-0.5 rounded" style="background: {{ $tag->color ?? '#e4e4e7' }}20; color: {{ $tag->color ?? '#71717a' }};">{{ $tag->nombre }}</span>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="flex flex-col items-center justify-center py-8 px-2 text-center min-h-[120px] rounded-lg border border-dashed border-zinc-200 dark:border-zinc-600">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Sin leads en esta etapa</p>
                        @can('crm.create')
                        <flux:button size="xs" variant="ghost" wire:click="openNewLead">Añadir lead</flux:button>
                        @endcan
                    </div>
                    @endforelse
                </div>
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
</div>

@script
<script>
    Livewire.on('lead-saved', () => { $wire.leadSaved(); });
    Livewire.on('convert-done', () => { $wire.convertDone(); });
    Livewire.on('close-lead-modal', () => { $wire.closeLeadModal(); });
    Livewire.on('close-convert-modal', () => { $wire.closeConvertModal(); });
</script>
@endscript
