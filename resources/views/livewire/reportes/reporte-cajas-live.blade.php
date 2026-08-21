<div class="space-y-5 rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-4 text-white">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold">Reporte de cajas</h1>
                <p class="text-sm text-emerald-100">Filtro por usuario y fechas con detalle completo de movimientos.</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <x-reportes.exportar-buttons tipo="cajas" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" :usuarioId="$usuarioId" :reporteModoSucursal="$reporteModoSucursal" :reporteSucursalId="$reporteSucursalId" />
                <button type="button" onclick="window.print()" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20">
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-4 px-5 pb-5">
        <x-reportes.sucursal-scope-panel
            :etiqueta="$reporteSucursalEtiqueta"
            :puede-elegir="$reportePuedeElegirSucursal"
            :sucursales="$reporteSucursalesDisponibles"
        />

        <div class="grid gap-3 lg:grid-cols-[1fr_auto] print:hidden">
            <x-reportes.filtros-periodo :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" :con-hora="true" />
            <div class="rounded-xl border border-indigo-200/60 bg-gradient-to-br from-indigo-50/80 to-white p-4 shadow-sm dark:border-indigo-800/60 dark:from-indigo-950/30 dark:to-zinc-900/80">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">Usuario de caja</p>
                <select wire:model.live="usuarioId"
                    class="w-full min-w-52 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 dark:border-indigo-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Todos</option>
                    @foreach ($usuarios as $usuario)
                        <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
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
            <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4 dark:border-amber-900/50 dark:bg-amber-950/30">
                <div class="text-xs font-medium text-amber-700 dark:text-amber-400">Ventas al crédito</div>
                <div class="text-lg font-bold text-amber-800 dark:text-amber-300">{{ $resumen['ventas_credito']['cantidad'] ?? 0 }}</div>
                <div class="text-xs text-amber-700/80 dark:text-amber-400/80">S/ {{ number_format((float) ($resumen['ventas_credito']['total_ventas'] ?? 0), 2) }} · Pendiente S/ {{ number_format((float) ($resumen['ventas_credito']['total_saldo_pendiente'] ?? 0), 2) }}</div>
            </div>
        </div>

        @php
            $matriz = $matrizTipoMetodo ?? ($resumen['matriz_tipo_metodo'] ?? []);
            $metodosMatriz = $matriz['metodos'] ?? [];
            $celdasMatriz = $matriz['celdas'] ?? [];
        @endphp

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <div>
                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Totales por tipo y método de pago</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Solo ingresos reales de caja. Las ventas a crédito no se incluyen. El pie muestra el total del período.</p>
                </div>
                <div class="print:hidden">
                    <flux:button type="button" size="xs" variant="ghost" icon="document-arrow-down"
                        wire:click="abrirPreviewMatrizPdf"
                        wire:loading.attr="disabled"
                        wire:target="abrirPreviewMatrizPdf">
                        Exportar PDF
                    </flux:button>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="sticky left-0 z-10 bg-zinc-50 px-3 py-2 text-left text-xs font-medium dark:bg-zinc-900">Tipo</th>
                            @foreach ($metodosMatriz as $metodo)
                                <th class="px-3 py-2 text-right text-xs font-medium whitespace-nowrap">{{ $metodo }}</th>
                            @endforeach
                            <th class="px-3 py-2 text-right text-xs font-medium">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($tiposMatriz as $tipo)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="sticky left-0 bg-white px-3 py-2 text-xs font-medium text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">{{ $tipo }}</td>
                                @foreach ($metodosMatriz as $metodo)
                                    @php $celda = $celdasMatriz[$tipo][$metodo] ?? null; @endphp
                                    <td class="px-3 py-2 text-right text-xs">
                                        @if ($celda)
                                            S/ {{ number_format((float) $celda['total'], 2) }}
                                            @if (! empty($celda['cantidad']))
                                                <button type="button"
                                                    wire:click="abrirDetalleMatriz(@js($tipo), @js($metodo))"
                                                    class="ml-0.5 text-indigo-600 hover:underline dark:text-indigo-400 print:text-zinc-400 print:no-underline"
                                                    title="Ver operaciones">
                                                    ({{ $celda['cantidad'] }})
                                                </button>
                                            @endif
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right text-xs font-semibold">
                                    S/ {{ number_format((float) ($matriz['totales_tipo'][$tipo] ?? 0), 2) }}
                                    @if (! empty($matriz['cantidades_tipo'][$tipo]))
                                        <button type="button"
                                            wire:click="abrirDetalleMatriz(@js($tipo), null)"
                                            class="ml-0.5 font-normal text-indigo-600 hover:underline dark:text-indigo-400 print:text-zinc-400 print:no-underline"
                                            title="Ver operaciones del tipo">
                                            ({{ $matriz['cantidades_tipo'][$tipo] }})
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(2, count($metodosMatriz) + 2) }}" class="px-3 py-6 text-center text-zinc-500">Sin ingresos de caja en el período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($tiposMatriz->total() > 0)
                        <tfoot class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <td class="sticky left-0 bg-zinc-50 px-3 py-2 text-xs font-semibold dark:bg-zinc-900">Total período</td>
                                @foreach ($metodosMatriz as $metodo)
                                    <td class="px-3 py-2 text-right text-xs font-semibold">
                                        S/ {{ number_format((float) ($matriz['totales_metodo'][$metodo] ?? 0), 2) }}
                                        @if (! empty($matriz['cantidades_metodo'][$metodo]))
                                            <button type="button"
                                                wire:click="abrirDetalleMatriz(null, @js($metodo))"
                                                class="ml-0.5 font-normal text-indigo-600 hover:underline dark:text-indigo-400 print:text-zinc-400 print:no-underline"
                                                title="Ver operaciones del método">
                                                ({{ $matriz['cantidades_metodo'][$metodo] }})
                                            </button>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                    S/ {{ number_format((float) ($matriz['total_general'] ?? 0), 2) }}
                                    @if (! empty($matriz['cantidad_general']))
                                        <button type="button"
                                            wire:click="abrirDetalleMatriz(null, null)"
                                            class="ml-0.5 font-normal text-indigo-600 hover:underline dark:text-indigo-400 print:text-zinc-400 print:no-underline"
                                            title="Ver todas las operaciones">
                                            ({{ $matriz['cantidad_general'] }})
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <x-reportes.table-pagination :paginator="$tiposMatriz" model="perPageMatriz" />
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Resumen por usuario de caja</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $porUsuario->total() }} usuario(s) en el período.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white dark:bg-zinc-950">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium">Usuario</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Cajas</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Ingresos caja</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Salidas</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Ventas crédito</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Saldo crédito</th>
                            <th class="px-3 py-2 text-right text-xs font-medium print:hidden">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($porUsuario as $filaUsuario)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-3 py-2 text-xs font-medium">{{ $filaUsuario['usuario'] ?? 'Sin usuario' }}</td>
                                <td class="px-3 py-2 text-right text-xs">{{ $filaUsuario['cantidad_cajas'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-right text-xs font-semibold text-emerald-700 dark:text-emerald-400">S/ {{ number_format((float) ($filaUsuario['total_ingresos'] ?? 0), 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs text-red-600 dark:text-red-400">S/ {{ number_format((float) ($filaUsuario['total_salidas'] ?? 0), 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs text-amber-700 dark:text-amber-400">S/ {{ number_format((float) ($filaUsuario['ventas_credito_total'] ?? 0), 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs text-amber-800 dark:text-amber-300">S/ {{ number_format((float) ($filaUsuario['saldo_credito_pendiente'] ?? 0), 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs print:hidden">
                                    <x-ui.table-actions>
                                    <flux:button type="button" size="xs" variant="ghost" icon="eye"
                                        wire:click="$set('usuarioId', '{{ $filaUsuario['usuario_id'] ?? '' }}')"
                                        aria-label="Ver cajas" />
                                    </x-ui.table-actions>
                                </td>
                            </tr>
                            @if (! empty($filaUsuario['cajas']) && filled($usuarioId) && (int) $usuarioId === (int) ($filaUsuario['usuario_id'] ?? 0))
                                @foreach ($filaUsuario['cajas'] as $cajaUsuario)
                                    <tr class="bg-zinc-50/80 dark:bg-zinc-900/40">
                                        <td class="px-3 py-1.5 pl-8 text-xs text-zinc-600 dark:text-zinc-400">Caja #{{ $cajaUsuario['caja_id'] }}</td>
                                        <td class="px-3 py-1.5 text-right text-xs capitalize">{{ $cajaUsuario['estado'] ?? '-' }}</td>
                                        <td class="px-3 py-1.5 text-right text-xs">S/ {{ number_format((float) ($cajaUsuario['total_ingresos'] ?? 0), 2) }}</td>
                                        <td class="px-3 py-1.5 text-right text-xs">S/ {{ number_format((float) ($cajaUsuario['total_salidas'] ?? 0), 2) }}</td>
                                        <td class="px-3 py-1.5 text-right text-xs" colspan="2">
                                            {{ $cajaUsuario['fecha_apertura']?->format('d/m/Y H:i') ?? '—' }}
                                            @if ($cajaUsuario['fecha_cierre'])
                                                → {{ $cajaUsuario['fecha_cierre']->format('d/m/Y H:i') }}
                                            @endif
                                        </td>
                                        <td class="px-3 py-1.5 text-right text-xs print:hidden">
                                            <x-ui.table-actions>
                                            <flux:button type="button" size="xs" variant="ghost" icon="document-text"
                                                wire:click="abrirDetalleCaja({{ $cajaUsuario['caja_id'] }})"
                                                aria-label="Detalle" />
                                            </x-ui.table-actions>
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        @empty
                            <tr>
                                <td colspan="7" class="px-3 py-6 text-center text-zinc-500">Sin movimientos agrupados por usuario.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-reportes.table-pagination :paginator="$porUsuario" model="perPageUsuarios" />
        </div>

        <div class="overflow-hidden rounded-xl border border-amber-200 dark:border-amber-900/50">
            <div class="border-b border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-950/40">
                <h2 class="text-sm font-semibold text-amber-900 dark:text-amber-100">Ventas al crédito (no suman a caja)</h2>
                <p class="text-xs text-amber-800/80 dark:text-amber-300/80">
                    {{ $ventasCredito->total() }} ventas · Total S/ {{ number_format((float) ($resumen['ventas_credito']['total_ventas'] ?? 0), 2) }} ·
                    Anticipos S/ {{ number_format((float) ($resumen['ventas_credito']['total_anticipos'] ?? 0), 2) }} ·
                    Pendiente S/ {{ number_format((float) ($resumen['ventas_credito']['total_saldo_pendiente'] ?? 0), 2) }}
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-white dark:bg-zinc-950">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium">Venta</th>
                            <th class="px-3 py-2 text-left text-xs font-medium">Fecha</th>
                            <th class="px-3 py-2 text-left text-xs font-medium">Caja / Usuario</th>
                            <th class="px-3 py-2 text-left text-xs font-medium">Comprador</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Total</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Anticipo</th>
                            <th class="px-3 py-2 text-right text-xs font-medium">Pendiente</th>
                            <th class="px-3 py-2 text-right text-xs font-medium print:hidden">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100 dark:divide-amber-900/30">
                        @forelse ($ventasCredito as $ventaCredito)
                            <tr class="hover:bg-amber-50/60 dark:hover:bg-amber-950/20">
                                <td class="px-3 py-2 text-xs font-medium">{{ $ventaCredito['numero_venta'] }}</td>
                                <td class="px-3 py-2 text-xs">{{ $ventaCredito['fecha']?->format('d/m/Y H:i') ?? '—' }}</td>
                                <td class="px-3 py-2 text-xs">#{{ $ventaCredito['caja_id'] }} · {{ $ventaCredito['usuario_caja'] }}</td>
                                <td class="px-3 py-2 text-xs">{{ $ventaCredito['comprador'] ?: '—' }}</td>
                                <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) $ventaCredito['total'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs text-emerald-700 dark:text-emerald-400">S/ {{ number_format((float) $ventaCredito['monto_inicial'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs font-semibold text-amber-800 dark:text-amber-300">S/ {{ number_format((float) $ventaCredito['saldo_pendiente'], 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs print:hidden">
                                    <x-ui.table-actions>
                                    <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                        wire:click="abrirTicketVenta({{ $ventaCredito['venta_id'] }})"
                                        aria-label="Ver venta" />
                                    </x-ui.table-actions>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-3 py-6 text-center text-zinc-500">No hay ventas al crédito en el período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-reportes.table-pagination :paginator="$ventasCredito" model="perPageVentasCredito" />
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Cajas</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $cajas->total() }} caja(s) en el período.</p>
            </div>
            <div class="overflow-x-auto">
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
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                <td class="px-3 py-2 text-xs font-semibold">#{{ $c->id }}</td>
                                <td class="px-3 py-2 text-xs">{{ $c->usuario?->name ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs">{{ $c->sucursal?->nombre ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs">{{ $c->fecha_apertura?->format('d/m/Y H:i') }}</td>
                                <td class="px-3 py-2 text-xs">{{ $c->fecha_cierre?->format('d/m/Y H:i') ?? '-' }}</td>
                                <td class="px-3 py-2 text-xs capitalize">{{ $c->estado }}</td>
                                <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) $c->saldo_inicial, 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) ($c->saldo_final ?? 0), 2) }}</td>
                                <td class="px-3 py-2 text-right text-xs print:hidden">
                                    <x-ui.table-actions>
                                    <flux:button type="button" size="xs" variant="ghost" icon="eye"
                                        wire:click="abrirDetalleCaja({{ $c->id }})"
                                        aria-label="Detalle" />
                                    </x-ui.table-actions>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-6 text-center text-zinc-500">No hay cajas en el período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-reportes.table-pagination :paginator="$cajas" model="perPageCajas" />
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Detalle de movimientos</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $detalleMovimientos->total() }} movimiento(s) en el período.</p>
            </div>
            <div class="overflow-x-auto">
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
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
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
                                    <x-ui.table-actions>
                                    @if (! empty($movimiento['ticket_venta_id']))
                                        <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                            wire:click="abrirTicketVenta({{ $movimiento['ticket_venta_id'] }})"
                                            title="Ver ticket de venta"
                                            aria-label="Detalle venta" />
                                    @elseif (! empty($movimiento['ticket_pago_id']))
                                        <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                            wire:click="abrirTicketPago({{ $movimiento['ticket_pago_id'] }})"
                                            title="Ver ticket de pago / membresía"
                                            aria-label="Detalle pago" />
                                    @else
                                        <span class="text-zinc-400">-</span>
                                    @endif
                                    </x-ui.table-actions>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-3 py-6 text-center text-zinc-500">Sin movimientos en el período.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <x-reportes.table-pagination :paginator="$detalleMovimientos" model="perPageMovimientos" />
        </div>
    </div>

    <flux:modal wire:model="mostrarModalMatrizDetalle" focusable class="md:max-w-5xl">
        <div class="flex flex-col p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Detalle de operaciones</h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $this->matrizDetalleTitulo }} · {{ $movimientosMatrizDetalle->total() }} operación(es)</p>
                </div>
                <flux:button variant="ghost" size="sm" type="button" wire:click="cerrarDetalleMatriz">Cerrar</flux:button>
            </div>
            <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium">Fecha</th>
                                <th class="px-3 py-2 text-left text-xs font-medium">Caja</th>
                                <th class="px-3 py-2 text-left text-xs font-medium">Usuario</th>
                                <th class="px-3 py-2 text-left text-xs font-medium">Sucursal</th>
                                <th class="px-3 py-2 text-left text-xs font-medium">Concepto</th>
                                <th class="px-3 py-2 text-left text-xs font-medium">Método</th>
                                <th class="px-3 py-2 text-left text-xs font-medium">Operación</th>
                                <th class="px-3 py-2 text-right text-xs font-medium">Monto</th>
                                <th class="px-3 py-2 text-right text-xs font-medium">Ticket</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($movimientosMatrizDetalle as $movimiento)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/40">
                                    <td class="px-3 py-2 text-xs">{{ $movimiento['fecha']?->format('d/m/Y H:i') ?? '-' }}</td>
                                    <td class="px-3 py-2 text-xs">#{{ $movimiento['caja_id'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $movimiento['usuario_caja'] ?? $movimiento['usuario'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $movimiento['sucursal_caja'] ?? $movimiento['sucursal'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $movimiento['concepto'] ?? '-' }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $movimiento['metodo_pago'] ?: '-' }}</td>
                                    <td class="px-3 py-2 text-xs">{{ $movimiento['numero_operacion'] ?: ($movimiento['referencia_label'] ?: '-') }}</td>
                                    <td class="px-3 py-2 text-right text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                                        + S/ {{ number_format((float) ($movimiento['monto'] ?? 0), 2) }}
                                    </td>
                                    <td class="px-3 py-2 text-right text-xs">
                                        @if (! empty($movimiento['ticket_venta_id']))
                                            <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                                wire:click="abrirTicketVenta({{ $movimiento['ticket_venta_id'] }})"
                                                title="Ver ticket de venta"
                                                aria-label="Detalle venta" />
                                        @elseif (! empty($movimiento['ticket_pago_id']))
                                            <flux:button type="button" size="xs" variant="ghost" icon="printer"
                                                wire:click="abrirTicketPago({{ $movimiento['ticket_pago_id'] }})"
                                                title="Ver ticket de pago / membresía"
                                                aria-label="Detalle pago" />
                                        @else
                                            <span class="text-zinc-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-3 py-6 text-center text-zinc-500">Sin operaciones para este filtro.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-reportes.table-pagination :paginator="$movimientosMatrizDetalle" model="perPageMatrizDetalle" />
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalMatrizPdf" focusable class="md:max-w-5xl">
        <div class="flex flex-col p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Totales por tipo y método de pago</h2>
                <div class="flex flex-wrap gap-2">
                    @if ($mostrarModalMatrizPdf)
                        <a href="{{ route('reportes.cajas.exportar.pdf', $this->matrizPdfQuery(true)) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Abrir en nueva pestaña
                        </a>
                        <a href="{{ route('reportes.cajas.exportar.pdf', $this->matrizPdfQuery(false)) }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-medium text-red-700 shadow-sm hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300 dark:hover:bg-red-900/30">
                            Descargar PDF
                        </a>
                    @endif
                    <flux:button variant="ghost" size="sm" type="button" wire:click="cerrarPreviewMatrizPdf">Cerrar</flux:button>
                </div>
            </div>
            @if ($mostrarModalMatrizPdf)
                <iframe
                    src="{{ route('reportes.cajas.exportar.pdf', $this->matrizPdfQuery(true)) }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"
                    style="height: 75vh; min-height: 400px;"
                    title="PDF de totales por tipo y método de pago">
                </iframe>
            @endif
        </div>
    </flux:modal>

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
                            'reporte_modo_sucursal' => $reporteModoSucursal,
                            'reporte_sucursal_id' => ($reporteModoSucursal === 'specific' && $reporteSucursalId) ? $reporteSucursalId : null,
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
                        'reporte_modo_sucursal' => $reporteModoSucursal,
                        'reporte_sucursal_id' => ($reporteModoSucursal === 'specific' && $reporteSucursalId) ? $reporteSucursalId : null,
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
