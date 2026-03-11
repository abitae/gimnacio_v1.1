<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cuotas vencidas</h1>
        <flux:button variant="ghost" size="xs" href="{{ route('reportes.index') }}" wire:navigate>Volver a reportes</flux:button>
    </div>
    <div class="flex gap-2 items-center">
        <select wire:model.live="estadoFilter" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-40">
            <option value="">Todas</option>
            <option value="pendiente">Pendiente</option>
            <option value="vencida">Vencida</option>
        </select>
        <span class="text-sm text-zinc-500">Total pendiente: S/ {{ number_format($totalMonto, 2) }}</span>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Matrícula</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cuota</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Monto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($cuotas as $c)
                    @php $mat = $c->plan->clienteMatricula; @endphp
                    <tr>
                        <td class="px-4 py-2">{{ $mat->cliente->nombres }} {{ $mat->cliente->apellidos }}</td>
                        <td class="px-4 py-2">{{ $mat->nombre }}</td>
                        <td class="px-4 py-2">{{ $c->numero_cuota }}</td>
                        <td class="px-4 py-2">{{ $c->fecha_vencimiento->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">S/ {{ number_format($c->monto, 2) }}</td>
                        <td class="px-4 py-2">
                            @can('cliente-matriculas.update')
                            <a href="{{ route('cuotas.pagar', $c) }}" wire:navigate>
                                <flux:button size="xs" variant="ghost">Pagar</flux:button>
                            </a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">No hay cuotas vencidas</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-end">{{ $cuotas->links() }}</div>
</div>
