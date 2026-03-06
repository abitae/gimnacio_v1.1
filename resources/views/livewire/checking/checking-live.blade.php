@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-6">
    <!-- Header Profesional -->
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="check-circle" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Control de Acceso</h1>
                    <p class="text-sm text-white/90">Registro de ingresos y salidas de clientes</p>
                </div>
            </div>
            <div class="hidden md:flex items-center gap-2 rounded-lg bg-white/10 backdrop-blur-sm px-4 py-2">
                <flux:icon name="clock" class="h-5 w-5 text-white" />
                <span class="text-sm font-medium text-white">{{ now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    <div>
    </div>

    <!-- Búsqueda de Cliente - Diseño Profesional -->
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex items-center gap-2">
            <flux:icon name="magnifying-glass" class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
            <label class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                Buscar Cliente
            </label>
        </div>
        <div class="relative">
            <div class="relative">
                <flux:input 
                    icon="magnifying-glass" 
                    type="search" 
                    wire:model.live.debounce.300ms="clienteSearch" 
                    placeholder="Buscar: código, nombre..." 
                    class="w-full text-base border-zinc-300 focus:border-purple-500 focus:ring-purple-500"
                    autofocus
                    aria-label="Buscar cliente" 
                />
                
                @if ($isSearching)
                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                        <flux:icon name="arrow-path" class="h-5 w-5 animate-spin text-purple-600" />
                    </div>
                @endif
            </div>
            
            @if ($clienteSearch && !$isSearching && !$selectedCliente)
                @if ($clientes->count() > 0)
                    <div class="absolute z-20 mt-2 w-full rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800 max-h-64 overflow-y-auto">
                        @foreach ($clientes as $cliente)
                            <button type="button"
                                wire:click="selectCliente({{ $cliente->id }})"
                                class="w-full px-4 py-3 text-left hover:bg-purple-50 dark:hover:bg-zinc-700 focus:bg-purple-50 dark:focus:bg-zinc-700 focus:outline-none transition-colors border-b border-zinc-100 dark:border-zinc-700 last:border-b-0">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-purple-400 to-indigo-500">
                                        <span class="text-sm font-semibold text-white">
                                            {{ strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-semibold text-zinc-900 dark:text-zinc-100">
                                            {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                        </div>
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                                            @if ($cliente->email)
                                                <span class="ml-2">• {{ $cliente->email }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @elseif (strlen(trim($clienteSearch)) >= 2)
                    <div class="absolute z-20 mt-2 w-full rounded-lg border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800 p-6">
                        <div class="text-center">
                            <flux:icon name="magnifying-glass" class="mx-auto h-12 w-12 text-zinc-400" />
                            <p class="mt-2 text-sm font-medium text-zinc-600 dark:text-zinc-400">No se encontraron clientes</p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">Intenta con otro término de búsqueda</p>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <!-- Información del Cliente (vista siempre visible; datos se cargan al buscar) -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-stretch lg:min-h-[32rem]">
        <!-- Columna Izquierda: Perfil del Cliente -->
        <div class="lg:col-span-1 flex flex-col min-h-0">
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex flex-col flex-1 min-h-0">
                <div class="border-b border-zinc-200 bg-gradient-to-r from-zinc-50 to-zinc-100 px-6 py-4 dark:border-zinc-700 dark:from-zinc-800 dark:to-zinc-900 flex-shrink-0">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Perfil del Cliente</h3>
                        @if ($selectedCliente)
                            <flux:button variant="ghost" size="xs" wire:click="clearClienteSelection" aria-label="Limpiar selección">
                                <flux:icon name="x-mark" class="h-4 w-4" />
                            </flux:button>
                        @endif
                    </div>
                </div>

                <div class="p-6 flex-1 flex flex-col min-h-0">
                    @if ($selectedCliente)
                        <!-- Foto del Cliente -->
                        <div class="flex justify-center relative mb-4">
                            <div class="relative">
                                @if ($selectedCliente->foto)
                                    <img src="{{ Storage::url($selectedCliente->foto) }}" alt="Foto del cliente"
                                        class="h-32 w-32 rounded-full object-cover border-4 border-white shadow-lg ring-4 ring-purple-100 dark:ring-purple-900/20">
                                @else
                                    <div class="h-32 w-32 rounded-full bg-gradient-to-br from-purple-500 via-indigo-500 to-pink-500 flex items-center justify-center border-4 border-white shadow-lg ring-4 ring-purple-100 dark:ring-purple-900/20">
                                        <span class="text-3xl font-bold text-white">
                                            {{ strtoupper(substr($selectedCliente->nombres, 0, 1) . substr($selectedCliente->apellidos, 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                                @if ($selectedCliente->biotime_state)
                                    <div class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full bg-blue-500 border-2 border-white shadow-md">
                                        <flux:icon name="identification" class="h-4 w-4 text-white" />
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Información Básica -->
                        <div class="space-y-3 text-center">
                            <div>
                                <p class="text-base font-bold text-zinc-900 dark:text-zinc-100">
                                    {{ $selectedCliente->nombres }} {{ $selectedCliente->apellidos }}
                                </p>
                                @if ($membresiaActiva)
                                    <p class="mt-1 text-xs font-medium text-purple-600 dark:text-purple-400">
                                        Membresía #{{ $membresiaActiva->id }}
                                    </p>
                                @endif
                            </div>

                            <div class="space-y-2 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/50">
                                <div class="flex items-center justify-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    <flux:icon name="identification" class="h-4 w-4" />
                                    <span>{{ strtoupper($selectedCliente->tipo_documento) }}: {{ $selectedCliente->numero_documento }}</span>
                                </div>
                                @if ($selectedCliente->telefono)
                                    <div class="flex items-center justify-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                        <flux:icon name="phone" class="h-4 w-4" />
                                        <span>{{ $selectedCliente->telefono }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                <flux:icon name="user" class="h-8 w-8 text-zinc-400" />
                            </div>
                            <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-400">Busque un cliente para ver su perfil</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Columna Central: Estado y Acciones -->
        <div class="lg:col-span-1 flex flex-col gap-4 min-h-0">
            @if ($selectedCliente)
                <!-- Membresía y Estado de Acceso -->
                @if ($membresiaActiva)
                    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex flex-col flex-1 min-h-0">
                        <div class="border-b border-zinc-200 bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 dark:border-zinc-700 dark:from-green-900/20 dark:to-emerald-900/20">
                            <div class="flex items-center justify-between">
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Estado de Membresía</h3>
                                <span class="inline-flex rounded-full bg-green-500 px-3 py-1 text-xs font-semibold text-white">
                                    {{ $membresiaActiva->membresia->nombre ?? ($membresiaActiva->nombre ?? 'N/A') }}
                                </span>
                            </div>
                        </div>

                        <div class="p-6 space-y-4 flex-1 min-h-0">
                            <!-- Validación de Acceso por Horario -->
                            @if (!empty($validacionAcceso) && !$validacionAcceso['tiene_acceso'])
                                <div class="rounded-lg border-2 border-red-300 bg-red-50 p-4 dark:border-red-700 dark:bg-red-900/20">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                            <flux:icon name="exclamation-triangle" class="h-5 w-5 text-red-600 dark:text-red-400" />
                                        </div>
                                        <p class="text-sm font-bold text-red-900 dark:text-red-100">
                                            {{ $validacionAcceso['mensaje'] }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <!-- Estado de Deudas -->
                            <div>
                                @if ($saldoPendiente > 0)
                                    <div class="rounded-lg border-2 border-red-300 bg-gradient-to-r from-red-50 to-rose-50 p-4 dark:border-red-700 dark:from-red-900/20 dark:to-rose-900/20">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-xs font-semibold text-red-600 dark:text-red-400 uppercase tracking-wide">Deuda Pendiente</p>
                                                <p class="mt-1 text-2xl font-bold text-red-900 dark:text-red-100">S/ {{ number_format($saldoPendiente, 2) }}</p>
                                            </div>
                                            <a href="{{ route('cliente-matriculas.index') }}?cliente={{ $selectedCliente->id }}" wire:navigate>
                                                <flux:button size="sm" color="red" variant="primary" class="shadow-md">
                                                    Pagar
                                                </flux:button>
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-700 dark:bg-green-900/20">
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="check-circle" class="h-5 w-5 text-green-600 dark:text-green-400" />
                                            <p class="text-sm font-semibold text-green-800 dark:text-green-300">Sin deuda pendiente</p>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Información de Membresía -->
                            <div class="grid grid-cols-2 gap-3 rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/50">
                                <div>
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha Inicio</p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $membresiaActiva->fecha_inicio->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha Fin</p>
                                    <p class="mt-1 text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        {{ $membresiaActiva->fecha_fin ? $membresiaActiva->fecha_fin->format('d/m/Y') : 'Sin límite' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="rounded-xl border-2 border-red-300 bg-red-50 p-6 dark:border-red-700 dark:bg-red-900/20 flex-1 flex items-start min-h-0">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <flux:icon name="exclamation-triangle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-red-900 dark:text-red-100">Sin Membresía Activa</h3>
                                <p class="mt-1 text-xs text-red-700 dark:text-red-300">El cliente no tiene una membresía activa para ingresar.</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Botón de Registro: si tiene ingreso en curso → Registrar Salida; si no → Registrar Ingreso -->
                @if ($ingresoEnCurso)
                    <div class="space-y-2 flex-shrink-0">
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-center dark:border-amber-700 dark:bg-amber-900/20">
                            <p class="text-xs font-medium text-amber-800 dark:text-amber-200">Ingreso en curso desde {{ $ingresoEnCurso->fecha_hora_ingreso->format('d/m/Y H:i') }}</p>
                        </div>
                        @can('checking.update')
                        <flux:button
                            icon="clock"
                            color="amber"
                            variant="primary"
                            wire:click="registrarSalida({{ $ingresoEnCurso->id }})"
                            wire:loading.attr="disabled"
                            class="w-full py-4 text-base font-semibold shadow-lg hover:shadow-xl transition-shadow"
                        >
                            <span wire:loading.remove wire:target="registrarSalida">Registrar Salida</span>
                            <span wire:loading wire:target="registrarSalida">
                                <span class="inline-flex items-center gap-2">
                                    <flux:icon name="arrow-path" class="h-5 w-5 animate-spin" />
                                    Registrando salida...
                                </span>
                            </span>
                        </flux:button>
                        @endcan
                    </div>
                @elseif ($membresiaActiva && (empty($validacionAcceso) || $validacionAcceso['tiene_acceso']))
                    <div class="flex-shrink-0">
                        @can('checking.create')
                        <flux:button
                            icon="check-circle"
                            color="green"
                            variant="primary"
                            wire:click="registrarIngreso"
                            wire:loading.attr="disabled"
                            class="w-full py-4 text-base font-semibold shadow-lg hover:shadow-xl transition-shadow"
                        >
                            <span wire:loading.remove wire:target="registrarIngreso">Registrar Ingreso</span>
                            <span wire:loading wire:target="registrarIngreso">
                                <span class="inline-flex items-center gap-2">
                                    <flux:icon name="arrow-path" class="h-5 w-5 animate-spin" />
                                    Registrando...
                                </span>
                            </span>
                        </flux:button>
                        @endcan
                    </div>
                @endif
            @else
                <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex-1 flex flex-col min-h-0">
                    <div class="flex flex-col items-center justify-center py-16 text-center flex-1">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="check-circle" class="h-8 w-8 text-zinc-400" />
                        </div>
                        <p class="mt-4 text-sm font-medium text-zinc-600 dark:text-zinc-400">Busque un cliente para ver estado y registrar ingreso</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Columna Derecha: Asistencias y Estadísticas -->
        <div class="lg:col-span-1 flex flex-col gap-4 min-h-0">
            <!-- Asistencias Recientes -->
            <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex flex-col flex-1 min-h-0">
                <div class="border-b border-zinc-200 bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 dark:border-zinc-700 dark:from-blue-900/20 dark:to-indigo-900/20 flex-shrink-0">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Asistencias Recientes</h3>
                </div>
                <div class="p-4 flex-1 min-h-0 overflow-auto">
                    @if ($selectedCliente)
                        @if (count($asistenciasRecientes) > 0)
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @foreach ($asistenciasRecientes as $asistencia)
                                    <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <div class="flex h-8 w-8 items-center justify-center rounded-full {{ $asistencia->fecha_hora_salida ? 'bg-green-100 dark:bg-green-900/30' : 'bg-yellow-100 dark:bg-yellow-900/30' }}">
                                                    <flux:icon name="clock" class="h-4 w-4 {{ $asistencia->fecha_hora_salida ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400' }}" />
                                                </div>
                                                <div>
                                                    <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                                        {{ $asistencia->fecha_hora_ingreso->format('d/m/Y') }}
                                                    </p>
                                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                        Ingreso: {{ $asistencia->fecha_hora_ingreso->format('H:i') }}
                                                        @if ($asistencia->fecha_hora_salida)
                                                            • Salida: {{ $asistencia->fecha_hora_salida->format('H:i') }}
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium {{ $asistencia->fecha_hora_salida ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' }}">
                                                {{ $asistencia->fecha_hora_salida ? 'Completa' : 'En curso' }}
                                            </span>
                                        </div>
                                        @if ($asistencia->registradaPor)
                                            <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                                Por: {{ $asistencia->registradaPor->name }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="py-8 text-center">
                                <flux:icon name="document-text" class="mx-auto h-12 w-12 text-zinc-400" />
                                <p class="mt-2 text-sm font-medium text-zinc-500 dark:text-zinc-400">No hay asistencias registradas</p>
                            </div>
                        @endif
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-center">
                            <flux:icon name="document-text" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Busque un cliente para ver asistencias</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Estadísticas de Asistencia -->
            @if ($selectedCliente && !empty($estadisticasAsistencia) && $membresiaActiva)
                    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex-shrink-0">
                        <div class="border-b border-zinc-200 bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 dark:border-zinc-700 dark:from-purple-900/20 dark:to-pink-900/20">
                            <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Estadísticas</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            @if ($estadisticasAsistencia['total_sesiones'])
                                <div class="rounded-lg bg-zinc-50 p-3 dark:bg-zinc-900/50">
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $estadisticasAsistencia['total_asistencias'] }}</span> asistencias de 
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $estadisticasAsistencia['total_sesiones'] }}</span> sesiones
                                    </p>
                                    @if ($estadisticasAsistencia['porcentaje_efectividad'] !== null)
                                        <div class="mt-2">
                                            <div class="flex items-center justify-between mb-1">
                                                <span class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Efectividad</span>
                                                <span class="text-sm font-bold {{ $estadisticasAsistencia['porcentaje_efectividad'] < 50 ? 'text-red-600 dark:text-red-400' : ($estadisticasAsistencia['porcentaje_efectividad'] < 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}">
                                                    {{ number_format($estadisticasAsistencia['porcentaje_efectividad'], 2) }}%
                                                </span>
                                            </div>
                                            <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                                                <div class="h-full {{ $estadisticasAsistencia['porcentaje_efectividad'] < 50 ? 'bg-red-500' : ($estadisticasAsistencia['porcentaje_efectividad'] < 70 ? 'bg-yellow-500' : 'bg-green-500') }}" 
                                                     style="width: {{ min($estadisticasAsistencia['porcentaje_efectividad'], 100) }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                            <div class="grid grid-cols-2 gap-2">
                                <div class="rounded-lg bg-green-50 p-3 text-center dark:bg-green-900/20">
                                    <p class="text-xs font-medium text-green-600 dark:text-green-400">Asistidas</p>
                                    <p class="mt-1 text-lg font-bold text-green-700 dark:text-green-300">{{ $estadisticasAsistencia['asistencias_completas'] ?? 0 }}</p>
                                </div>
                                <div class="rounded-lg bg-yellow-50 p-3 text-center dark:bg-yellow-900/20">
                                    <p class="text-xs font-medium text-yellow-600 dark:text-yellow-400">Pendientes</p>
                                    <p class="mt-1 text-lg font-bold text-yellow-700 dark:text-yellow-300">{{ $estadisticasAsistencia['asistencias_pendientes'] ?? 0 }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
            @else
                <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800 flex-shrink-0">
                    <div class="border-b border-zinc-200 bg-gradient-to-r from-purple-50 to-pink-50 px-6 py-4 dark:border-zinc-700 dark:from-purple-900/20 dark:to-pink-900/20">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Estadísticas</h3>
                    </div>
                    <div class="flex flex-col items-center justify-center py-12 text-center p-4">
                        <flux:icon name="chart-bar" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Busque un cliente para ver estadísticas</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Historial de Membresías y Clases -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mt-6">
        <!-- Historial de Membresías -->
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 dark:border-zinc-700 dark:from-indigo-900/20 dark:to-purple-900/20">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Historial de Membresías</h3>
            </div>
            @if ($selectedCliente)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Membresía</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Período</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Asist. / Sesiones</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Monto</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Deuda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($historialMembresias as $membresia)
                                    @php
                                        $deudaMembresia = 0;
                                        $asistenciasCount = 0;
                                        $sesionesTotal = null;
                                        if ($membresia instanceof \App\Models\Core\ClienteMatricula) {
                                            $deudaMembresia = $this->clienteMatriculaService->obtenerSaldoPendiente($membresia->id);
                                            $asistenciasCount = \App\Models\Core\Asistencia::where('cliente_matricula_id', $membresia->id)->count();
                                            $sesionesTotal = $membresia->sesiones_totales;
                                        } else {
                                            $clienteMembresiaService = app(\App\Services\ClienteMembresiaService::class);
                                            $deudaMembresia = $clienteMembresiaService->obtenerSaldoPendiente($membresia->id);
                                            $asistenciasCount = \App\Models\Core\Asistencia::where('cliente_membresia_id', $membresia->id)->count();
                                        }
                                        $asistSesiones = $sesionesTotal !== null ? $asistenciasCount . '/' . $sesionesTotal : $asistenciasCount . '/-';
                                    @endphp
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                        <td class="px-4 py-3 text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $membresia->membresia->nombre ?? ($membresia->nombre ?? 'N/A') }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $membresia->fecha_inicio->format('d/m/Y') }} -
                                            {{ $membresia->fecha_fin ? $membresia->fecha_fin->format('d/m/Y') : 'Sin límite' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $asistSesiones }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                                @if ($membresia->estado === 'activa') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                                                @elseif($membresia->estado === 'vencida') bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                                                @elseif($membresia->estado === 'cancelada') bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                                                @elseif($membresia->estado === 'congelada') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400
                                                @elseif($membresia->estado === 'completada') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-400
                                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400 @endif">
                                                {{ ucfirst($membresia->estado) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                            S/ {{ number_format($membresia->precio_final, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-xs">
                                            @if ($deudaMembresia > 0)
                                                <span class="font-semibold text-red-600 dark:text-red-400">
                                                    S/ {{ number_format($deudaMembresia, 2) }}
                                                </span>
                                            @else
                                                <span class="text-green-600 dark:text-green-400">Sin deuda</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                            No hay historial de membresías
                                        </td>
                                    </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <flux:icon name="identification" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Busque un cliente para ver historial de membresías</p>
                </div>
            @endif
        </div>

        <!-- Historial de Matrículas a Clases -->
        <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 bg-gradient-to-r from-pink-50 to-rose-50 px-6 py-4 dark:border-zinc-700 dark:from-pink-900/20 dark:to-rose-900/20">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">Historial de Clases</h3>
            </div>
            @if ($selectedCliente)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Clase</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Fecha Inicio</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Estado</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Sesiones</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Monto</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-zinc-600 dark:text-zinc-400">Deuda</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($historialClases as $clase)
                                    @php
                                        $deudaClase = $this->clienteMatriculaService->obtenerSaldoPendiente($clase->id);
                                    @endphp
                                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                                        <td class="px-4 py-3 text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $clase->clase->nombre ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                            {{ $clase->fecha_inicio->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                                                @if ($clase->estado === 'activa') bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400
                                                @elseif($clase->estado === 'vencida') bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400
                                                @elseif($clase->estado === 'cancelada') bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400
                                                @elseif($clase->estado === 'completada') bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400
                                                @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400 @endif">
                                                {{ ucfirst($clase->estado) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-xs text-zinc-600 dark:text-zinc-400">
                                            @if ($clase->sesiones_totales)
                                                {{ $clase->sesiones_usadas ?? 0 }}/{{ $clase->sesiones_totales }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                            S/ {{ number_format($clase->precio_final, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-xs">
                                            @if ($deudaClase > 0)
                                                <span class="font-semibold text-red-600 dark:text-red-400">
                                                    S/ {{ number_format($deudaClase, 2) }}
                                                </span>
                                            @else
                                                <span class="text-green-600 dark:text-green-400">Sin deuda</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                            No hay historial de clases
                                        </td>
                                    </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center py-12 text-center">
                    <flux:icon name="academic-cap" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">Busque un cliente para ver historial de clases</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Modal de Confirmación (ingreso o salida) -->
    <flux:modal name="confirmacion-modal" wire:model="mostrarModalConfirmacion" focusable variant="floating" class="md:w-lg">
        @if ($asistenciaRegistrada && $tipoRegistroModal)
            <div class="space-y-6 p-6">
                <div class="text-center">
                    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-green-100 to-emerald-100 dark:from-green-900/20 dark:to-emerald-900/20">
                        <flux:icon name="check" class="h-10 w-10 text-green-600 dark:text-green-400" />
                    </div>
                    @if ($tipoRegistroModal === 'salida')
                        <h2 class="mt-4 text-xl font-bold text-zinc-900 dark:text-zinc-100">¡Salida Registrada!</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            La salida de <strong class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente?->nombres }} {{ $selectedCliente?->apellidos }}</strong> ha sido registrada exitosamente.
                        </p>
                    @else
                        <h2 class="mt-4 text-xl font-bold text-zinc-900 dark:text-zinc-100">¡Ingreso Registrado!</h2>
                        <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                            El ingreso de <strong class="text-zinc-900 dark:text-zinc-100">{{ $selectedCliente?->nombres }} {{ $selectedCliente?->apellidos }}</strong> ha sido registrado exitosamente.
                        </p>
                    @endif
                </div>

                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between items-center">
                            <span class="text-zinc-600 dark:text-zinc-400">Ingreso:</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $asistenciaRegistrada->fecha_hora_ingreso->format('d/m/Y H:i:s') }}
                            </span>
                        </div>
                        @if ($tipoRegistroModal === 'salida' && $asistenciaRegistrada->fecha_hora_salida)
                            <div class="flex justify-between items-center">
                                <span class="text-zinc-600 dark:text-zinc-400">Salida:</span>
                                <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $asistenciaRegistrada->fecha_hora_salida->format('d/m/Y H:i:s') }}
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between items-center">
                            <span class="text-zinc-600 dark:text-zinc-400">Membresía:</span>
                            <span class="font-semibold text-zinc-900 dark:text-zinc-100">
                                {{ $membresiaActiva?->membresia?->nombre ?? $membresiaActiva?->nombre ?? '-' }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="cerrarModal">Cerrar</flux:button>
                    <flux:button color="green" wire:click="cerrarModal" class="shadow-md">Continuar</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
