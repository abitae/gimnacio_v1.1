<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Ventas a crédito</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Seguimiento por venta y pago total por cliente desde la deuda más antigua a la más reciente.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
            <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="DNI, código, celular, venta o nombre" />
            <flux:input type="date" size="xs" wire:model.live="fechaInicio" />
            <flux:input type="date" size="xs" wire:model.live="fechaFin" />
            <flux:select size="xs" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Documento</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Celular</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Venta</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Pagado</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Saldo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($ventas as $v)
                    @php
                        $debt = $v->clientDebt;
                        $saldo = (float) ($debt?->saldo_pendiente ?? max(0, ($v->total ?? 0) - ($v->monto_inicial ?? 0)));
                        $montoPagado = (float) ($debt?->monto_pagado ?? ($v->monto_inicial ?? 0));
                        $estado = $debt?->estado ?? ($saldo > 0 ? 'pendiente' : 'pagado');
                    @endphp
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-3 text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $v->cliente?->codigo ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $v->cliente ? $v->cliente->nombres.' '.$v->cliente->apellidos : '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $v->cliente?->tipo_documento }} {{ $v->cliente?->numero_documento }}</td>
                        <td class="px-4 py-3 text-xs">{{ $v->cliente?->telefono ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs font-medium">{{ $v->numero_venta }}</td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format((float) $v->total, 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format($montoPagado, 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-semibold text-amber-600 dark:text-amber-400">S/ {{ number_format($saldo, 2) }}</td>
                        <td class="px-4 py-3 text-xs">{{ $v->fecha_venta?->format('d/m/Y H:i') ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <span class="rounded-full px-2 py-1 {{ $estado === 'vencido' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : ($estado === 'parcial' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : ($estado === 'pagado' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300')) }}">
                                {{ ucfirst($estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-1">
                                @if ($v->cliente)
                                    <flux:button size="xs" variant="ghost" href="{{ route('clientes.perfil', $v->cliente) }}" wire:navigate>Ficha</flux:button>
                                    @if ($saldo > 0)
                                        <flux:button size="xs" variant="ghost" wire:click="abrirModalCobroCliente({{ $v->cliente_id }})">Pagar cliente</flux:button>
                                    @endif
                                @endif
                                @if ($debt && $saldo > 0)
                                    <flux:button size="xs" variant="primary" color="green" wire:click="abrirModalCobroVenta({{ $debt->id }})">Pagar venta</flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay ventas a crédito para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">{{ $ventas->links() }}</div>

    <flux:modal wire:model="mostrarModalCobroVenta" class="md:w-lg">
        <div class="space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Pagar venta específica</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Registra el pago de la venta seleccionada y recalcula su saldo pendiente.</p>
            </div>
            <flux:input size="xs" wire:model.live.number="cobroForm.monto_pago" type="number" step="0.01" min="0.01" label="Monto" />
            <flux:select size="xs" wire:model.live="cobroForm.payment_method_id" label="Método de pago">
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->nombre }}</option>
                @endforeach
            </flux:select>
            @if ($selectedPaymentMethod?->requiere_numero_operacion)
                <flux:input size="xs" wire:model.live="cobroForm.numero_operacion" label="Número de operación" />
            @endif
            @if ($selectedPaymentMethod?->requiere_entidad)
                <flux:input size="xs" wire:model.live="cobroForm.entidad_financiera" label="Entidad financiera" />
            @endif
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cerrarModalCobroVenta">Cancelar</flux:button>
                <flux:button variant="primary" color="green" wire:click="procesarCobroVenta">Registrar pago</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalCobroCliente" class="md:w-lg">
        <div class="space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Pagar deuda total del cliente</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ $clienteSeleccionado ? $clienteSeleccionado->nombres.' '.$clienteSeleccionado->apellidos : 'Cliente' }}
                    · Total pendiente S/ {{ number_format((float) $totalClienteSeleccionado, 2) }}
                </p>
            </div>
            <flux:select size="xs" wire:model.live="cobroForm.payment_method_id" label="Método de pago">
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->nombre }}</option>
                @endforeach
            </flux:select>
            @if ($selectedPaymentMethod?->requiere_numero_operacion)
                <flux:input size="xs" wire:model.live="cobroForm.numero_operacion" label="Número de operación" />
            @endif
            @if ($selectedPaymentMethod?->requiere_entidad)
                <flux:input size="xs" wire:model.live="cobroForm.entidad_financiera" label="Entidad financiera" />
            @endif
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cerrarModalCobroCliente">Cancelar</flux:button>
                <flux:button variant="primary" color="green" wire:click="procesarCobroCliente">Pagar total</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalTicketPago" class="md:w-5xl">
        <div class="p-4">
            @if ($pagoIdTicket)
                <iframe
                    src="{{ route('pagos.ticket.pdf', ['pago' => $pagoIdTicket]) }}"
                    class="h-[70vh] w-full rounded-xl border border-zinc-200 dark:border-zinc-700"
                    title="Ticket de pago"
                ></iframe>
            @endif
        </div>
    </flux:modal>
</div>
