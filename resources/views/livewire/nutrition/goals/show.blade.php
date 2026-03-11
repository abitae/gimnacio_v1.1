<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $goal->cliente ? $goal->cliente->nombres . ' ' . $goal->cliente->apellidos : 'Cliente' }}</h1>
            <p class="text-sm text-zinc-500">{{ \App\Models\Core\NutritionGoal::OBJETIVOS[$goal->objetivo] ?? $goal->objetivo_personalizado ?? $goal->objetivo }} · Trainer: {{ $goal->trainerUser?->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            @can('gestion-nutricional.update')
            <flux:button size="xs" variant="ghost" href="{{ route('gestion-nutricional.objetivos.edit', $goal) }}" wire:navigate>Editar</flux:button>
            @endcan
            @can('gestion-nutricional.create')
            <flux:button size="xs" href="{{ route('gestion-nutricional.objetivos.seguimiento.create', $goal) }}" wire:navigate>Registrar seguimiento</flux:button>
            @endcan
            <flux:button variant="ghost" size="xs" href="{{ route('gestion-nutricional.objetivos.index') }}" wire:navigate>Volver</flux:button>
        </div>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500">Inicio</span>
            <p class="font-medium">{{ $goal->fecha_inicio->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500">Objetivo fecha</span>
            <p class="font-medium">{{ $goal->fecha_objetivo ? $goal->fecha_objetivo->format('d/m/Y') : '—' }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500">Estado</span>
            <p class="font-medium">{{ ucfirst($goal->estado) }}</p>
        </div>
    </div>
    @if($goal->observaciones)
    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $goal->observaciones }}</p>
    @endif
    <h2 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Seguimiento</h2>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-xs">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                    <th class="px-4 py-2 text-left font-medium">Peso</th>
                    <th class="px-4 py-2 text-left font-medium">Observaciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($progress as $p)
                    <tr>
                        <td class="px-4 py-2">{{ $p->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $p->peso !== null ? $p->peso . ' kg' : '—' }}</td>
                        <td class="px-4 py-2">{{ Str::limit($p->observaciones, 40) ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">Sin registros de seguimiento</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-end">{{ $progress->links() }}</div>
</div>
