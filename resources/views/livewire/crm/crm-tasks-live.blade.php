<div class="space-y-4">
    <x-crm.subnav />

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Tareas CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Mi día y seguimientos</p>
        </div>
        <div class="flex gap-2">
            @can('crm.crear')
            <flux:button size="sm" variant="primary" wire:click="openNewTask">Nueva tarea</flux:button>
            @endcan
            <flux:button size="sm" variant="{{ $view === 'my-day' ? 'primary' : 'ghost' }}" wire:click="$set('view', 'my-day')">Mi día</flux:button>
            <flux:button size="sm" variant="{{ $view === 'list' ? 'primary' : 'ghost' }}" wire:click="$set('view', 'list')">Listado</flux:button>
        </div>
    </div>

    @if($view === 'my-day' && $myDay)
    @php
        $dayGroups = [
            'overdue' => [
                'label' => 'Vencidas',
                'icon' => 'exclamation-triangle',
                'containerClass' => 'rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/30 p-4',
                'headingClass' => 'font-medium text-amber-800 dark:text-amber-200 flex items-center gap-2',
                'dateFormat' => 'd/m H:i',
                'actionable' => true,
            ],
            'today' => [
                'label' => 'Hoy',
                'icon' => 'calendar',
                'containerClass' => 'rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/30 p-4',
                'headingClass' => 'font-medium text-blue-800 dark:text-blue-200 flex items-center gap-2',
                'dateFormat' => 'H:i',
                'actionable' => true,
            ],
            'next_7_days' => [
                'label' => 'Próximos 7 días',
                'icon' => 'calendar-days',
                'containerClass' => 'rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/50 p-4',
                'headingClass' => 'font-medium text-zinc-800 dark:text-zinc-200 flex items-center gap-2',
                'dateFormat' => 'd/m H:i',
                'actionable' => false,
            ],
        ];
    @endphp
    <div class="grid gap-4 md:grid-cols-3">
        @foreach($dayGroups as $key => $group)
        <div class="{{ $group['containerClass'] }}">
            <h2 class="{{ $group['headingClass'] }}">
                <flux:icon name="{{ $group['icon'] }}" class="w-5 h-5" /> {{ $group['label'] }}
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $myDay[$key]->count() }} tareas</p>
            <ul class="mt-3 space-y-2 max-h-48 overflow-y-auto">
                @forelse($myDay[$key] as $t)
                <li class="{{ $group['actionable'] ? 'flex items-center justify-between gap-2' : '' }} text-sm p-2 rounded bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600">
                    <span class="truncate">{{ $t->tipo_label }} · {{ $t->fecha_hora_programada->format($group['dateFormat']) }}</span>
                    @if($group['actionable'])
                    <flux:button size="xs" wire:click="completeTask({{ $t->id }})" wire:loading.attr="disabled" wire:target="completeTask({{ $t->id }})">
                        <span wire:loading.remove wire:target="completeTask({{ $t->id }})">Hecha</span>
                        <span wire:loading wire:target="completeTask({{ $t->id }})">...</span>
                    </flux:button>
                    @endif
                </li>
                @empty
                <li class="text-xs text-zinc-500">Ninguna</li>
                @endforelse
            </ul>
        </div>
        @endforeach
    </div>
    @endif

    @if($view === 'list' && isset($tasks))
    <div class="flex gap-2 items-center">
        <flux:select wire:model.live="statusFilter" size="sm" class="w-auto" aria-label="Filtrar por estado">
            <option value="">Todos</option>
            <option value="pending">Pendiente</option>
            <option value="done">Hecha</option>
            <option value="overdue">Vencida</option>
        </flux:select>
    </div>
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Tipo</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Fecha</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Prioridad</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Estado</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Lead/Cliente</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($tasks as $t)
                <tr>
                    <td class="px-4 py-2">{{ $t->tipo_label }}</td>
                    <td class="px-4 py-2">{{ $t->fecha_hora_programada->format('d/m/Y H:i') }}</td>
                    <td class="px-4 py-2">{{ $t->prioridad_label }}</td>
                    <td class="px-4 py-2"><x-crm.status-badge :status="$t->estado" type="task" :label="$t->estado_label" /></td>
                    <td class="px-4 py-2">
                        @if($t->lead) {{ $t->lead->nombre_completo }} @elseif($t->cliente) {{ $t->cliente->nombres }} {{ $t->cliente->apellidos }} @else — @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($t->estado === 'pending' || $t->estado === 'overdue')
                        <flux:button size="xs" wire:click="completeTask({{ $t->id }})" wire:loading.attr="disabled" wire:target="completeTask">
                            <span wire:loading.remove wire:target="completeTask">Hecha</span>
                            <span wire:loading wire:target="completeTask">...</span>
                        </flux:button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-empty-state message="Sin tareas" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">
            {{ $tasks->links() }}
        </div>
    </div>
    @endif

    <flux:modal name="new-task" wire:model="modalTask" focusable flyout variant="floating" class="md:w-lg">
        @if($modalTask)
        <flux:heading size="lg">Nueva tarea CRM</flux:heading>
        <div class="mt-3 space-y-3">
            <flux:field>
                <flux:label>Lead</flux:label>
                <flux:select wire:model.live="taskLeadId">
                    <option value="">Sin lead</option>
                    @foreach($this->assignableLeads as $lead)
                    <option value="{{ $lead->id }}">{{ $lead->nombre_completo }} · {{ $lead->telefono }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
            @if($taskLeadId)
            <livewire:crm.task-form-live :lead-id="(int) $taskLeadId" :key="'task-new-lead-'.$taskLeadId" />
            @else
            <p class="text-sm text-zinc-500">Selecciona un lead para programar la tarea.</p>
            @endif
        </div>
        @endif
    </flux:modal>
</div>

@script
<script>
    Livewire.on('task-saved', () => { $wire.taskSaved(); });
</script>
@endscript
