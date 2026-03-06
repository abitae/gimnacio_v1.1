@php $tieneDeudaPoll = $selectedCliente && ((($saldoPendiente ?? 0) > 0) || (isset($selectedCliente->deuda_total) && $selectedCliente->deuda_total > 0)); @endphp
{{-- Poll fijo cada 5s. Sonido de deuda intermitente cada 1s, con o sin fullscreen. --}}
<div wire:poll.5s
    data-tiene-deuda="{{ $tieneDeudaPoll ? '1' : '0' }}"
    x-data="{
        fullscreen: false,
        soundEnabled: localStorage.getItem('dashboard_debt_sound') !== 'false',
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                this.$refs.dashboardPanel.requestFullscreen().then(() => { this.fullscreen = true; }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => { this.fullscreen = false; }).catch(() => {});
            }
        },
        toggleDebtSound() {
            localStorage.setItem('dashboard_debt_sound', this.soundEnabled);
        },
        playDebtBeep() {
            try {
                var ctx = new (window.AudioContext || window.webkitAudioContext)();
                var osc = ctx.createOscillator();
                var g = ctx.createGain();
                osc.connect(g);
                g.connect(ctx.destination);
                osc.frequency.value = 800;
                osc.type = 'sine';
                g.gain.setValueAtTime(0.25, ctx.currentTime);
                g.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.15);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.15);
            } catch (e) {}
        }
    }"
    x-init="setInterval(() => {
        var d = Alpine.$data($el);
        if (d && d.soundEnabled && $el.getAttribute('data-tiene-deuda') === '1') d.playDebtBeep();
    }, 1000)"
    x-ref="dashboardPanel"
    @fullscreenchange.window="fullscreen = !!document.fullscreenElement"
    :class="fullscreen ? 'space-y-6 bg-white min-h-screen p-4' : 'space-y-6'">
    {{-- Header mismo estilo que Checking --}}
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="home" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Dashboard</h1>
                    <p class="text-sm text-white/90">Resumen del cliente seleccionado e historial de ingresos y salidas</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <label class="hidden sm:flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-3 py-2 cursor-pointer" title="Activar o desactivar sonido cuando el cliente tiene deuda">
                    <flux:icon name="speaker-wave" class="h-5 w-5 text-white" x-show="soundEnabled" />
                    <flux:icon name="speaker-x-mark" class="h-5 w-5 text-white" x-show="!soundEnabled" x-cloak />
                    <span class="text-xs font-medium text-white">Sonido deuda</span>
                    <input type="checkbox" class="rounded border-white/30 bg-white/20 text-purple-600 focus:ring-white/50" x-model="soundEnabled" @change="toggleDebtSound()" />
                </label>
                <flux:button
                    variant="ghost"
                    size="sm"
                    type="button"
                    @click="toggleFullscreen()"
                    class="rounded-lg bg-white/10 text-white hover:bg-white/20 shrink-0"
                    aria-label="Ver en pantalla completa"
                    title="Ver en pantalla completa">
                    <span x-show="!fullscreen" class="inline-flex"><flux:icon name="arrows-pointing-out" class="h-5 w-5" /></span>
                    <span x-show="fullscreen" class="inline-flex" x-cloak><flux:icon name="arrows-pointing-in" class="h-5 w-5" /></span>
                </flux:button>
                <div class="hidden md:flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-4 py-2">
                    <flux:icon name="clock" class="h-5 w-5 text-white" />
                    <span class="text-sm font-medium text-white">{{ now()->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid: 1) Perfil + Estado | 2) Asistencias | 3) Estadísticas + Historial Membresías + Clases --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-stretch">
        {{-- 1. Perfil del Cliente + Estado y acceso (compacto, encuadrado) --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex flex-col min-h-0 overflow-visible">
            <div class="border-b border-zinc-200 bg-zinc-50/80 dark:bg-zinc-800/80 px-3 py-2 dark:border-zinc-700 shrink-0">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold tracking-tight text-zinc-800 dark:text-zinc-200">Perfil del Cliente y Estado y acceso</h3>
                    @if ($selectedCliente)
                        <flux:button variant="ghost" size="xs" wire:click="clearClienteSelection" aria-label="Limpiar selección" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                            <flux:icon name="x-mark" class="h-4 w-4" />
                        </flux:button>
                    @endif
                </div>
            </div>
            <div class="p-3 flex-1 flex flex-col min-h-0 min-w-0">
                @if ($selectedCliente)
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4 items-center">
                        {{-- Perfil: 2/3 — Foto y datos --}}
                        <div class="md:col-span-2 flex flex-col md:flex-row items-center md:items-center gap-3">
                            <div class="shrink-0">
                                @if ($selectedCliente->foto)
                                    <img src="{{ asset('storage/' . $selectedCliente->foto) }}" alt="{{ $selectedCliente->nombres }} {{ $selectedCliente->apellidos }}"
                                        class="h-28 w-28 rounded-full object-cover border-[3px] border-zinc-800 dark:border-zinc-600 shadow-md" />
                                @else
                                    <div class="h-28 w-28 rounded-full bg-gradient-to-br from-indigo-600 to-purple-700 flex items-center justify-center border-[3px] border-zinc-800 dark:border-zinc-600 shadow-md">
                                        <span class="text-2xl font-bold text-white tracking-tight">{{ strtoupper(substr($selectedCliente->nombres ?? '', 0, 1) . substr($selectedCliente->apellidos ?? '', 0, 1)) }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="text-center md:text-left space-y-0.5">
                                <p class="text-lg font-semibold text-zinc-900 dark:text-zinc-100 tracking-tight">{{ $selectedCliente->nombres }} {{ $selectedCliente->apellidos }}</p>
                                <div class="flex items-center justify-center md:justify-start gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    <flux:icon name="identification" class="h-3.5 w-3.5 shrink-0 text-zinc-400" />
                                    <span>{{ strtoupper($selectedCliente->tipo_documento) }}: {{ $selectedCliente->numero_documento }}</span>
                                </div>
                            </div>
                        </div>
                        {{-- Estado y acceso: 1/3 — Badges de estado --}}
                        <div class="md:col-span-1 flex flex-col justify-center border-t border-zinc-200 dark:border-zinc-700 pt-3 md:border-t-0 md:pt-0 md:border-l md:pl-4 border-zinc-200 dark:border-zinc-700 space-y-2">
                            @if ($membresiaActiva)
                                @if (!empty($validacionAcceso) && !$validacionAcceso['tiene_acceso'])
                                    <div class="rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/40 p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50">
                                                <flux:icon name="exclamation-triangle" class="h-4 w-4 text-red-600 dark:text-red-400" />
                                            </div>
                                            <p class="text-xs font-semibold text-red-800 dark:text-red-200 leading-tight">{{ $validacionAcceso['mensaje'] }}</p>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40 p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                                                <flux:icon name="check-circle" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200 leading-tight">{{ $validacionAcceso['mensaje'] ?? 'Acceso permitido.' }}</p>
                                        </div>
                                    </div>
                                @endif
                                @if ($ingresoEnCurso)
                                    <div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 px-2.5 py-2 shadow-sm">
                                        <p class="text-[11px] font-medium text-amber-800 dark:text-amber-200 leading-tight">En gimnasio desde {{ $ingresoEnCurso->fecha_hora_ingreso->format('d/m/Y H:i') }}</p>
                                    </div>
                                @endif
                                @php $tieneDeuda = ($saldoPendiente > 0) || (isset($selectedCliente->deuda_total) && $selectedCliente->deuda_total > 0); $deudaTotal = $selectedCliente->deuda_total ?? $saldoPendiente; @endphp
                                @if ($tieneDeuda)
                                    <div wire:key="debt-alert-{{ $selectedClienteId }}"
                                        class="rounded-lg border-2 border-red-500 bg-red-100 dark:border-red-500 dark:bg-red-950/60 p-3 shadow-lg ring-2 ring-red-400/50 dark:ring-red-500/40 animate-pulse">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-red-200 dark:bg-red-900/70">
                                                <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-700 dark:text-red-300" />
                                            </div>
                                            <div>
                                                <p class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-300">Deuda pendiente</p>
                                                <p class="mt-0.5 text-xl font-bold text-red-900 dark:text-red-100">S/ {{ number_format($deudaTotal, 2) }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40 p-2.5 shadow-sm">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                                                <flux:icon name="check-circle" class="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                                            </div>
                                            <p class="text-xs font-semibold text-emerald-800 dark:text-emerald-200 leading-tight">Sin deuda pendiente</p>
                                        </div>
                                    </div>
                                @endif
                            @else
                                <div class="rounded-lg border border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-950/40 p-2.5 shadow-sm">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/50">
                                            <flux:icon name="exclamation-triangle" class="h-4 w-4 text-red-600 dark:text-red-400" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-red-800 dark:text-red-200 leading-tight">Sin membresía activa</p>
                                            <p class="text-[11px] text-red-600/90 dark:text-red-300/90 mt-0.5 leading-tight">Sin membresía activa.</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    {{-- Estadísticas: ancho completo, encuadrado --}}
                    @if (!empty($estadisticasAsistencia))
                        <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700 col-span-full -mx-0.5">
                            <p class="text-[11px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Estadísticas</p>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div class="rounded-lg border border-zinc-200 bg-zinc-50 dark:bg-zinc-900/50 dark:border-zinc-700 p-2.5 min-w-0">
                                    <p class="text-[11px] text-zinc-600 dark:text-zinc-400 leading-tight">
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $estadisticasAsistencia['total_asistencias'] ?? 0 }}</span> asistencias
                                        @if (isset($estadisticasAsistencia['total_sesiones']))
                                            de <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $estadisticasAsistencia['total_sesiones'] }}</span> sesiones
                                        @endif
                                    </p>
                                    @if (isset($estadisticasAsistencia['porcentaje_efectividad']) && $estadisticasAsistencia['porcentaje_efectividad'] !== null)
                                        <div class="mt-1.5">
                                            <div class="flex items-center justify-between mb-0.5">
                                                <span class="text-[11px] font-medium text-zinc-600 dark:text-zinc-400">Efectividad</span>
                                                <span class="text-xs font-bold {{ $estadisticasAsistencia['porcentaje_efectividad'] < 50 ? 'text-red-600 dark:text-red-400' : ($estadisticasAsistencia['porcentaje_efectividad'] < 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}">{{ number_format($estadisticasAsistencia['porcentaje_efectividad'], 2) }}%</span>
                                            </div>
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full {{ $estadisticasAsistencia['porcentaje_efectividad'] < 50 ? 'bg-red-500' : ($estadisticasAsistencia['porcentaje_efectividad'] < 70 ? 'bg-yellow-500' : 'bg-green-500') }}" style="width: {{ min($estadisticasAsistencia['porcentaje_efectividad'], 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/40 p-2.5 text-center min-w-0">
                                    <p class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400">Asistidas</p>
                                    <p class="mt-0.5 text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $estadisticasAsistencia['asistencias_completas'] ?? 0 }}</p>
                                </div>
                                <div class="rounded-lg border border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-950/40 p-2.5 text-center min-w-0">
                                    <p class="text-[11px] font-medium text-amber-600 dark:text-amber-400">Pendientes</p>
                                    <p class="mt-0.5 text-lg font-bold text-amber-700 dark:text-amber-300">{{ $estadisticasAsistencia['asistencias_pendientes'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700/50">
                            <flux:icon name="user" class="h-8 w-8 text-zinc-400" />
                        </div>
                        <p class="mt-3 text-xs font-medium text-zinc-500 dark:text-zinc-400">Seleccione un cliente en el historial para ver su perfil y estado de acceso</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- 4. Asistencias Recientes --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex flex-col min-h-0">
            <div class="border-b border-zinc-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 dark:border-zinc-700 dark:from-blue-900/20 dark:to-indigo-900/20 shrink-0">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Asistencias Recientes</h3>
            </div>
            @if ($selectedCliente && count($asistenciasRecientes ?? []) > 0)
                <div class="p-4 flex-1 min-h-0 overflow-auto">
                    <div class="space-y-2">
                        @foreach ($asistenciasRecientes as $asistencia)
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $asistencia->fecha_hora_salida ? 'bg-green-100 dark:bg-green-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30' }}">
                                            <flux:icon name="clock" class="h-4 w-4 {{ $asistencia->fecha_hora_salida ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}" />
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $asistencia->fecha_hora_ingreso?->format('d/m/Y') }}</p>
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Ingreso: {{ $asistencia->fecha_hora_ingreso?->format('H:i') }} @if ($asistencia->fecha_hora_salida) • Salida: {{ $asistencia->fecha_hora_salida->format('H:i') }} @endif</p>
                                        </div>
                                    </div>
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $asistencia->fecha_hora_salida ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' }}">{{ $asistencia->fecha_hora_salida ? 'Completa' : 'En curso' }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center p-6">
                    <flux:icon name="document-text" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Busque un cliente para ver asistencias</p>
                </div>
            @endif
        </div>

        {{-- 5. Historial de Membresías e Historial de Clases --}}
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex flex-col min-h-0">
            <div class="border-b border-zinc-200 bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 dark:border-zinc-700 dark:from-purple-900/20 dark:to-pink-900/20 shrink-0">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Historial de Membresías e Historial de Clases</h3>
            </div>
            @if ($selectedCliente && (collect($historialMembresias ?? [])->isNotEmpty() || collect($historialClases ?? [])->isNotEmpty()))
                <div class="p-4 flex-1 min-h-0 overflow-auto space-y-4">
                    {{-- Historial de Membresías --}}
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Membresías</p>
                        @if (collect($historialMembresias ?? [])->isNotEmpty())
                            <div class="space-y-2">
                                @foreach (collect($historialMembresias ?? []) as $item)
                                    @php
                                        $nombre = $item->membresia->nombre ?? 'N/A';
                                        $estado = $item->estado ?? ($item->fecha_fin && $item->fecha_fin->isPast() ? 'vencida' : 'activa');
                                    @endphp
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $nombre }}</span>
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $estado === 'activa' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ ucfirst($estado) }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $item->fecha_inicio?->format('d/m/Y') ?? '-' }} @if($item->fecha_fin) – {{ $item->fecha_fin->format('d/m/Y') }} @endif</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Sin historial de membresías</p>
                        @endif
                    </div>
                    {{-- Historial de Clases --}}
                    <div>
                        <p class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide mb-2">Clases</p>
                        @if (collect($historialClases ?? [])->isNotEmpty())
                            <div class="space-y-2">
                                @foreach (collect($historialClases ?? []) as $item)
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $item->clase->nombre ?? 'N/A' }}</span>
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ ($item->estado ?? '') === 'activa' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ ucfirst($item->estado ?? '-') }}</span>
                                        </div>
                                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Desde {{ $item->fecha_inicio?->format('d/m/Y') ?? '-' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">Sin historial de clases</p>
                        @endif
                    </div>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center p-6">
                    <flux:icon name="chart-bar" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Busque un cliente para ver historial de membresías y clases</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Tabla historial de ingresos y salidas - mismo estilo card Checking --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden">
        <div class="border-b border-zinc-200 bg-gradient-to-r from-zinc-50 to-zinc-100 px-6 py-4 dark:border-zinc-700 dark:from-zinc-800 dark:to-zinc-900">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Historial de ingresos y salidas</h3>
        </div>
        <div class="overflow-x-auto max-h-80 overflow-y-auto">
            <table wire:loading.class="opacity-50" class="w-full text-sm text-left">
                <thead class="text-xs text-zinc-500 dark:text-zinc-400 uppercase bg-zinc-50 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-2.5 text-left">Cliente</th>
                        <th class="px-4 py-2.5 text-left">Ingreso</th>
                        <th class="px-4 py-2.5 text-left">Salida</th>
                        <th class="px-4 py-2.5 text-left">Estado</th>
                        <th class="px-4 py-2.5 text-left">Deuda</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->historialAsistencias as $asistencia)
                        @php
                            $cliente = $asistencia->cliente;
                            $clienteId = $cliente ? (int) $cliente->id : 0;
                            $clienteNombre = $cliente ? ($cliente->nombres . ' ' . $cliente->apellidos) : 'N/A';
                            $esSeleccionado = $selectedClienteId && $clienteId && $clienteId === (int) $selectedClienteId;
                            $tieneDeudaRow = $cliente && ($cliente->deuda_total ?? 0) > 0;
                            $deudaMonto = $tieneDeudaRow ? (float) $cliente->deuda_total : 0;
                        @endphp
                        <tr
                            wire:key="asistencia-{{ $asistencia->id }}"
                            wire:click="selectClienteFromRow({{ $clienteId }})"
                            role="button"
                            tabindex="0"
                            class="border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition-colors {{ $esSeleccionado ? 'bg-purple-50 dark:bg-purple-900/20' : '' }} {{ $tieneDeudaRow ? 'border-l-4 border-l-red-500 bg-red-50/70 dark:bg-red-950/30' : '' }}"
                        >
                            <td class="px-4 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $clienteNombre }}</td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-300">{{ $asistencia->fecha_hora_ingreso?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-4 py-2 text-zinc-600 dark:text-zinc-300">{{ $asistencia->fecha_hora_salida ? $asistencia->fecha_hora_salida->format('d/m/Y H:i') : '-' }}</td>
                            <td class="px-4 py-2">
                                @if ($asistencia->fecha_hora_salida)
                                    <span class="inline-flex rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-300">Salida</span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">Dentro</span>
                                @endif
                            </td>
                            <td class="px-4 py-2">
                                @if ($tieneDeudaRow)
                                    <span class="inline-flex items-center gap-1 rounded-md border-2 border-red-500 bg-red-100 px-2 py-1 text-xs font-bold uppercase tracking-wide text-red-800 dark:border-red-500 dark:bg-red-900/50 dark:text-red-200">
                                        <flux:icon name="exclamation-triangle" class="h-4 w-4 shrink-0" />
                                        S/ {{ number_format($deudaMonto, 2) }}
                                    </span>
                                @else
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No hay registros de ingreso o salida.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
