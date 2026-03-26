<div class="space-y-6">
    <section class="rounded-3xl border border-zinc-200 bg-gradient-to-br from-white via-zinc-50 to-emerald-50/60 p-6 shadow-sm dark:border-zinc-800 dark:from-zinc-900 dark:via-zinc-900 dark:to-emerald-950/20">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-emerald-600 dark:text-emerald-400">Caja operativa</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-zinc-950 dark:text-zinc-50">Control de caja</h1>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Una caja abierta por usuario. Movimientos por categoría.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('cajas.create')
                    @if (!$cajaActiva)
                        <flux:button icon="plus" variant="primary" wire:click="abrirModalApertura">Abrir caja</flux:button>
                    @endif
                @endcan
                @can('cajas.movimientos-manuales')
                    <flux:button icon="arrow-down-circle" variant="outline" wire:click="abrirModalIngresoManual" :disabled="!$cajaActiva">Ingreso manual</flux:button>
                    <flux:button icon="arrow-up-circle" variant="outline" wire:click="abrirModalSalidaManual" :disabled="!$cajaActiva">Salida manual</flux:button>
                @elsecan('cajas.update')
                    @if ($cajaActiva && $cajaActiva->usuario_id === auth()->id())
                        <flux:button icon="arrow-down-circle" variant="outline" wire:click="abrirModalIngresoManual">Ingreso manual</flux:button>
                        <flux:button icon="arrow-up-circle" variant="outline" wire:click="abrirModalSalidaManual">Salida manual</flux:button>
                    @endif
                @endcan
                @if ($cajaActiva && $cajaActiva->estado === 'abierta' && (int) $cajaActiva->usuario_id === (int) auth()->id())
                    @can('cajas.update')
                        <flux:button icon="lock-closed" color="red" variant="primary" wire:click="abrirModalCierre({{ $cajaActiva->id }})">Cerrar caja</flux:button>
                    @endcan
                @endif
                <flux:button icon="clock" variant="ghost" wire:click="abrirModalHistorial">Historial</flux:button>
            </div>
        </div>

        <div class="mt-6 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/70">
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Caja activa</p>
                @if ($cajaActiva)
                    <p class="mt-2 text-xl font-semibold text-zinc-950 dark:text-zinc-50">#{{ $cajaActiva->id }}</p>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $cajaActiva->usuario->name }}</p>
                @else
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Sin caja seleccionada</p>
                @endif
            </div>
            <div class="rounded-2xl border border-zinc-200 bg-white/80 p-4 dark:border-zinc-800 dark:bg-zinc-900/70">
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Saldo inicial</p>
                <p class="mt-2 text-xl font-semibold text-zinc-950 dark:text-zinc-50">S/ {{ number_format($resumenCaja['saldo_inicial'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Ingresos</p>
                <p class="mt-2 text-xl font-semibold text-emerald-800 dark:text-emerald-300">S/ {{ number_format($resumenCaja['total_ingresos'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-rose-200 bg-rose-50/80 p-4 dark:border-rose-900 dark:bg-rose-950/30">
                <p class="text-xs uppercase tracking-wide text-rose-700 dark:text-rose-400">Salidas</p>
                <p class="mt-2 text-xl font-semibold text-rose-800 dark:text-rose-300">S/ {{ number_format($resumenCaja['total_salidas'] ?? 0, 2) }}</p>
            </div>
            <div class="rounded-2xl border border-sky-200 bg-sky-50/80 p-4 dark:border-sky-900 dark:bg-sky-950/30">
                <p class="text-xs uppercase tracking-wide text-sky-700 dark:text-sky-400">Saldo actual</p>
                <p class="mt-2 text-xl font-semibold text-sky-800 dark:text-sky-300">S/ {{ number_format($resumenCaja['saldo_actual'] ?? 0, 2) }}</p>
            </div>
        </div>
    </section>

    <section class="grid gap-6 lg:grid-cols-2">
        {{-- Card ENTRADAS: solo movimientos tipo entrada, pestañas por categoría --}}
        <div class="flex flex-col rounded-3xl border border-emerald-200/80 bg-white p-5 shadow-sm dark:border-emerald-900/50 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-emerald-100 pb-4 dark:border-emerald-950/50">
                <div>
                    <h2 class="text-lg font-semibold text-emerald-900 dark:text-emerald-100">Entradas</h2>
                    <p class="text-sm text-emerald-800/80 dark:text-emerald-300/80">S/ {{ number_format($resumenCaja['total_ingresos'] ?? 0, 2) }} total</p>
                </div>
            </div>
            @if ($cajaActiva && $entradasPorCategoria->isNotEmpty())
                <div class="mt-3 flex gap-1 overflow-x-auto border-b border-zinc-100 pb-2 dark:border-zinc-800 scrollbar-thin">
                    @foreach ($entradasPorCategoria as $cat => $items)
                        <button type="button" wire:click="setTabEntrada('{{ $cat }}')"
                            class="shrink-0 rounded-xl px-3 py-2 text-xs font-medium transition {{ $tabEntradaActiva === $cat ? 'bg-emerald-600 text-white dark:bg-emerald-700' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200' }}">
                            {{ $labelCategoria($cat) }}
                            <span class="opacity-80">({{ $items->count() }})</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-3 max-h-[28rem] flex-1 overflow-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-emerald-50/95 dark:bg-emerald-950/40">
                            <tr class="text-left text-xs uppercase tracking-wide text-emerald-800 dark:text-emerald-300">
                                <th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2">Concepto</th>
                                <th class="px-3 py-2">Método</th>
                                <th class="px-3 py-2 text-right">Monto</th>
                                <th class="px-3 py-2">Ref.</th>
                                <th class="px-3 py-2 text-right">Ticket</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($entradasPorCategoria->get($tabEntradaActiva, collect()) as $movimiento)
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $movimiento['fecha']->format('d/m H:i') }}</td>
                                    <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">{{ $movimiento['concepto'] }}</td>
                                    <td class="px-3 py-2 text-zinc-500">{{ $movimiento['metodo_pago'] ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-emerald-700 dark:text-emerald-400">+ S/ {{ number_format($movimiento['monto'], 2) }}</td>
                                    <td class="px-3 py-2">
                                        @if (($movimiento['referencia_tipo'] ?? '') === 'App\\Models\\Core\\Venta')
                                            <button type="button" class="text-sky-600 underline-offset-2 hover:underline dark:text-sky-400" wire:click="verDetalleVenta({{ $movimiento['referencia_id'] }})">{{ $movimiento['referencia_label'] }}</button>
                                        @else
                                            <span class="text-zinc-500">{{ $movimiento['referencia_label'] ?: '—' }}</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        @if (! empty($movimiento['ticket_pago_id']))
                                            <button type="button" class="text-xs text-sky-600 underline-offset-2 hover:underline dark:text-sky-400" wire:click="abrirTicketPagoCaja({{ $movimiento['ticket_pago_id'] }})">Reimprimir</button>
                                        @else
                                            <span class="text-zinc-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (!$cajaActiva)
                <p class="mt-6 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">Abre tu caja para ver entradas.</p>
            @else
                <p class="mt-6 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">Sin entradas en esta sesión.</p>
            @endif
        </div>

        {{-- Card SALIDAS --}}
        <div class="flex flex-col rounded-3xl border border-rose-200/80 bg-white p-5 shadow-sm dark:border-rose-900/50 dark:bg-zinc-900">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-rose-100 pb-4 dark:border-rose-950/50">
                <div>
                    <h2 class="text-lg font-semibold text-rose-900 dark:text-rose-100">Salidas</h2>
                    <p class="text-sm text-rose-800/80 dark:text-rose-300/80">S/ {{ number_format($resumenCaja['total_salidas'] ?? 0, 2) }} total</p>
                </div>
            </div>
            @if ($cajaActiva && $salidasPorCategoria->isNotEmpty())
                <div class="mt-3 flex gap-1 overflow-x-auto border-b border-zinc-100 pb-2 dark:border-zinc-800">
                    @foreach ($salidasPorCategoria as $cat => $items)
                        <button type="button" wire:click="setTabSalida('{{ $cat }}')"
                            class="shrink-0 rounded-xl px-3 py-2 text-xs font-medium transition {{ $tabSalidaActiva === $cat ? 'bg-rose-600 text-white dark:bg-rose-700' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-200' }}">
                            {{ $labelCategoria($cat) }}
                            <span class="opacity-80">({{ $items->count() }})</span>
                        </button>
                    @endforeach
                </div>
                <div class="mt-3 max-h-[28rem] flex-1 overflow-auto rounded-2xl border border-zinc-200 dark:border-zinc-800">
                    <table class="min-w-full text-sm">
                        <thead class="sticky top-0 bg-rose-50/95 dark:bg-rose-950/40">
                            <tr class="text-left text-xs uppercase tracking-wide text-rose-800 dark:text-rose-300">
                                <th class="px-3 py-2">Fecha</th>
                                <th class="px-3 py-2">Concepto</th>
                                <th class="px-3 py-2">Método</th>
                                <th class="px-3 py-2 text-right">Monto</th>
                                <th class="px-3 py-2">Ref.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($salidasPorCategoria->get($tabSalidaActiva, collect()) as $movimiento)
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $movimiento['fecha']->format('d/m H:i') }}</td>
                                    <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">{{ $movimiento['concepto'] }}</td>
                                    <td class="px-3 py-2 text-zinc-500">{{ $movimiento['metodo_pago'] ?: '—' }}</td>
                                    <td class="px-3 py-2 text-right font-semibold text-rose-700 dark:text-rose-400">− S/ {{ number_format($movimiento['monto'], 2) }}</td>
                                    <td class="px-3 py-2 text-zinc-500">{{ $movimiento['referencia_label'] ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @elseif (!$cajaActiva)
                <p class="mt-6 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">Abre tu caja para ver salidas.</p>
            @else
                <p class="mt-6 rounded-2xl border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-600">Sin salidas en esta sesión.</p>
            @endif
        </div>
    </section>

    <flux:modal wire:model="mostrarModalApertura" class="md:w-xl">
        <div class="space-y-4 p-6">
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Abrir caja</h2>
            <input type="number" step="0.01" wire:model="formApertura.saldo_inicial" placeholder="Saldo inicial" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <textarea wire:model="formApertura.observaciones_apertura" rows="3" placeholder="Observaciones" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cerrarModalApertura">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="abrirCaja">Abrir caja</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalIngresoManual" class="md:w-xl">
        <div class="space-y-4 p-6">
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Ingreso manual</h2>
            @if ($cajaActiva)
                <p class="text-sm text-zinc-500">Caja #{{ $cajaActiva->id }} (tu sesión actual)</p>
            @endif
            <input type="hidden" wire:model="formIngresoManual.caja_id">
            <input type="number" step="0.01" wire:model="formIngresoManual.monto" placeholder="Monto" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <input type="text" wire:model="formIngresoManual.concepto" placeholder="Concepto" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <textarea wire:model="formIngresoManual.observaciones" rows="3" placeholder="Observaciones" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('mostrarModalIngresoManual', false)">Cancelar</flux:button>
                <flux:button variant="primary" wire:click="registrarIngresoManual">Registrar ingreso</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalSalidaManual" class="md:w-xl">
        <div class="space-y-4 p-6">
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Salida manual</h2>
            @if ($cajaActiva)
                <p class="text-sm text-zinc-500">Caja #{{ $cajaActiva->id }} (tu sesión actual)</p>
            @endif
            <input type="hidden" wire:model="formSalidaManual.caja_id">
            <input type="number" step="0.01" wire:model="formSalidaManual.monto" placeholder="Monto" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <input type="text" wire:model="formSalidaManual.concepto" placeholder="Concepto" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
            <textarea wire:model="formSalidaManual.observaciones" rows="3" placeholder="Observaciones" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="$set('mostrarModalSalidaManual', false)">Cancelar</flux:button>
                <flux:button variant="primary" color="red" wire:click="registrarSalidaManual">Registrar salida</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalCierre" class="md:w-3xl">
        @if ($reporteCierre)
            <div class="space-y-4 p-6">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Cerrar caja #{{ $reporteCierre['caja']->id }}</h2>
                <div class="grid gap-3 md:grid-cols-4">
                    <div class="rounded-2xl border border-zinc-200 p-4 dark:border-zinc-800"><p class="text-xs text-zinc-500">Inicial</p><p class="mt-2 text-lg font-semibold">S/ {{ number_format($reporteCierre['saldo_inicial'], 2) }}</p></div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/20"><p class="text-xs text-emerald-600">Ingresos</p><p class="mt-2 text-lg font-semibold text-emerald-700 dark:text-emerald-300">S/ {{ number_format($reporteCierre['total_ingresos'], 2) }}</p></div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900 dark:bg-rose-950/20"><p class="text-xs text-rose-600">Salidas</p><p class="mt-2 text-lg font-semibold text-rose-700 dark:text-rose-300">S/ {{ number_format($reporteCierre['total_salidas'], 2) }}</p></div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4 dark:border-sky-900 dark:bg-sky-950/20"><p class="text-xs text-sky-600">Esperado</p><p class="mt-2 text-lg font-semibold text-sky-700 dark:text-sky-300">S/ {{ number_format($reporteCierre['saldo_final_esperado'], 2) }}</p></div>
                </div>
                <textarea wire:model="formCierre.observaciones_cierre" rows="3" placeholder="Observaciones de cierre" class="w-full rounded-xl border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"></textarea>
                <div class="flex justify-end gap-2">
                    <flux:button variant="ghost" wire:click="cerrarModalCierre">Cancelar</flux:button>
                    <flux:button variant="primary" color="red" wire:click="cerrarCaja">Confirmar cierre</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model="mostrarModalHistorial" class="md:w-6xl">
        <div class="space-y-4 p-6">
            <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Historial de cajas</h2>
            <div class="grid gap-3 md:grid-cols-3">
                <input type="date" wire:model.live="fechaDesde" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <input type="date" wire:model.live="fechaHasta" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100">
                <select wire:model.live="perPage" class="rounded-xl border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"><option value="10">10</option><option value="15">15</option><option value="20">20</option><option value="50">50</option></select>
            </div>
            <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                <table class="min-w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-950">
                        <tr class="text-left text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                            <th class="px-4 py-3">Caja</th><th class="px-4 py-3">Usuario</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Apertura</th><th class="px-4 py-3 text-right">Inicial</th><th class="px-4 py-3 text-right">Ingresos</th><th class="px-4 py-3 text-right">Salidas</th><th class="px-4 py-3 text-right">Final</th><th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($cajas as $caja)
                            <tr class="bg-white dark:bg-zinc-900">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">#{{ $caja->id }}</td>
                                <td class="px-4 py-3">{{ $caja->usuario->name }}</td>
                                <td class="px-4 py-3">{{ ucfirst($caja->estado) }}</td>
                                <td class="px-4 py-3">{{ $caja->fecha_apertura->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-right">S/ {{ number_format($caja->saldo_inicial, 2) }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700 dark:text-emerald-400">S/ {{ number_format($caja->calcularTotalIngresos(), 2) }}</td>
                                <td class="px-4 py-3 text-right text-rose-700 dark:text-rose-400">S/ {{ number_format($caja->calcularTotalSalidas(), 2) }}</td>
                                <td class="px-4 py-3 text-right font-semibold">S/ {{ number_format($caja->saldo_final ?: ($caja->saldo_inicial + $caja->calcularTotalIngresos() - $caja->calcularTotalSalidas()), 2) }}</td>
                                <td class="px-4 py-3 text-right"><flux:button size="xs" variant="ghost" wire:click="verReporte({{ $caja->id }})">Reporte</flux:button></td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay cajas para el rango seleccionado.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $cajas->links() }}
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalReporte" class="md:w-5xl">
        @if ($reporteCierre)
            <div class="space-y-5 p-6">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Reporte de caja #{{ $reporteCierre['caja']->id }}</h2>
                <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-950">
                            <tr class="text-left text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                <th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3">Concepto</th><th class="px-4 py-3">Usuario</th><th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($reporteCierre['movimientos'] as $movimiento)
                                <tr><td class="px-4 py-3">{{ $movimiento['fecha']->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ $movimiento['tipo_visual'] }}</td><td class="px-4 py-3">{{ $movimiento['concepto'] }}</td><td class="px-4 py-3">{{ $movimiento['usuario'] ?: '—' }}</td><td class="px-4 py-3 text-right">{{ $movimiento['tipo'] === 'entrada' ? '+' : '-' }} S/ {{ number_format($movimiento['monto'], 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model="mostrarModalDetalleVenta" class="md:w-4xl">
        @if ($ventaDetalle)
            <div class="space-y-5 p-6">
                <h2 class="text-lg font-semibold text-zinc-950 dark:text-zinc-50">Detalle de venta {{ $ventaDetalle->numero_venta }}</h2>
                <div class="overflow-hidden rounded-2xl border border-zinc-200 dark:border-zinc-800">
                    <table class="min-w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-950">
                            <tr class="text-left text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">
                                <th class="px-4 py-3">Item</th><th class="px-4 py-3">Tipo</th><th class="px-4 py-3 text-right">Cantidad</th><th class="px-4 py-3 text-right">P. unit</th><th class="px-4 py-3 text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @foreach ($ventaDetalle->items as $item)
                                <tr><td class="px-4 py-3">{{ $item->nombre_item }}</td><td class="px-4 py-3">{{ ucfirst($item->tipo_item) }}</td><td class="px-4 py-3 text-right">{{ $item->cantidad }}</td><td class="px-4 py-3 text-right">S/ {{ number_format($item->precio_unitario, 2) }}</td><td class="px-4 py-3 text-right">S/ {{ number_format($item->subtotal, 2) }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </flux:modal>

    <flux:modal wire:model="mostrarModalTicketPago" focusable class="md:max-w-4xl">
        <div class="flex flex-col p-4">
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ticket de cobro</h2>
                <div class="flex gap-2">
                    @if ($pagoIdTicketCaja)
                        <a href="{{ route('pagos.ticket.pdf', ['pago' => $pagoIdTicketCaja]) }}" target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                            Abrir en nueva pestaña
                        </a>
                    @endif
                    <flux:button variant="ghost" size="sm" type="button" wire:click="cerrarModalTicketPagoCaja">Cerrar</flux:button>
                </div>
            </div>
            @if ($pagoIdTicketCaja)
                <iframe
                    src="{{ route('pagos.ticket.pdf', ['pago' => $pagoIdTicketCaja]) }}"
                    class="w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800"
                    style="height: 75vh; min-height: 400px;"
                    title="Ticket PDF">
                </iframe>
            @endif
        </div>
    </flux:modal>
</div>
