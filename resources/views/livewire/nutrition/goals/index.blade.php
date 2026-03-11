<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-3">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Objetivos nutricionales</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Por cliente y trainer</p>
        </div>
        @can('gestion-nutricional.create')
        <flux:button icon="plus" size="xs" href="{{ route('gestion-nutricional.objetivos.create', $clienteId ? ['cliente_id' => $clienteId] : []) }}" wire:navigate>Nuevo objetivo</flux:button>
        @endcan
    </div>
    <div class="flex gap-2">
        @if(!$clienteId)
        <select wire:model.live="clienteId" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs w-48">
            <option value="">Todos los clientes</option>
            @foreach($clientes as $c)
                <option value="{{ $c->id }}">{{ $c->nombres }} {{ $c->apellidos }}</option>
            @endforeach
        </select>
        @endif
        <select wire:model.live="trainerFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-xs w-40">
            <option value="">Todos los trainers</option>
            @foreach($trainers as $t)
                <option value="{{ $t->id }}">{{ $t->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Trainer</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Objetivo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Inicio</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($goals as $g)
                    <tr>
                        <td class="px-4 py-2">{{ $g->cliente ? $g->cliente->nombres . ' ' . $g->cliente->apellidos : '-' }}</td>
                        <td class="px-4 py-2">{{ $g->trainerUser?->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ \App\Models\Core\NutritionGoal::OBJETIVOS[$g->objetivo] ?? $g->objetivo_personalizado ?? $g->objetivo }}</td>
                        <td class="px-4 py-2">{{ $g->fecha_inicio->format('d/m/Y') }}</td>
                        <td class="px-4 py-2"><span class="rounded-full px-1.5 py-0.5 text-xs {{ $g->estado === 'activo' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-zinc-100 dark:bg-zinc-700' }}">{{ ucfirst($g->estado) }}</span></td>
                        <td class="px-4 py-2">
                            <flux:button size="xs" variant="ghost" href="{{ route('gestion-nutricional.objetivos.show', $g) }}" wire:navigate>Ver</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">No hay objetivos</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-end">{{ $goals->links() }}</div>
</div>
