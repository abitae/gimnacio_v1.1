<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('crm.pipeline') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center gap-1">
            <flux:icon name="arrow-left" class="w-4 h-4" /> Volver al pipeline
        </a>
        @can('crm.update')
        @if(!$lead->isConvertido())
        <flux:button size="sm" variant="primary" wire:click="openConvertModal"
            wire:loading.attr="disabled" wire:target="openConvertModal">
            <span wire:loading.remove wire:target="openConvertModal">Convertir a cliente</span>
            <span wire:loading wire:target="openConvertModal">Abriendo...</span>
        </flux:button>
        @endif
        @endcan
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
        <div class="flex items-start justify-between gap-2">
            <div>
                @if($lead->codigo)
                <p class="text-xs font-mono text-zinc-500 dark:text-zinc-400 mb-0.5">{{ $lead->codigo }}</p>
                @endif
                <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $lead->nombre_completo }}</h1>
                <p class="text-sm text-zinc-500">{{ $lead->stage->nombre }}</p>
                <div class="mt-3 grid gap-2 sm:grid-cols-2 text-sm">
                    <p><span class="text-zinc-500">Teléfono:</span> {{ $lead->telefono }}</p>
                    @if($lead->email)<p><span class="text-zinc-500">Email:</span> {{ $lead->email }}</p>@endif
                    @if($lead->canal_origen)<p><span class="text-zinc-500">Canal:</span> {{ $lead->canal_origen }}</p>@endif
                    @if($lead->assignedTo)<p><span class="text-zinc-500">Asesor:</span> {{ $lead->assignedTo->name }}</p>@endif
                </div>
                @if($lead->whatsapp_url)
                <a href="{{ $lead->whatsapp_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-2 text-green-600 dark:text-green-400 text-sm">
                    <flux:icon name="chat-bubble-left-right" class="w-4 h-4" /> Abrir WhatsApp
                </a>
                @endif
            </div>
            @can('crm.update')
            <flux:button size="xs" variant="ghost" wire:click="openTagsModal"
                wire:loading.attr="disabled" wire:target="openTagsModal">
                <flux:icon name="tag" class="w-4 h-4" /> Etiquetas
            </flux:button>
            @endcan
        </div>
        @if($lead->tags->isNotEmpty())
        <div class="flex flex-wrap gap-1 mt-2">
            @foreach($lead->tags as $tag)
            <span class="text-xs px-2 py-0.5 rounded-full" style="background: {{ $tag->color ?? '#e4e4e7' }}30; color: {{ $tag->color ?? '#71717a' }};">{{ $tag->nombre }}</span>
            @endforeach
        </div>
        @endif
        @if($lead->notas)
        <div class="mt-3 p-2 rounded bg-zinc-50 dark:bg-zinc-900 text-sm">{{ $lead->notas }}</div>
        @endif
    </div>

    {{-- Oportunidades (Deals) --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-medium text-zinc-800 dark:text-zinc-200">Oportunidades</h2>
            @can('crm.create')
            <flux:button size="xs" variant="primary" wire:click="openDealModal(null)"
                wire:loading.attr="disabled" wire:target="openDealModal">Nueva oportunidad</flux:button>
            @endcan
        </div>
        <ul class="space-y-2 text-sm">
            @forelse($lead->deals as $d)
            <li class="flex items-center justify-between gap-2 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-900">
                <div>
                    <span class="font-medium">{{ $d->membresia?->nombre ?? 'Sin membresía' }}</span>
                    · S/ {{ number_format($d->precio_objetivo, 2) }}
                    · <span class="text-zinc-500">{{ $d->estado }}</span>
                    @if($d->fecha_estimada_cierre)
                    <span class="text-zinc-400">· Cierre: {{ $d->fecha_estimada_cierre->format('d/m/Y') }}</span>
                    @endif
                </div>
                <div class="flex gap-1">
                    @if($d->estado === 'open')
                    @can('crm.update')
                    <flux:button size="xs" variant="ghost" wire:click="openDealModal({{ $d->id }})">Editar</flux:button>
                    @endcan
                    @endif
                    @can('crm.delete')
                    <flux:button size="xs" variant="ghost" wire:click="deleteDeal({{ $d->id }})" wire:confirm="¿Eliminar esta oportunidad?">Eliminar</flux:button>
                    @endcan
                </div>
            </li>
            @empty
            <li class="p-4 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-600 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">Sin oportunidades en este lead.</p>
                @can('crm.create')
                <flux:button size="sm" variant="primary" wire:click="openDealModal(null)">Crear primera oportunidad</flux:button>
                @endcan
            </li>
            @endforelse
        </ul>
    </div>

    {{-- Actividades --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between mb-3">
            <h2 class="font-medium text-zinc-800 dark:text-zinc-200">Actividades</h2>
            @can('crm.create')
            <flux:button size="xs" variant="primary" wire:click="openActivityModal(null)"
                wire:loading.attr="disabled" wire:target="openActivityModal">Nueva actividad</flux:button>
            @endcan
        </div>
        <ul class="space-y-2 text-sm">
            @forelse($lead->activities as $a)
            <li class="flex items-center justify-between gap-2 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-900">
                <div>
                    <span class="font-medium">{{ $a->tipo_label }}</span>
                    · {{ $a->fecha_hora->format('d/m/Y H:i') }}
                    @if($a->resultado)<span class="text-zinc-500">· {{ $a->resultado }}</span>@endif
                    <span class="text-zinc-400">· {{ $a->user->name ?? '-' }}</span>
                </div>
                @can('crm.update')
                <flux:button size="xs" variant="ghost" wire:click="openActivityModal({{ $a->id }})">Editar</flux:button>
                @endcan
                @can('crm.delete')
                <flux:button size="xs" variant="ghost" wire:click="deleteActivity({{ $a->id }})" wire:confirm="¿Eliminar esta actividad?">Eliminar</flux:button>
                @endcan
            </li>
            @empty
            <li class="p-4 rounded-lg border border-dashed border-zinc-200 dark:border-zinc-600 text-center">
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">Sin actividades registradas.</p>
                @can('crm.create')
                <flux:button size="sm" variant="primary" wire:click="openActivityModal(null)">Registrar primera actividad</flux:button>
                @endcan
            </li>
            @endforelse
        </ul>
    </div>

    {{-- Tareas --}}
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
        <div class="flex items-center justify-between mb-2">
            <h2 class="font-medium text-zinc-800 dark:text-zinc-200">Tareas</h2>
            @can('crm.create')
            <flux:button size="xs" variant="primary" wire:click="openTaskModal(null)"
                wire:loading.attr="disabled" wire:target="openTaskModal">Nueva tarea</flux:button>
            @endcan
        </div>
        <ul class="space-y-2 text-sm">
            @forelse($lead->tasks as $t)
            <li class="flex justify-between gap-2 p-2 rounded-lg bg-zinc-50 dark:bg-zinc-900">
                <span>{{ $t->tipo_label }} · {{ $t->fecha_hora_programada->format('d/m H:i') }} ({{ $t->estado_label }})</span>
                @can('crm.update')
                @if($t->estado !== 'done')
                <flux:button size="xs" variant="ghost" wire:click="openTaskModal({{ $t->id }})">Editar</flux:button>
                @endif
                @endcan
            </li>
            @empty
            <li class="text-zinc-500">Sin tareas</li>
            @endforelse
        </ul>
    </div>

    @if($modalConvert)
    <flux:modal name="convert-lead-detail" wire:model="modalConvert" focusable flyout variant="floating" class="md:w-lg">
        <livewire:crm.convert-lead-live :lead-id="$leadId" :key="'convert-detail-'.$leadId" />
    </flux:modal>
    @endif

    <flux:modal name="deal-form-detail" wire:model="modalDeal" focusable flyout variant="floating" class="md:w-lg">
        @if($modalDeal)
        <livewire:crm.deal-form-live :lead-id="$leadId" :deal-id="$editingDealId" :key="'deal-'.$editingDealId" />
        @endif
    </flux:modal>

    <flux:modal name="activity-form-detail" wire:model="modalActivity" focusable flyout variant="floating" class="md:w-lg">
        @if($modalActivity)
        <livewire:crm.activity-form-live :lead-id="$leadId" :activity-id="$editingActivityId" :key="'activity-'.$editingActivityId" />
        @endif
    </flux:modal>

    <flux:modal name="tags-picker-detail" wire:model="modalTags" focusable flyout variant="floating" class="md:w-lg">
        @if($modalTags)
        <livewire:crm.tag-picker-live entity-type="lead" :lead-id="$leadId" :key="'tags-lead-'.$leadId" />
        @endif
    </flux:modal>

    <flux:modal name="task-form-detail" wire:model="modalTask" focusable flyout variant="floating" class="md:w-lg">
        @if($modalTask)
        <livewire:crm.task-form-live :lead-id="$leadId" :task-id="$editingTaskId" :key="'task-'.($editingTaskId ?? 'new')" />
        @endif
    </flux:modal>
</div>

@script
<script>
    Livewire.on('convert-done', () => { $wire.convertDone(); });
    Livewire.on('deal-saved', () => { $wire.dealSaved(); });
    Livewire.on('activity-saved', () => { $wire.activitySaved(); });
    Livewire.on('tags-saved', () => { $wire.tagsSaved(); });
    Livewire.on('task-saved', () => { $wire.taskSaved(); });
    Livewire.on('close-deal-modal', () => { $wire.closeDealModal(); });
    Livewire.on('close-activity-modal', () => { $wire.closeActivityModal(); });
    Livewire.on('close-tags-modal', () => { $wire.closeTagsModal(); });
    Livewire.on('close-task-modal', () => { $wire.closeTaskModal(); });
</script>
@endscript
