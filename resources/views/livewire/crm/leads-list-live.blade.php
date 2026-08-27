<div class="space-y-4">
    <x-crm.subnav />

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
        <flux:select wire:model.live="stage_id" size="sm" class="w-auto" aria-label="Filtrar por etapa">
            <option value="">Todas las etapas</option>
            @foreach($this->stages as $s)
            <option value="{{ $s->id }}">{{ $s->nombre }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="assignedFilter" size="sm" class="w-auto" aria-label="Filtrar por asesor">
            <option value="">Todos</option>
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
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500">Acciones</th>
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
                    <td class="px-4 py-2 text-right">
                        <x-ui.table-actions>
                            <flux:button size="xs" variant="ghost" icon="eye" href="{{ route('crm.leads.show', $lead->id) }}" wire:navigate>Ver</flux:button>
                        </x-ui.table-actions>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6"><x-empty-state message="No hay leads con estos filtros" /></td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">{{ $leads->links() }}</div>
    </div>
</div>
