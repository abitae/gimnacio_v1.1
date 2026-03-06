<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Renovaciones y Reactivación</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Listas por vencer y vencidos para crear campañas</p>
    </div>

    <div class="flex gap-2">
        <flux:button size="sm" variant="{{ $tab === 'renovacion' ? 'primary' : 'ghost' }}" wire:click="$set('tab', 'renovacion')">Por vencer</flux:button>
        <flux:button size="sm" variant="{{ $tab === 'reactivacion' ? 'primary' : 'ghost' }}" wire:click="$set('tab', 'reactivacion')">Vencidos</flux:button>
    </div>

    @if($tab === 'renovacion')
    <div class="flex gap-2 items-center flex-wrap">
        <span class="text-sm text-zinc-500">Membresías que vencen en:</span>
        <flux:button size="xs" variant="{{ $renovacionDays === '7' ? 'primary' : 'ghost' }}" wire:click="$set('renovacionDays', '7')">7 días</flux:button>
        <flux:button size="xs" variant="{{ $renovacionDays === '3' ? 'primary' : 'ghost' }}" wire:click="$set('renovacionDays', '3')">3 días</flux:button>
        <flux:button size="xs" variant="{{ $renovacionDays === '1' ? 'primary' : 'ghost' }}" wire:click="$set('renovacionDays', '1')">1 día</flux:button>
        @can('crm.create')
        @if($this->renovacionList->isEmpty())
        <flux:button size="sm" variant="primary" class="ml-auto" disabled title="No hay contactos en la lista">
            Crear campaña desde esta lista
        </flux:button>
        @else
        <flux:button size="sm" variant="primary" class="ml-auto" wire:click="openCrearCampana">
            Crear campaña desde esta lista
        </flux:button>
        @endif
        @endcan
    </div>
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Cliente</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Membresía</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Vence</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($this->renovacionList as $cm)
                <tr>
                    <td class="px-4 py-2">{{ $cm->cliente ? $cm->cliente->nombres . ' ' . $cm->cliente->apellidos : '—' }}</td>
                    <td class="px-4 py-2">{{ $cm->membresia?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $cm->fecha_fin->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">No hay membresías por vencer en este período</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($tab === 'reactivacion')
    <div class="flex gap-2 items-center flex-wrap">
        <span class="text-sm text-zinc-500">Membresías vencidas hace:</span>
        <flux:button size="xs" variant="{{ $reactivacionDays === '15' ? 'primary' : 'ghost' }}" wire:click="$set('reactivacionDays', '15')">15 días</flux:button>
        <flux:button size="xs" variant="{{ $reactivacionDays === '30' ? 'primary' : 'ghost' }}" wire:click="$set('reactivacionDays', '30')">30 días</flux:button>
        <flux:button size="xs" variant="{{ $reactivacionDays === '60' ? 'primary' : 'ghost' }}" wire:click="$set('reactivacionDays', '60')">60 días</flux:button>
        @can('crm.create')
        @if($this->reactivacionList->isEmpty())
        <flux:button size="sm" variant="primary" class="ml-auto" disabled title="No hay contactos en la lista">
            Crear campaña desde esta lista
        </flux:button>
        @else
        <flux:button size="sm" variant="primary" class="ml-auto" wire:click="openCrearCampana">
            Crear campaña desde esta lista
        </flux:button>
        @endif
        @endcan
    </div>
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Cliente</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Membresía</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Venció</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($this->reactivacionList as $cm)
                <tr>
                    <td class="px-4 py-2">{{ $cm->cliente ? $cm->cliente->nombres . ' ' . $cm->cliente->apellidos : '—' }}</td>
                    <td class="px-4 py-2">{{ $cm->membresia?->nombre ?? '—' }}</td>
                    <td class="px-4 py-2">{{ $cm->fecha_fin->format('d/m/Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">No hay registros para este período</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    <flux:modal name="crear-campana-renewal" wire:model="modalCrearCampana" focusable flyout variant="floating" class="md:w-lg">
        <div>
            <flux:heading size="lg">Crear campaña</flux:heading>
            <p class="text-sm text-zinc-500 mt-1">{{ count($selectedIds) }} contactos en la lista.</p>
            <form wire:submit="crearCampana" class="mt-4 space-y-3">
                <flux:field>
                    <flux:label>Nombre de la campaña <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="campanaNombre" required placeholder="Ej. Renovación marzo 7 días" />
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="closeCrearCampana">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Crear y generar targets</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
