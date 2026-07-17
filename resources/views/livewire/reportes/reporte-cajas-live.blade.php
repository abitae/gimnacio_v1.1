<div class="space-y-5 rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-4 text-white">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold">Reporte de cajas</h1>
                <p class="text-sm text-emerald-100">Filtro por sucursal, usuario y fechas con detalle completo de movimientos.</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <x-reportes.exportar-buttons tipo="cajas" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
                <button type="button" onclick="window.print()" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20">
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-4 px-5 pb-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4 print:hidden">
            <flux:input type="date" size="xs" wire:model.live="fechaDesde" label="Fecha inicio" />
            <flux:input type="date" size="xs" wire:model.live="fechaHasta" label="Fecha fin" />
            <flux:select size="xs" wire:model.live="sucursalId" label="Sucursal">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                @endforeach
            </flux:select>
            <flux:select size="xs" wire:model.live="usuarioId" label="Usuario">
                <option value="">Todos</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Cajas</div>
                <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['cantidad'] }}</div>
            </div>
            <div class="rounded-xl border border-green-100 bg-green-50/60 p-4 dark:border-green-900/50 dark:bg-green-950/30">
                <div class="text-xs font-medium text-green-600 dark:text-green-400">Abiertas</div>
                <div class="text-lg font-bold text-green-700 dark:text-green-300">{{ $resumen['abiertas'] }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Cerradas</div>
                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $resumen['cerradas'] }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total ingresos</div>
                <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format((float) $resumen['total_ingresos'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-red-100 bg-red-50/60 p-4 dark:border-red-900/50 dark:bg-red-950/30">
                <div class="text-xs font-medium text-red-600 dark:text-red-400">Total salidas</div>
                <div class="text-lg font-bold text-red-700 dark:text-red-300">S/ {{ number_format((float) $resumen['total_salidas'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-900/50 dark:bg-sky-950/30">
                <div class="text-xs font-medium text-sky-600 dark:text-sky-400">Total vendido</div>
                <div class="text-lg font-bold text-sky-700 dark:text-sky-300">S/ {{ number_format((float) ($resumen['total_vendido'] ?? 0), 2) }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Totales por método de pago</h2>
                <div class="space-y-3">
                    @forelse(($resumen['por_metodo_pago'] ?? []) as $metodo => $datos)
                        @php
                            $esDetalle = is_array($datos);
                            $totalMetodo = $esDetalle ? (float) ($datos['total'] ?? 0) : (float) $datos;
                            $porTipo = $esDetalle ? ($datos['por_tipo'] ?? []) : [];
                        @endphp
                        <div class="rounded-lg bg-zinc-50 px-3 py-2 dark:bg-zinc-900/50">
                            <div class="flex items-center justify-between text-sm">
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $metodo ?: 'Sin método' }}</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($totalMetodo, 2) }}</span>
                            </div>
                            @if (! empty($porTipo))
                                <div class="mt-1.5 space-y-1 border-t border-zinc-200 pt-1.5 dark:border-zinc-700">
                                    @foreach ($porTipo as $tipo => $fila)
                                        <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                                            <span>{{ $tipo }}@if (! empty($fila['cantidad'])) <span class="text-zinc-400">({{ $fila['cantidad'] }})</span>@endif</span>
                                            <span class="font-medium">S/ {{ number_format((float) ($fila['total'] ?? 0), 2) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Sin movimientos por método de pago.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Totales por usuario</h2>
                <div class="space-y-2">
                    @forelse(($resumen['por_usuario'] ?? []) as $usuario => $monto)
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900/50">
                            <span>{{ $usuario }}</span>
                            <span class="font-semibold">S/ {{ number_format((float) $monto, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Sin movimientos agrupados por usuario.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Cajas</h2>
                <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400 print:hidden">
                    Filas
                    <select wire:model.live="perPageCajas"
                        class="rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium">Caja</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Usuario</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Sucursal</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Apertura</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Cierre</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Estado</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Inicial</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Final</th>
                        <th class="px-3 py-2 text-right text-xs font-medium print:hidden">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($cajas as $c)
                        <tr>
                            <td class="px-3 py-2 text-xs font-semibold">#{{ $c->id }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->usuario?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->sucursal?->nombre ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->fecha_apertura?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->fecha_cierre?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs capitalize">{{ $c->estado }}</td>
                            <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) $c->saldo_inicial, 2) }}</td>
                            <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) ($c->saldo_final ?? 0), 2) }}</td>
                            <td class="px-3 py-2 text-right text-xs print:hidden">
                                <flux:button type="button" size="xs" variant="ghost" icon="eye"
                                    wire:click="abrirDetalleCaja({{ $c->id }})">
                                    Detalle
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-4 text-center text-zinc-500">No hay cajas en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($cajas->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700 print:hidden">
                    {{ $cajas->links() }}
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                    Detalle de movimientos
                </div>
                <label class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400 print:hidden">
                    Filas
                    <select wire:model.live="perPageMovimientos"
                        class="rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="10">10</option>
                        <option value="15">15</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </label>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-white dark:bg-zinc-950">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium">Fecha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Caja</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Usuario</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Sucursal</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Concepto</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Método</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Operación</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Monto</th>
                        <th class="px-3 py-2 text-right text-xs font-medium print:hidden">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($detalleMovimientos as $movimiento)
                        <tr>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['fecha']?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">#{{ $movimiento['caja_id'] }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['usuario_caja'] ?? $movimiento['usuario'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['sucursal_caja'] ?? $movimiento['sucursal'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['concepto'] }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['metodo_pago'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['numero_operacion'] ?: ($movimiento['referencia_label'] ?: '-') }}</td>
                            <td class="px-3 py-2 text-right text-xs font-semibold {{ $movimiento['tipo'] === 'entrada' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $movimiento['tipo'] === 'entrada' ? '+' : '-' }} S/ {{ number_format((float) $movimiento['monto'], 2) }}
                            </td>
                            <td class="px-3 py-2 text-right text-xs print:hidden">
                                @if (! empty($movimiento['ticket_venta_id']))
                                    <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                        wire:click="abrirTicketVenta({{ $movimiento['ticket_venta_id'] }})"
                                        title="Ver ticket de venta">
                                        Detalle
                                    </flux:button>
                                @elseif (! empty($movimiento['ticket_pago_id']))
                                    <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                        wire:click="abrirTicketPago({{ $movimiento['ticket_pago_id'] }})"
                                        title="Ver ticket de pago / membresía">
                                        Detalle
                                    </flux:button>
                                @else
                                    <span class="text-zinc-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-3 py-6 text-center text-zinc-500">Sin movimientos en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($detalleMovimientos->hasPages())
                <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700 print:hidden">
                    {{ $detalleMovimientos->links() }}
                </div>
            @endif
        </div>
    </div>

    <flux:modal wire:model="mostrarModalDetalleCaja" focusable class="md:max-w-4xl">
        <div class="flex flex-col p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Detalle de caja</h2>
                <div class="flex gap-2">
                    @if ($cajaDetalleId)
                        <a href="{{ route('reportes.cajas.exportar.pdf', [
                            'fecha_desde' => $fechaDesde ?: null,
                            'fecha_hasta' => $fechaHasta ?: null,
                            'usuario_id' => $usuarioId ?: null,
                            'sucursal_id' => $sucursalId ?: null,
                            'caja_id' => $cajaDetalleId,
                            'inline' => 1,
                        ]) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Abrir en nueva pestana
                        </a>
                    @endif
                    <flux:button variant="ghost" size="sm" type="button" wire:click="cerrarDetalleCaja">Cerrar</flux:button>
                </div>
            </div>
            @if ($cajaDetalleId)
                <iframe
                    src="{{ route('reportes.cajas.exportar.pdf', [
                        'fecha_desde' => $fechaDesde ?: null,
                        'fecha_hasta' => $fechaHasta ?: null,
                        'usuario_id' => $usuarioId ?: null,
                        'sucursal_id' => $sucursalId ?: null,
                        'caja_id' => $cajaDetalleId,
                        'inline' => 1,
                    ]) }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"
                    style="height: 75vh; min-height: 400px;"
                    title="Detalle PDF de caja">
                </iframe>
            @endif
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalTicketVenta" focusable class="md:max-w-4xl">
        <div class="flex flex-col p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ticket de venta</h2>
                <div class="flex gap-2">
                    @if ($ventaIdTicketReporte)
                        <a href="{{ route('ventas.comprobante.pdf', ['venta' => $ventaIdTicketReporte]) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Abrir en nueva pestana
                        </a>
                    @endif
                    <flux:button variant="ghost" size="sm" type="button" wire:click="cerrarTicketVenta">Cerrar</flux:button>
                </div>
            </div>
            @if ($ventaIdTicketReporte)
                <iframe
                    src="{{ route('ventas.comprobante.pdf', ['venta' => $ventaIdTicketReporte]) }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"
                    style="height: 75vh; min-height: 400px;"
                    title="Ticket PDF de venta">
                </iframe>
            @endif
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalTicketPago" focusable class="md:max-w-4xl">
        <div class="flex flex-col p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ticket de pago</h2>
                <div class="flex gap-2">
                    @if ($pagoIdTicketReporte)
                        <a href="{{ route('pagos.ticket.pdf', ['pago' => $pagoIdTicketReporte]) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Abrir en nueva pestana
                        </a>
                    @endif
                    <flux:button variant="ghost" size="sm" type="button" wire:click="cerrarTicketPago">Cerrar</flux:button>
                </div>
            </div>
            @if ($pagoIdTicketReporte)
                <iframe
                    src="{{ route('pagos.ticket.pdf', ['pago' => $pagoIdTicketReporte]) }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"
                    style="height: 75vh; min-height: 400px;"
                    title="Ticket PDF de pago">
                </iframe>
            @endif
        </div>
    </flux:modal>
</div>
