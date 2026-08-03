<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Ventas a crédito</h1>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">Cobro individual, masivo por selección o pago total del cliente.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ $exportUrl }}" target="_blank" rel="noopener"
                class="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-300 dark:hover:bg-green-900/30">
                <flux:icon name="table-cells" class="size-4" />
                Exportar Excel
            </a>
        </div>
    </div>

    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
        <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="DNI, código, celular, venta o nombre" />
        <flux:input type="date" size="xs" wire:model.live="fechaInicio" label="Desde" />
        <flux:input type="date" size="xs" wire:model.live="fechaFin" label="Hasta" />
        <flux:select size="xs" wire:model.live="perPage" label="Filas por página">
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </flux:select>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Ventas</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $totales['cantidad_ventas'] ?? 0 }}</div>
        </div>
        <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-900/50 dark:bg-sky-950/30">
            <div class="text-xs font-medium text-sky-600 dark:text-sky-400">Total vendido</div>
            <div class="text-lg font-bold text-sky-700 dark:text-sky-300">S/ {{ number_format((float) ($totales['total_ventas'] ?? 0), 2) }}</div>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
            <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Pagado</div>
            <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format((float) ($totales['total_pagado'] ?? 0), 2) }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
            <div class="text-xs font-medium text-amber-700 dark:text-amber-400">Saldo pendiente</div>
            <div class="text-lg font-bold text-amber-800 dark:text-amber-300">S/ {{ number_format((float) ($totales['total_saldo_pendiente'] ?? 0), 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50/60 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
            <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Con saldo (clientes)</div>
            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $totales['cantidad_con_saldo'] ?? 0 }}</div>
        </div>
    </div>

    <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50/70 px-4 py-3 dark:border-emerald-900/40 dark:bg-emerald-950/20 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Cobro masivo</div>
            <div class="text-xs text-emerald-800/80 dark:text-emerald-300/80">
                @if ($cantidadSeleccionadas > 0)
                    {{ $cantidadSeleccionadas }} venta(s) seleccionada(s) · Total S/ {{ number_format((float) $totalSeleccionado, 2) }}
                @else
                    Marque las ventas con saldo de clientes del gimnasio para cobrar en lote.
                @endif
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <flux:button size="xs" variant="ghost" wire:click="seleccionarPaginaActual" :disabled="empty($debtIdsPagina)">
                Seleccionar página
            </flux:button>
            <flux:button size="xs" variant="ghost" wire:click="limpiarSeleccion" :disabled="$cantidadSeleccionadas === 0">
                Limpiar
            </flux:button>
            <flux:button size="xs" variant="primary" color="green" icon="credit-card"
                wire:click="abrirModalCobroMasivo"
                :disabled="$cantidadSeleccionadas === 0">
                Cobrar seleccionadas ({{ $cantidadSeleccionadas }})
            </flux:button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="min-w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="w-12 px-3 py-3 text-center">
                        @if (! empty($debtIdsPagina))
                            <input type="checkbox"
                                class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800"
                                wire:click="alternarSeleccionPaginaActual"
                                @checked($paginaCompletaSeleccionada)
                                title="Seleccionar o quitar toda la página"
                                aria-label="Seleccionar toda la página"
                            />
                        @endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Cliente</th>
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
                        $fila = $filasVentas[$v->id] ?? [];
                        $debt = $v->clientDebt;
                        $saldo = (float) ($fila['saldo'] ?? 0);
                        $montoPagado = (float) ($fila['monto_pagado'] ?? 0);
                        $estado = $fila['estado'] ?? 'pendiente';
                        $esCobrable = ! empty($fila['es_cobrable_cliente']) && ! empty($fila['client_debt_id']);
                        $debtId = (int) ($fila['client_debt_id'] ?? 0);
                        $seleccionada = $debtId > 0 && in_array($debtId, $deudasSeleccionadas, true);
                    @endphp
                    <tr wire:key="venta-credito-{{ $v->id }}"
                        class="transition-colors {{ $seleccionada ? 'bg-emerald-50/80 dark:bg-emerald-950/25' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30' }}">
                        <td class="px-3 py-3 text-center align-middle">
                            @if ($esCobrable)
                                <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                    <input type="checkbox"
                                        class="size-4 rounded border-zinc-300 text-emerald-600 focus:ring-emerald-500 dark:border-zinc-600 dark:bg-zinc-800"
                                        wire:click.stop="alternarDeudaSeleccionada({{ $debtId }})"
                                        @checked($seleccionada)
                                        aria-label="Seleccionar venta {{ $v->numero_venta }}"
                                    />
                                </label>
                            @else
                                <span class="text-[10px] text-zinc-400" title="Solo clientes del gimnasio con saldo">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $fila['codigo'] ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">
                            <div class="font-medium text-zinc-800 dark:text-zinc-100">{{ $fila['comprador_nombre'] ?? '—' }}</div>
                            <div class="mt-1 flex flex-wrap items-center gap-1 text-[11px] text-zinc-500 dark:text-zinc-400">
                                <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">{{ $fila['comprador_tipo'] ?? '' }}</span>
                                @if (! empty($fila['comprador_detalle']))
                                    <span>{{ $fila['comprador_detalle'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3 text-xs font-medium">{{ $v->numero_venta }}</td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format((float) $v->total, 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format($montoPagado, 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-semibold text-amber-600 dark:text-amber-400">S/ {{ number_format($saldo, 2) }}</td>
                        <td class="px-4 py-3 text-xs whitespace-nowrap">
                            <div>{{ $v->fecha_venta?->format('d/m/Y H:i') ?? '—' }}</div>
                            <div class="text-[11px] text-zinc-500">{{ $v->usuario?->name ?? '-' }}</div>
                        </td>
                        <td class="px-4 py-3 text-xs">
                            <span class="rounded-full px-2 py-1 {{ $estado === 'vencido' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : ($estado === 'parcial' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : ($estado === 'pagado' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300')) }}">
                                {{ ucfirst($estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <x-ui.table-actions>
                                @if ($v->cliente)
                                    <flux:button size="xs" variant="ghost" icon="user-circle" href="{{ route('clientes.perfil', $v->cliente) }}" wire:navigate title="Ver ficha" />
                                    @if ($saldo > 0)
                                        <flux:button size="xs" variant="ghost" icon="wallet" wire:click="abrirModalCobroCliente({{ $v->cliente_id }})" title="Pagar todo del cliente" />
                                    @endif
                                @endif
                                @if ($debt && $saldo > 0)
                                    <flux:button size="xs" variant="primary" color="green" icon="credit-card" wire:click="abrirModalCobroVenta({{ $debt->id }})" title="Pagar esta venta" />
                                @endif
                                <flux:button size="xs" variant="ghost" icon="printer" href="{{ route('ventas.comprobante.pdf', ['venta' => $v->id, 'reprint' => 1]) }}" target="_blank" title="Reimprimir ticket" />
                            </x-ui.table-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay ventas a crédito para los filtros seleccionados.</td>
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

    <flux:modal wire:model="mostrarModalCobroMasivo" class="md:w-lg">
        <div class="space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Cobro masivo</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ $cantidadSeleccionadas }} venta(s) seleccionada(s)
                    · Total a cobrar S/ {{ number_format((float) $totalSeleccionado, 2) }}
                </p>
                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Se liquidará el saldo completo de cada venta seleccionada.</p>
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
                <flux:button variant="ghost" wire:click="cerrarModalCobroMasivo">Cancelar</flux:button>
                <flux:button variant="primary" color="green" wire:click="procesarCobroMasivo">Registrar cobros</flux:button>
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
