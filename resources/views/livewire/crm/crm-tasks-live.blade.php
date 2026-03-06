<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Tareas CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Mi día y seguimientos</p>
        </div>
        <div class="flex gap-2">
            <flux:button size="sm" variant="{{ $view === 'my-day' ? 'primary' : 'ghost' }}" wire:click="$set('view', 'my-day')">Mi día</flux:button>
            <flux:button size="sm" variant="{{ $view === 'list' ? 'primary' : 'ghost' }}" wire:click="$set('view', 'list')">Listado</flux:button>
        </div>
    </div>

    @if($view === 'my-day' && $myDay)
    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/30 p-4">
            <h2 class="font-medium text-amber-800 dark:text-amber-200 flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="w-5 h-5" /> Vencidas
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $myDay['overdue']->count() }} tareas</p>
            <ul class="mt-3 space-y-2 max-h-48 overflow-y-auto">
                @forelse($myDay['overdue'] as $t)
                <li class="flex items-center justify-between gap-2 text-sm p-2 rounded bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600">
                    <span class="truncate">{{ $t->tipo_label }} · {{ $t->fecha_hora_programada->format('d/m H:i') }}</span>
                    <flux:button size="xs" wire:click="completeTask({{ $t->id }})" wire:loading.attr="disabled" wire:target="completeTask">
                        <span wire:loading.remove wire:target="completeTask">Hecha</span>
                        <span wire:loading wire:target="completeTask">...</span>
                    </flux:button>
                </li>
                @empty
                <li class="text-xs text-zinc-500">Ninguna</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50/50 dark:bg-blue-950/30 p-4">
            <h2 class="font-medium text-blue-800 dark:text-blue-200 flex items-center gap-2">
                <flux:icon name="calendar" class="w-5 h-5" /> Hoy
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $myDay['today']->count() }} tareas</p>
            <ul class="mt-3 space-y-2 max-h-48 overflow-y-auto">
                @forelse($myDay['today'] as $t)
                <li class="flex items-center justify-between gap-2 text-sm p-2 rounded bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600">
                    <span class="truncate">{{ $t->tipo_label }} · {{ $t->fecha_hora_programada->format('H:i') }}</span>
                    <flux:button size="xs" wire:click="completeTask({{ $t->id }})" wire:loading.attr="disabled" wire:target="completeTask">
                        <span wire:loading.remove wire:target="completeTask">Hecha</span>
                        <span wire:loading wire:target="completeTask">...</span>
                    </flux:button>
                </li>
                @empty
                <li class="text-xs text-zinc-500">Ninguna</li>
                @endforelse
            </ul>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-zinc-50/50 dark:bg-zinc-900/50 p-4">
            <h2 class="font-medium text-zinc-800 dark:text-zinc-200 flex items-center gap-2">
                <flux:icon name="calendar-days" class="w-5 h-5" /> Próximos 7 días
            </h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">{{ $myDay['next_7_days']->count() }} tareas</p>
            <ul class="mt-3 space-y-2 max-h-48 overflow-y-auto">
                @forelse($myDay['next_7_days'] as $t)
                <li class="text-sm p-2 rounded bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600">
                    {{ $t->tipo_label }} · {{ $t->fecha_hora_programada->format('d/m H:i') }}
                </li>
                @empty
                <li class="text-xs text-zinc-500">Ninguna</li>
                @endforelse
            </ul>
        </div>
    </div>
    @endif

    @if($view === 'list' && isset($tasks))
    <div class="flex gap-2 items-center">
        <select wire:model.live="statusFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-1.5 text-sm">
            <option value="">Todos</option>
            <option value="pending">Pendiente</option>
            <option value="done">Hecha</option>
            <option value="overdue">Vencida</option>
        </select>
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
                    <td class="px-4 py-2">{{ $t->estado_label }}</td>
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
                <tr><td colspan="6" class="px-4 py-6 text-center text-zinc-500">Sin tareas</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">
            {{ $tasks->links() }}
        </div>
    </div>
    @endif
</div>
