<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('crm.campaigns') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center gap-1">
            <flux:icon name="arrow-left" class="w-4 h-4" /> Volver a campañas
        </a>
        @can('crm.create')
        <flux:button size="sm" variant="primary" wire:click="openGenerarTargets">Generar más targets</flux:button>
        @endcan
    </div>

    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
        <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $campaign->nombre }}</h1>
        <p class="text-sm text-zinc-500">{{ \App\Models\Crm\Campaign::TIPOS[$campaign->tipo] ?? $campaign->tipo }} · {{ \App\Models\Crm\Campaign::ESTADOS[$campaign->estado] ?? $campaign->estado }}</p>
    </div>

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Contacto</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Asignado a</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Estado</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($targets as $t)
                <tr>
                    <td class="px-4 py-2">
                        @if($t->cliente)
                        {{ $t->cliente->nombres }} {{ $t->cliente->apellidos }}
                        @elseif($t->lead)
                        {{ $t->lead->nombre_completo }} (lead)
                        @else
                        —
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <select class="rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-2 py-1 text-xs"
                            wire:model.live="targetAssignments.{{ $t->id }}">
                            <option value="">—</option>
                            @foreach($this->users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="px-4 py-2">
                        @can('crm.update')
                        <select class="rounded border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-2 py-1 text-xs"
                            wire:model.live="targetStatuses.{{ $t->id }}">
                            @foreach(\App\Models\Crm\CampaignTarget::ESTADOS as $e)
                            <option value="{{ $e }}">{{ $e }}</option>
                            @endforeach
                        </select>
                        @else
                        {{ $t->estado }}
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="px-4 py-6 text-center text-zinc-500">Sin targets. Genera desde filtros de renovación/reactivación.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">{{ $targets->links() }}</div>
    </div>

    <flux:modal name="generar-targets" wire:model="modalGenerar" focusable flyout variant="floating" class="md:w-lg">
        <div>
            <flux:heading size="lg">Generar targets</flux:heading>
            <p class="text-sm text-zinc-500 mt-1">Añade contactos a esta campaña desde filtros de renovación o reactivación.</p>
            <form wire:submit="generarTargets" class="mt-4 space-y-3">
                <flux:field>
                    <flux:label>Tipo</flux:label>
                    <select wire:model="filtroTipo" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                        <option value="renovacion">Renovación (por vencer)</option>
                        <option value="reactivacion">Reactivación (vencidos)</option>
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Días</flux:label>
                    @if($filtroTipo === 'renovacion')
                    <select wire:model="filtroDias" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                        <option value="7">7 días</option>
                        <option value="3">3 días</option>
                        <option value="1">1 día</option>
                    </select>
                    @else
                    <select wire:model="filtroDias" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                        <option value="15">15 días</option>
                        <option value="30">30 días</option>
                        <option value="60">60 días</option>
                    </select>
                    @endif
                </flux:field>
                <flux:field>
                    <flux:label>Asignar a</flux:label>
                    <select wire:model="asignarUsuario" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                        <option value="">Ninguno</option>
                        @foreach($this->users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="$set('modalGenerar', false)">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Generar targets</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
