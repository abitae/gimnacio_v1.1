<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Oportunidades (Deals)</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Todas las oportunidades CRM</p>
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <div class="min-w-[200px] flex-1">
            <flux:input icon="magnifying-glass" type="search" placeholder="Buscar por nombre, teléfono, email..."
                wire:model.live.debounce.300ms="search" class="w-full" />
        </div>
        <select wire:model.live="estadoFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-1.5 text-sm">
            <option value="">Todos los estados</option>
            <option value="open">Abiertas</option>
            <option value="won">Ganadas</option>
            <option value="lost">Perdidas</option>
        </select>
        <select wire:model.live="assignedFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-1.5 text-sm">
            <option value="me">Mis oportunidades</option>
            <option value="">Todos</option>
        </select>
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Lead / Cliente</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Membresía</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Precio</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Estado</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Asignado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($deals as $d)
                <tr>
                    <td class="px-4 py-2">
                        @if($d->lead)
                        <a href="{{ route('crm.leads.show', $d->lead_id) }}" wire:navigate class="text-zinc-900 dark:text-zinc-100 hover:underline">{{ $d->lead->nombre_completo }}</a>
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $d->membresia?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2 text-right">S/ {{ number_format($d->precio_objetivo, 2) }}</td>
                    <td class="px-4 py-2">{{ $d->estado }}</td>
                    <td class="px-4 py-2">{{ $d->assignedTo?->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @if($d->lead)
                        <a href="{{ route('crm.leads.show', $d->lead_id) }}" wire:navigate class="text-xs text-zinc-600 hover:text-zinc-900 dark:hover:text-zinc-300">Ver lead</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">No hay oportunidades con estos filtros.</p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500">Prueba a cambiar filtros o <a href="{{ route('crm.pipeline') }}" wire:navigate class="text-zinc-700 dark:text-zinc-300 hover:underline">ver el Pipeline</a>.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">{{ $deals->links() }}</div>
    </div>
</div>
