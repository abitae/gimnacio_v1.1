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
                    @php
                        $cli = $c->plan->cliente;
                        $mat = $c->clienteMatricula;
                    @endphp
                    <tr>
                        <td class="px-4 py-2">{{ $cli->nombres }} {{ $cli->apellidos }}</td>
                        <td class="px-4 py-2">{{ $mat ? $mat->nombre : '—' }}</td>
                        <td class="px-4 py-2">{{ $c->numero_cuota }}</td>
                        <td class="px-4 py-2">{{ $c->fecha_vencimiento->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">S/ {{ number_format($c->monto, 2) }}</td>
                        <td class="px-4 py-2">
                            @can('cliente-matriculas.update')
                            <flux:button size="xs" variant="ghost" type="button" wire:click="openRegistrarPagoCuota({{ $c->id }})">{{ __('Pagar') }}</flux:button>
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

    @can('cliente-matriculas.update')
    <flux:modal name="reporte-pago-cuota-modal" wire:model="cuotaPagoModalAbierto" focusable class="md:w-lg">
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
