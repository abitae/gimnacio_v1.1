<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Sesiones de entrenamiento</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $cliente->nombres }} {{ $cliente->apellidos }} · {{ $clientRoutine->routineTemplate?->nombre ?? 'Rutina' }}</p>
        </div>
        @can('ejercicios-rutinas.create')
        <flux:button href="{{ route('clientes.rutinas.sesiones.create', [$cliente, $clientRoutine]) }}" variant="primary" size="xs" wire:navigate>Registrar sesión</flux:button>
        @endcan
        <flux:button href="{{ route('clientes.rutinas.show', [$cliente, $clientRoutine]) }}" variant="ghost" size="xs" wire:navigate>Volver a rutina</flux:button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha y hora</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($sessions as $session)
                    <tr class="bg-white dark:bg-zinc-800">
                        <td class="px-4 py-2">{{ $session->fecha_hora->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $session->estado_label }}</td>
                        <td class="px-4 py-2 text-right">
                            <flux:button href="{{ route('clientes.sesiones.show', [$cliente, $session]) }}" size="xs" variant="ghost" wire:navigate>Ver</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">No hay sesiones registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $sessions->links() }}
</div>
