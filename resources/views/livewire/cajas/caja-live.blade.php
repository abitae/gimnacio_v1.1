<div class="space-y-4 border border-zinc-200 rounded-lg p-4">
    <div class="flex h-full w-full flex-1 flex-col gap-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Gestión de Caja</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Administra la apertura y cierre de cajas</p>
            </div>
            <div>
                @if ($this->cajasAbiertas->count() > 0)
                    @php
                        $cajaAbierta = $this->cajasAbiertas->first();
                    @endphp
                    @can('cajas.update')
                        <flux:button icon="x-mark" color="red" variant="primary" size="sm"
                            wire:click="abrirModalCierre({{ $cajaAbierta->id }})" wire:loading.attr="disabled"
                            wire:target="abrirModalCierre" aria-label="Cerrar caja">
                            <span wire:loading.remove wire:target="abrirModalCierre">Cerrar Caja</span>
                            <span wire:loading wire:target="abrirModalCierre">Cargando...</span>
                        </flux:button>
                    @endcan
                @else
                    @can('cajas.create')
                        <flux:button icon="plus" color="purple" variant="primary" size="sm"
                            wire:click="abrirModalApertura" wire:loading.attr="disabled" wire:target="abrirModalApertura"
                            aria-label="Abrir nueva caja">
                            <span wire:loading.remove wire:target="abrirModalApertura">Abrir Caja</span>
                            <span wire:loading wire:target="abrirModalApertura">Cargando...</span>
                        </flux:button>
                    @endcan
                @endif
                <flux:button size="xs" variant="outline" icon="clock" wire:click="abrirModalHistorial"
                    aria-label="Ver historial de cajas">Ver historial</flux:button>
            </div>
        </div>

        <!-- Flash Messages -->
        <div>
        </div>

        <!-- Caja Abierta: estadísticas compactas -->
        @if ($this->cajasAbiertas->count() > 0)
            @php
                $cajaAbierta = $this->cajasAbiertas->first();
                $saldoActual =
                    $cajaAbierta->saldo_inicial +
                    $cajaAbierta->calcularTotalIngresos() -
                    $cajaAbierta->calcularTotalSalidas();
                $totalIngresos = $cajaAbierta->calcularTotalIngresos();
                $totalSalidas = $cajaAbierta->calcularTotalSalidas();
                $desgloseMetodos = $cajaAbierta->calcularTotalPorMetodoPago();
            @endphp
            <div
                class="rounded-lg border border-green-200 dark:border-green-800 bg-green-50/50 dark:bg-green-900/10 p-3">
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-green-500 px-2 py-0.5 text-[10px] font-semibold text-white">Caja
                            #{{ $cajaAbierta->id }}</span>
                        <span
                            class="text-[10px] text-zinc-500 dark:text-zinc-400">{{ $cajaAbierta->fecha_apertura->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex gap-1">
                        @can('cajas.update')
                            <flux:button size="xs" variant="ghost" color="red" icon="x-mark"
                                wire:click="abrirModalCierre({{ $cajaAbierta->id }})">Cerrar</flux:button>
                        @endcan
                    </div>
                </div>
                <div class="grid grid-cols-4 gap-2 text-center">
                    <div
                        class="rounded bg-white dark:bg-zinc-800/80 px-2 py-1.5 border border-zinc-200 dark:border-zinc-700">
                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400">Inicial</div>
                        <div class="text-xs font-bold text-zinc-900 dark:text-zinc-100">S/
                            {{ number_format($cajaAbierta->saldo_inicial, 2) }}</div>
                    </div>
                    <div
                        class="rounded bg-green-100 dark:bg-green-900/30 px-2 py-1.5 border border-green-200 dark:border-green-800">
                        <div class="text-[10px] text-green-600 dark:text-green-400">Ingresos</div>
                        <div class="text-xs font-bold text-green-700 dark:text-green-300">S/
                            {{ number_format($totalIngresos, 2) }}</div>
                    </div>
                    <div
                        class="rounded bg-red-100 dark:bg-red-900/30 px-2 py-1.5 border border-red-200 dark:border-red-800">
                        <div class="text-[10px] text-red-600 dark:text-red-400">Salidas</div>
                        <div class="text-xs font-bold text-red-700 dark:text-red-300">S/
                            {{ number_format($totalSalidas, 2) }}</div>
                    </div>
                    <div
                        class="rounded bg-purple-100 dark:bg-purple-900/30 px-2 py-1.5 border border-purple-200 dark:border-purple-800">
                        <div class="text-[10px] text-purple-600 dark:text-purple-400">Actual</div>
                        <div class="text-xs font-bold text-purple-700 dark:text-purple-300">S/
                            {{ number_format($saldoActual, 2) }}</div>
                    </div>
                </div>
                @if (!empty($desgloseMetodos))
                    <div
                        class="mt-2 pt-2 border-t border-green-200 dark:border-green-800 flex flex-wrap gap-x-3 gap-y-0.5 text-[10px]">
                        @foreach ($desgloseMetodos as $metodo => $datos)
                            <span class="text-zinc-600 dark:text-zinc-400 capitalize">{{ $metodo }}: <strong
                                    class="text-zinc-900 dark:text-zinc-100">S/
                                    {{ number_format($datos['total'], 2) }}</strong> ({{ $datos['cantidad'] }})</span>
                        @endforeach
                    </div>
                @endif
            </div>
        @else
            <div
                class="rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 text-center">
                <p class="text-sm text-zinc-600 dark:text-zinc-400">No tienes una caja abierta. Abre una caja para
                    comenzar.</p>
            </div>
        @endif

        <!-- Filtros compactos + botón historial -->
        <div class="flex flex-wrap items-end gap-2">
            <div class="flex flex-wrap items-center gap-2">
                <div class="w-32">
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Desde</label>
                    <input type="date" wire:model.live="fechaDesde"
                        class="w-full rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1 text-xs dark:text-zinc-100">
                </div>
                <div class="w-32">
                    <label class="block text-[10px] font-medium text-zinc-500 dark:text-zinc-400">Hasta</label>
                    <input type="date" wire:model.live="fechaHasta"
                        class="w-full rounded border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1 text-xs dark:text-zinc-100">
                </div>

            </div>
        </div>

        <!-- Entradas y Salidas en 2 columnas (solo si hay caja abierta) -->
        @if ($this->cajasAbiertas->count() > 0)
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3">
                <!-- Entradas -->
                <div
                    class="rounded-lg border border-green-200 dark:border-green-800 bg-white dark:bg-zinc-800 overflow-hidden">
                    <div
                        class="px-2 py-1.5 bg-green-50 dark:bg-green-900/20 border-b border-green-200 dark:border-green-800 flex items-center gap-1">
                        <flux:icon name="arrow-down-circle" class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                        <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Entradas</span>
                        <span class="text-[10px] text-zinc-500">({{ $entradasCajaAbierta->count() }})</span>
                    </div>
                    <div class="overflow-auto max-h-64">
                        <table class="w-full text-[11px]">
                            <thead class="bg-zinc-50 dark:bg-zinc-900 sticky top-0">
                                <tr>
                                    <th class="px-1.5 py-1 text-left font-medium text-zinc-600 dark:text-zinc-400">Fecha
                                    </th>
                                    <th class="px-1.5 py-1 text-left font-medium text-zinc-600 dark:text-zinc-400">
                                        Concepto</th>
                                    <th class="px-1.5 py-1 text-right font-medium text-zinc-600 dark:text-zinc-400">
                                        Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @forelse ($entradasCajaAbierta as $m)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                        <td class="px-1.5 py-1 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                            {{ $m->fecha->format('d/m H:i') }}</td>
                                        <td class="px-1.5 py-1 truncate max-w-[120px]" title="{{ $m->concepto }}">
                                            {{ $m->concepto }}</td>
                                        <td
                                            class="px-1.5 py-1 text-right font-medium text-green-600 dark:text-green-400">
                                            + S/ {{ number_format($m->monto, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-2 py-4 text-center text-zinc-400 text-xs">Sin
                                            entradas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Salidas -->
                <div
                    class="rounded-lg border border-red-200 dark:border-red-800 bg-white dark:bg-zinc-800 overflow-hidden">
                    <div
                        class="px-2 py-1.5 bg-red-50 dark:bg-red-900/20 border-b border-red-200 dark:border-red-800 flex items-center gap-1">
                        <flux:icon name="arrow-up-circle" class="h-3.5 w-3.5 text-red-600 dark:text-red-400" />
                        <span class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Salidas</span>
                        <span class="text-[10px] text-zinc-500">({{ $salidasCajaAbierta->count() }})</span>
                    </div>
                    <div class="overflow-auto max-h-64">
                        <table class="w-full text-[11px]">
                            <thead class="bg-zinc-50 dark:bg-zinc-900 sticky top-0">
                                <tr>
                                    <th class="px-1.5 py-1 text-left font-medium text-zinc-600 dark:text-zinc-400">
                                        Fecha</th>
                                    <th class="px-1.5 py-1 text-left font-medium text-zinc-600 dark:text-zinc-400">
                                        Concepto</th>
                                    <th class="px-1.5 py-1 text-right font-medium text-zinc-600 dark:text-zinc-400">
                                        Monto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @forelse ($salidasCajaAbierta as $m)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                        <td class="px-1.5 py-1 whitespace-nowrap text-zinc-600 dark:text-zinc-400">
                                            {{ $m->fecha_movimiento->format('d/m H:i') }}</td>
                                        <td class="px-1.5 py-1 truncate max-w-[120px]" title="{{ $m->concepto }}">
                                            {{ $m->concepto }}</td>
                                        <td class="px-1.5 py-1 text-right font-medium text-red-600 dark:text-red-400">-
                                            S/ {{ number_format($m->monto, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-2 py-4 text-center text-zinc-400 text-xs">Sin
                                            salidas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @else
            <p class="text-xs text-zinc-500 dark:text-zinc-400 py-2">Abre una caja para ver entradas y salidas aquí.
            </p>
        @endif
    </div>

    <!-- Modal Historial de Cajas -->
    <flux:modal name="historial-modal" wire:model="mostrarModalHistorial" focusable flyout variant="floating"
        class="md:w-5xl">
        <div class="p-4">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Historial de cajas</h2>
                <flux:button variant="ghost" size="xs" wire:click="cerrarModalHistorial" icon="x-mark">Cerrar
                </flux:button>
            </div>
            <div class="flex flex-wrap gap-2 mb-3">
                <label class="text-[10px] text-zinc-500">Desde</label>
                <input type="date" wire:model.live="fechaDesde"
                    class="rounded border border-zinc-300 dark:border-zinc-600 px-2 py-1 text-xs dark:bg-zinc-800 dark:text-zinc-100 w-32">
                <label class="text-[10px] text-zinc-500 ml-2">Hasta</label>
                <input type="date" wire:model.live="fechaHasta"
                    class="rounded border border-zinc-300 dark:border-zinc-600 px-2 py-1 text-xs dark:bg-zinc-800 dark:text-zinc-100 w-32">
                <select wire:model.live="perPage"
                    class="rounded border border-zinc-300 dark:border-zinc-600 px-2 py-1 text-xs dark:bg-zinc-800 dark:text-zinc-100 w-24">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                </select>
            </div>
            <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                <table class="w-full text-xs">
                    <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                        <tr>
                            <th class="px-2 py-1.5 text-left font-semibold text-zinc-700 dark:text-zinc-300">ID</th>
                            <th class="px-2 py-1.5 text-left font-semibold text-zinc-700 dark:text-zinc-300">Usuario
                            </th>
                            <th class="px-2 py-1.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Inicial
                            </th>
                            <th class="px-2 py-1.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Ingresos
                            </th>
                            <th class="px-2 py-1.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Salidas
                            </th>
                            <th class="px-2 py-1.5 text-right font-semibold text-zinc-700 dark:text-zinc-300">Final
                            </th>
                            <th class="px-2 py-1.5 text-left font-semibold text-zinc-700 dark:text-zinc-300">Estado
                            </th>
                            <th class="px-2 py-1.5 text-left font-semibold text-zinc-700 dark:text-zinc-300">Fechas
                            </th>
                            <th class="px-2 py-1.5 text-center font-semibold text-zinc-700 dark:text-zinc-300">Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($cajas as $caja)
                            @php
                                $ingresos = $caja->calcularTotalIngresos();
                                $salidas = $caja->calcularTotalSalidas();
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-2 py-1.5 font-medium">#{{ $caja->id }}</td>
                                <td class="px-2 py-1.5 truncate max-w-[100px]">{{ $caja->usuario->name }}</td>
                                <td class="px-2 py-1.5 text-right">S/ {{ number_format($caja->saldo_inicial, 2) }}
                                </td>
                                <td class="px-2 py-1.5 text-right text-green-600 dark:text-green-400">S/
                                    {{ number_format($ingresos, 2) }}</td>
                                <td class="px-2 py-1.5 text-right text-red-600 dark:text-red-400">S/
                                    {{ number_format($salidas, 2) }}</td>
                                <td class="px-2 py-1.5 text-right">
                                    @if ($caja->saldo_final)
                                        S/ {{ number_format($caja->saldo_final, 2) }}
                                    @else
                                        <span class="text-purple-600 dark:text-purple-400">S/
                                            {{ number_format($caja->saldo_inicial + $ingresos - $salidas, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5">
                                    <span
                                        class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $caja->estado === 'abierta' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ ucfirst($caja->estado) }}</span>
                                </td>
                                <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">
                                    {{ $caja->fecha_apertura->format('d/m/Y H:i') }}
                                    @if ($caja->fecha_cierre)
                                        <br><span class="text-[10px]">Cierre:
                                            {{ $caja->fecha_cierre->format('d/m H:i') }}</span>
                                    @endif
                                </td>
                                <td class="px-2 py-1.5">
                                    <div class="flex justify-center gap-0.5">
                                        <flux:button size="xs" variant="ghost" icon="document-text"
                                            wire:click="verReporte({{ $caja->id }})" title="Ver Reporte">
                                        </flux:button>
                                        @if ($caja->estado === 'abierta' && $caja->usuario_id === auth()->id())
                                            @can('cajas.update')
                                                <flux:button size="xs" variant="ghost" color="red"
                                                    icon="x-mark" wire:click="abrirModalCierre({{ $caja->id }})"
                                                    title="Cerrar Caja"></flux:button>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9"
                                    class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400 text-xs">No hay cajas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($cajas->hasPages())
                <div class="mt-4 flex justify-end">{{ $cajas->links() }}</div>
            @endif
        </div>
    </flux:modal>

    <!-- Modal Apertura -->
    <flux:modal name="apertura-modal" wire:model="mostrarModalApertura" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit.prevent="abrirCaja">
            <div class="space-y-4 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Abrir Nueva Caja</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Registra el saldo inicial de la caja</p>
                </div>

                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Saldo Inicial (S/)</flux:label>
                        <flux:input type="number" step="0.01" wire:model="formApertura.saldo_inicial"
                            placeholder="0.00" />
                        <flux:error name="formApertura.saldo_inicial" />
                        <flux:description>Ingresa el monto inicial con el que abres la caja</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>Observaciones</flux:label>
                        <flux:textarea wire:model="formApertura.observaciones_apertura" rows="3"
                            placeholder="Observaciones opcionales..." />
                        <flux:error name="formApertura.observaciones_apertura" />
                    </flux:field>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="ghost" wire:click="cerrarModalApertura" wire:loading.attr="disabled">
                        Cancelar</flux:button>
                    <flux:button type="submit" wire:loading.attr="disabled" wire:target="abrirCaja">
                        <span class="inline-flex items-center gap-1.5">
                            <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading
                                wire:target="abrirCaja" />
                            <span wire:loading.remove wire:target="abrirCaja">Abrir Caja</span>
                            <span wire:loading wire:target="abrirCaja">Abriendo...</span>
                        </span>
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Cierre -->
    <flux:modal name="cierre-modal" wire:model="mostrarModalCierre" focusable flyout variant="floating"
        class="md:w-3xl">
        @if ($cajaSeleccionada && $reporteCierre)
            <div class="space-y-4 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Cerrar Caja
                        #{{ $cajaSeleccionada->id }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Revisa el reporte antes de cerrar la caja
                    </p>
                </div>

                <!-- Reporte de Cierre -->
                <div
                    class="rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 space-y-4">
                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div
                            class="rounded-lg bg-white dark:bg-zinc-800 p-3 border border-zinc-200 dark:border-zinc-700">
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Saldo Inicial</div>
                            <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">S/
                                {{ number_format($reporteCierre['saldo_inicial'], 2) }}</div>
                        </div>
                        <div
                            class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
                            <div class="text-xs text-green-600 dark:text-green-400 mb-1">Total Ingresos</div>
                            <div class="text-lg font-bold text-green-700 dark:text-green-300">S/
                                {{ number_format($reporteCierre['total_ingresos'], 2) }}</div>
                        </div>
                        <div
                            class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
                            <div class="text-xs text-red-600 dark:text-red-400 mb-1">Total Salidas</div>
                            <div class="text-lg font-bold text-red-700 dark:text-red-300">S/
                                {{ number_format($reporteCierre['total_salidas'] ?? 0, 2) }}</div>
                        </div>
                        <div
                            class="rounded-lg bg-purple-50 dark:bg-purple-900/20 p-3 border border-purple-200 dark:border-purple-800">
                            <div class="text-xs text-purple-600 dark:text-purple-400 mb-1">Saldo Final Esperado</div>
                            <div class="text-lg font-bold text-purple-700 dark:text-purple-300">S/
                                {{ number_format($reporteCierre['saldo_final_esperado'], 2) }}</div>
                        </div>
                        <div
                            class="rounded-lg bg-blue-50 dark:bg-blue-900/20 p-3 border border-blue-200 dark:border-blue-800">
                            <div class="text-xs text-blue-600 dark:text-blue-400 mb-1">Transacciones</div>
                            <div class="text-lg font-bold text-blue-700 dark:text-blue-300">
                                {{ $reporteCierre['cantidad_transacciones'] }}</div>
                        </div>
                    </div>

                    <!-- Desglose por Método de Pago -->
                    @if (!empty($reporteCierre['desglose_por_metodo']))
                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Desglose por Método
                                de Pago</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                @foreach ($reporteCierre['desglose_por_metodo'] as $metodo => $datos)
                                    <div
                                        class="flex justify-between items-center p-2 rounded bg-white dark:bg-zinc-800 text-sm">
                                        <span
                                            class="text-zinc-600 dark:text-zinc-400 capitalize">{{ $metodo }}</span>
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                            S/ {{ number_format($datos['total'], 2) }} <span
                                                class="text-xs text-zinc-500">({{ $datos['cantidad'] }})</span>
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <form wire:submit.prevent="cerrarCaja">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Observaciones de Cierre</flux:label>
                            <flux:textarea wire:model="formCierre.observaciones_cierre" rows="3"
                                placeholder="Observaciones opcionales..." />
                            <flux:error name="formCierre.observaciones_cierre" />
                        </flux:field>

                        <div class="flex justify-end gap-2 pt-2">
                            <flux:button variant="ghost" wire:click="cerrarModalCierre" wire:loading.attr="disabled">
                                Cancelar</flux:button>
                            <flux:button type="submit" color="red" wire:loading.attr="disabled"
                                wire:target="cerrarCaja">
                                <span class="inline-flex items-center gap-1.5">
                                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading
                                        wire:target="cerrarCaja" />
                                    <span wire:loading.remove wire:target="cerrarCaja">Cerrar Caja</span>
                                    <span wire:loading wire:target="cerrarCaja">Cerrando...</span>
                                </span>
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </flux:modal>

    <!-- Modal Reporte -->
    <flux:modal name="reporte-modal" wire:model="mostrarModalReporte" focusable flyout variant="floating"
        class="md:w-4xl">
        @if ($reporteCierre && $cajaSeleccionada)
            <div class="space-y-4 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Reporte de Cierre - Caja
                        #{{ $cajaSeleccionada->id }}</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Información detallada de la caja</p>
                </div>

                <div
                    class="rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-5 space-y-5">
                    <!-- Información General -->
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Información General
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                            <div>
                                <span class="text-zinc-600 dark:text-zinc-400">Usuario:</span>
                                <span
                                    class="ml-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $reporteCierre['usuario']->name }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-600 dark:text-zinc-400">Fecha Apertura:</span>
                                <span
                                    class="ml-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $reporteCierre['fecha_apertura']->format('d/m/Y H:i') }}</span>
                            </div>
                            @if ($reporteCierre['fecha_cierre'])
                                <div>
                                    <span class="text-zinc-600 dark:text-zinc-400">Fecha Cierre:</span>
                                    <span
                                        class="ml-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $reporteCierre['fecha_cierre']->format('d/m/Y H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Resumen Financiero -->
                    <div>
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Resumen Financiero</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div
                                class="rounded-lg bg-white dark:bg-zinc-800 p-3 border border-zinc-200 dark:border-zinc-700">
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">Saldo Inicial</div>
                                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">S/
                                    {{ number_format($reporteCierre['saldo_inicial'], 2) }}</div>
                            </div>
                            <div
                                class="rounded-lg bg-green-50 dark:bg-green-900/20 p-3 border border-green-200 dark:border-green-800">
                                <div class="text-xs text-green-600 dark:text-green-400 mb-1">Total Ingresos</div>
                                <div class="text-lg font-bold text-green-700 dark:text-green-300">S/
                                    {{ number_format($reporteCierre['total_ingresos'], 2) }}</div>
                            </div>
                            <div
                                class="rounded-lg bg-red-50 dark:bg-red-900/20 p-3 border border-red-200 dark:border-red-800">
                                <div class="text-xs text-red-600 dark:text-red-400 mb-1">Total Salidas</div>
                                <div class="text-lg font-bold text-red-700 dark:text-red-300">S/
                                    {{ number_format($reporteCierre['total_salidas'] ?? 0, 2) }}</div>
                            </div>
                            <div
                                class="rounded-lg bg-purple-50 dark:bg-purple-900/20 p-3 border border-purple-200 dark:border-purple-800">
                                <div class="text-xs text-purple-600 dark:text-purple-400 mb-1">Saldo Final</div>
                                <div class="text-lg font-bold text-purple-700 dark:text-purple-300">
                                    @if ($reporteCierre['saldo_final'])
                                        S/ {{ number_format($reporteCierre['saldo_final'], 2) }}
                                    @else
                                        S/ {{ number_format($reporteCierre['saldo_final_esperado'], 2) }}
                                    @endif
                                </div>
                            </div>
                        </div>
                        @if (isset($reporteCierre['diferencia']) && $reporteCierre['diferencia'] != 0)
                            <div
                                class="mt-3 p-3 rounded-lg {{ $reporteCierre['diferencia'] > 0 ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' }}">
                                <div
                                    class="text-sm font-semibold {{ $reporteCierre['diferencia'] > 0 ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                                    Diferencia: S/ {{ number_format($reporteCierre['diferencia'], 2) }}
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Desglose por Método de Pago -->
                    @if (!empty($reporteCierre['desglose_por_metodo']))
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Desglose por Método
                                de Pago</h3>
                            <div class="space-y-2">
                                @foreach ($reporteCierre['desglose_por_metodo'] as $metodo => $datos)
                                    <div
                                        class="flex justify-between items-center p-3 rounded bg-white dark:bg-zinc-800 text-sm">
                                        <div>
                                            <span
                                                class="font-medium text-zinc-900 dark:text-zinc-100 capitalize">{{ $metodo }}</span>
                                            <span
                                                class="ml-2 text-zinc-600 dark:text-zinc-400">({{ $datos['cantidad'] }}
                                                transacciones)</span>
                                        </div>
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">S/
                                            {{ number_format($datos['total'], 2) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Observaciones -->
                    @if ($reporteCierre['observaciones_apertura'] || $reporteCierre['observaciones_cierre'])
                        <div>
                            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Observaciones</h3>
                            <div class="space-y-3 text-sm">
                                @if ($reporteCierre['observaciones_apertura'])
                                    <div class="p-3 rounded bg-white dark:bg-zinc-800">
                                        <span class="font-medium text-zinc-600 dark:text-zinc-400">Apertura:</span>
                                        <p class="mt-1 text-zinc-900 dark:text-zinc-100">
                                            {{ $reporteCierre['observaciones_apertura'] }}</p>
                                    </div>
                                @endif
                                @if ($reporteCierre['observaciones_cierre'])
                                    <div class="p-3 rounded bg-white dark:bg-zinc-800">
                                        <span class="font-medium text-zinc-600 dark:text-zinc-400">Cierre:</span>
                                        <p class="mt-1 text-zinc-900 dark:text-zinc-100">
                                            {{ $reporteCierre['observaciones_cierre'] }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button variant="ghost" wire:click="cerrarModalReporte">Cerrar</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>

    <!-- Modal Detalle de Venta -->
    <flux:modal name="detalle-venta-modal" wire:model="mostrarModalDetalleVenta" focusable flyout variant="floating"
        class="md:w-4xl">
        @if ($ventaDetalle)
            <div class="space-y-6 p-6">
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">Detalle de Venta</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Productos y servicios vendidos</p>
                </div>

                <!-- Información de la Venta -->
                <div
                    class="rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 p-4 space-y-3">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Número de Venta:</span>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $ventaDetalle->numero_venta }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Comprobante:</span>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ strtoupper($ventaDetalle->tipo_comprobante) }}
                                {{ $ventaDetalle->serie_comprobante }}-{{ $ventaDetalle->numero_comprobante }}
                            </div>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Método de Pago:</span>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100 capitalize">
                                {{ $ventaDetalle->metodo_pago }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Fecha:</span>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $ventaDetalle->fecha_venta->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                    @if ($ventaDetalle->cliente)
                        <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">Cliente:</span>
                            <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $ventaDetalle->cliente->nombres }} {{ $ventaDetalle->cliente->apellidos }}
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Items de la Venta -->
                <div class="space-y-3">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">Productos y Servicios</h3>
                    <div
                        class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Tipo
                                    </th>
                                    <th class="px-4 py-3 text-left font-semibold text-zinc-700 dark:text-zinc-300">Item
                                    </th>
                                    <th class="px-4 py-3 text-center font-semibold text-zinc-700 dark:text-zinc-300">
                                        Cantidad</th>
                                    <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                        Precio Unit.</th>
                                    <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                        Descuento</th>
                                    <th class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                        Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach ($ventaDetalle->items as $item)
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors">
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                                @if ($item->tipo_item === 'producto') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400
                                                @elseif ($item->tipo_item === 'servicio') bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400
                                                @else bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400 @endif">
                                                {{ ucfirst($item->tipo_item) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                                {{ $item->nombre_item }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span
                                                class="text-zinc-900 dark:text-zinc-100">{{ $item->cantidad }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-zinc-900 dark:text-zinc-100">S/
                                                {{ number_format($item->precio_unitario, 2) }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="text-zinc-500 dark:text-zinc-400">
                                                @if ($item->descuento > 0)
                                                    S/ {{ number_format($item->descuento, 2) }}
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">S/
                                                {{ number_format($item->subtotal, 2) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-zinc-50 dark:bg-zinc-900 border-t-2 border-zinc-200 dark:border-zinc-700">
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                        Subtotal:</td>
                                    <td class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                        S/ {{ number_format($ventaDetalle->subtotal, 2) }}
                                    </td>
                                </tr>
                                @if ($ventaDetalle->descuento > 0)
                                    <tr>
                                        <td colspan="5"
                                            class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">
                                            Descuento:</td>
                                        <td class="px-4 py-3 text-right font-semibold text-red-600 dark:text-red-400">
                                            - S/ {{ number_format($ventaDetalle->descuento, 2) }}
                                        </td>
                                    </tr>
                                @endif
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-3 text-right font-semibold text-zinc-700 dark:text-zinc-300">IGV
                                        (18% incluido):</td>
                                    <td class="px-4 py-3 text-right font-semibold text-zinc-900 dark:text-zinc-100">
                                        S/ {{ number_format($ventaDetalle->igv, 2) }}
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="5"
                                        class="px-4 py-3 text-right font-bold text-lg text-zinc-900 dark:text-zinc-100">
                                        Total:</td>
                                    <td
                                        class="px-4 py-3 text-right font-bold text-lg text-purple-600 dark:text-purple-400">
                                        S/ {{ number_format($ventaDetalle->total, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="cerrarModalDetalleVenta">Cerrar</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
