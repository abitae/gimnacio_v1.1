<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cronograma de cuotas</h1>
            <p class="text-sm text-zinc-500">{{ $clienteMatricula->cliente->nombres }} {{ $clienteMatricula->cliente->apellidos }} — {{ $clienteMatricula->nombre }}</p>
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" size="xs" href="{{ route('cliente-matriculas.index') }}" wire:navigate>Volver</flux:button>
            @if(!$plan && auth()->user()->can('cliente-matriculas.create'))
            <flux:button size="xs" href="{{ route('cliente-matriculas.cuotas.crear', $clienteMatricula) }}" wire:navigate>Crear plan de cuotas</flux:button>
            @endif
        </div>
    </div>

    @if($plan)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
        <div>
            <span class="text-zinc-500">Monto total</span>
            <p class="font-medium">S/ {{ number_format($plan->monto_total, 2) }}</p>
        </div>
        <div>
            <span class="text-zinc-500">Cuotas</span>
            <p class="font-medium">{{ $plan->numero_cuotas }} ({{ \App\Models\Core\EnrollmentInstallmentPlan::FRECUENCIAS[$plan->frecuencia] ?? $plan->frecuencia }})</p>
        </div>
        <div>
            <span class="text-zinc-500">Pagado</span>
            <p class="font-medium">S/ {{ number_format($plan->monto_pagado, 2) }}</p>
        </div>
        <div>
            <span class="text-zinc-500">Pendiente</span>
            <p class="font-medium">S/ {{ number_format($plan->saldo_pendiente, 2) }}</p>
        </div>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">#</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Monto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($installments as $cuota)
                    <tr>
                        <td class="px-4 py-2">{{ $cuota->numero_cuota }}</td>
                        <td class="px-4 py-2">{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">S/ {{ number_format($cuota->monto, 2) }}</td>
                        <td class="px-4 py-2">
                            <span class="rounded-full px-1.5 py-0.5 text-xs
                                @if($cuota->estado === 'pagada') bg-green-100 dark:bg-green-900/30
                                @elseif($cuota->estado === 'vencida') bg-red-100 dark:bg-red-900/30
                                @else bg-zinc-100 dark:bg-zinc-700 @endif">
                                {{ \App\Models\Core\EnrollmentInstallment::ESTADOS[$cuota->estado] ?? $cuota->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            @if(in_array($cuota->estado, ['pendiente', 'vencida', 'parcial']) && auth()->user()->can('cliente-matriculas.update'))
                            <flux:button size="xs" variant="ghost" href="{{ route('cuotas.pagar', $cuota) }}" wire:navigate>Pagar</flux:button>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-6 text-center text-zinc-500">
        <p class="mb-2">Esta matrícula no tiene plan de cuotas.</p>
        @can('cliente-matriculas.create')
        <flux:button size="xs" href="{{ route('cliente-matriculas.cuotas.crear', $clienteMatricula) }}" wire:navigate>Crear plan de cuotas</flux:button>
        @endcan
    </div>
    @endif
</div>
