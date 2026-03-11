<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Leads</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Listado con filtros</p>
        </div>
        <a href="{{ route('crm.pipeline') }}" wire:navigate class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100">
            Ver Pipeline
        </a>
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar..." class="min-w-[180px]" />
        <select wire:model.live="stage_id" class="rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-1.5 text-sm">
            <option value="">Todas las etapas</option>
            @foreach($this->stages as $s)
            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
            @endforeach
        </select>
        <select wire:model.live="assignedFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-1.5 text-sm">
            <option value="">Todos</option>
            <option value="me">Mis leads</option>
            @foreach($this->users as $u)
            <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        @if($this->canales->isNotEmpty())
        <select wire:model.live="canalFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-1.5 text-sm">
            <option value="">Todos los canales</option>
            @foreach($this->canales as $c)
            <option value="{{ $c }}">{{ $c }}</option>
            @endforeach
        </select>
        @endif
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Código</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Contacto</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Etapa</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Asignado</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Canal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($leads as $lead)
                <tr>
                    <td class="px-4 py-2 text-xs font-mono text-zinc-600 dark:text-zinc-400">{{ $lead->codigo ?? '—' }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('crm.leads.show', $lead->id) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $lead->nombre_completo }}</a>
                        <p class="text-xs text-zinc-500">{{ $lead->telefono }}</p>
                    </td>
                    <td class="px-4 py-2">{{ $lead->stage->nombre ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $lead->assignedTo->name ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $lead->canal_origen ?? '—' }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('crm.leads.show', $lead->id) }}" wire:navigate class="text-xs text-zinc-600 hover:text-zinc-900">Ver</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">No hay leads con estos filtros</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">{{ $leads->links() }}</div>
    </div>
</div>
