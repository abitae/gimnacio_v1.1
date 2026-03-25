<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cronograma de cuotas</h1>
            <p class="text-sm text-zinc-500">{{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
            @if($highlightMatriculaId)
                <p class="text-xs text-zinc-400 mt-1">Filtrando resaltado para matrícula #{{ $highlightMatriculaId }}</p>
            @endif
        </div>
        <div class="flex gap-2">
            <flux:button variant="ghost" size="xs" href="{{ route('cliente-matriculas.index') }}" wire:navigate>Volver</flux:button>
            @if(auth()->user()->can('cliente-matriculas.create') && $highlightMatriculaId)
            <flux:button size="xs" href="{{ route('clientes.cuotas.crear', ['cliente' => $cliente->id, 'matricula' => $highlightMatriculaId]) }}" wire:navigate>
                {{ $plan ? 'Añadir cuotas (esta matrícula)' : 'Crear plan de cuotas' }}
            </flux:button>
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
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Matrícula / concepto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Vencimiento</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Monto</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($installments as $cuota)
                    <tr @class(['bg-sky-50/80 dark:bg-sky-950/20' => $highlightMatriculaId && (int) $cuota->cliente_matricula_id === (int) $highlightMatriculaId])>
                        <td class="px-4 py-2">{{ $cuota->numero_cuota }}</td>
                        <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">
                            @if($cuota->clienteMatricula)
                                #{{ $cuota->cliente_matricula_id }} — {{ $cuota->clienteMatricula->nombre }}
                            @else
                                —
                            @endif
                        </td>
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
                            <flux:button size="xs" variant="ghost" type="button" wire:click="openRegistrarPagoCuota({{ $cuota->id }})">{{ __('Pagar') }}</flux:button>
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
        <p class="mb-2">Este cliente no tiene plan de cuotas.</p>
        @can('cliente-matriculas.create')
            @if($highlightMatriculaId)
                <flux:button size="xs" href="{{ route('clientes.cuotas.crear', ['cliente' => $cliente->id, 'matricula' => $highlightMatriculaId]) }}" wire:navigate>Crear plan de cuotas</flux:button>
            @else
                <p class="text-xs mb-2">Abre esta pantalla desde una matrícula para crear cuotas asociadas.</p>
            @endif
        @endcan
    </div>
    @endif

    @can('cliente-matriculas.update')
    <flux:modal name="schedule-pago-cuota-modal" wire:model="cuotaPagoModalAbierto" focusable class="md:w-lg">
        <form wire:submit.prevent="guardarPagoCuota" class="space-y-3 p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Registrar pago de cuota') }}</h2>
            <p class="text-xs text-zinc-500">{{ __('Requiere caja abierta. El monto debe coincidir con la cuota programada.') }}</p>
            <flux:input size="xs" type="number" step="0.01" wire:model="pagoCuotaForm.monto" label="{{ __('Monto') }}" required />
            <flux:input size="xs" type="date" wire:model="pagoCuotaForm.fecha_pago" label="{{ __('Fecha') }}" required />
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Medio de pago') }}</label>
                <select wire:model="pagoCuotaForm.payment_method_id"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="">{{ __('—') }}</option>
                    @foreach ($paymentMethods as $pm)
                        <option value="{{ $pm->id }}">{{ $pm->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <flux:input size="xs" wire:model="pagoCuotaForm.numero_operacion" label="{{ __('Nº operación') }}" />
            <flux:input size="xs" wire:model="pagoCuotaForm.entidad_financiera" label="{{ __('Entidad') }}" />
            <div class="flex justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" size="xs" wire:click="closeCuotaPagoModal">{{ __('Cancelar') }}</flux:button>
                <flux:button type="submit" variant="primary" size="xs">{{ __('Registrar pago') }}</flux:button>
            </div>
        </form>
    </flux:modal>
    @endcan
</div>
