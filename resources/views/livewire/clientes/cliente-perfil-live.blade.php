@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-6">
    <div class="rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/15">
                    <flux:icon name="user-circle" class="h-7 w-7 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Perfil de Cliente</h1>
                    <p class="text-sm text-white/90">Busca un cliente y visualiza toda su información comercial.</p>
                </div>
            </div>
            <div class="hidden rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white md:block">
                {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <x-cliente.search-input
            :clienteSearch="$clienteSearch"
            :clientes="$clientes"
            :selectedCliente="$selectedCliente"
            :isSearching="$isSearching" />
    </div>

    @if ($selectedCliente)
        @php
            $estadoClienteClass = match ($selectedCliente->estado_cliente) {
                'activo' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
                'inactivo' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                'suspendido' => 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-300',
                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            };
            $estadoComercial = strtolower((string) ($membresiaActiva->estado ?? ''));
            $estadoComercialClass = match ($estadoComercial) {
                'activa', 'activo' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
                'vencida', 'vencido' => 'bg-red-100 text-red-700 dark:bg-red-950/30 dark:text-red-300',
                'congelada', 'congelado' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/30 dark:text-sky-300',
                'completada' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
                default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
            };
            $nombrePlanActivo = $membresiaActiva->membresia->nombre ?? $membresiaActiva->nombre ?? $membresiaActiva->clase->nombre ?? 'Sin plan';
            $sinDeudaProducto = true;
            $deudaMembresia = $saldoPendiente > 0 ? $saldoPendiente : $selectedCliente->deuda_total;
            $stats = $estadisticasAsistencia;
            $efectividad = (float) ($stats['porcentaje_efectividad'] ?? 0);
            $totalSesiones = (int) ($stats['total_sesiones'] ?? 0);
            $asistidas = (int) ($stats['asistencias_completas'] ?? 0);
            $pendientes = (int) ($stats['asistencias_pendientes'] ?? 0);
        @endphp

        <div class="grid gap-4 xl:grid-cols-[260px_1fr_290px]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Cliente</p>
                        <flux:button variant="ghost" size="xs" wire:click="clearClienteSelection">
                            <flux:icon name="x-mark" class="h-4 w-4" />
                        </flux:button>
                    </div>

                    <div class="flex flex-col items-center text-center">
                        @if ($selectedCliente->foto)
                            <img src="{{ Storage::url($selectedCliente->foto) }}" alt="Foto del cliente" class="h-28 w-28 rounded-2xl object-cover shadow-md">
                        @else
                            <div class="flex h-28 w-28 items-center justify-center rounded-2xl bg-zinc-200 text-zinc-400 dark:bg-zinc-800">
                                <flux:icon name="user" class="size-14" />
                            </div>
                        @endif

                        <p class="mt-4 text-base font-bold uppercase leading-tight text-zinc-900 dark:text-zinc-100">
                            {{ $selectedCliente->nombres }} {{ $selectedCliente->apellidos }}
                        </p>
                        @if ($selectedCliente->fecha_nacimiento)
                            <p class="text-sm text-zinc-500">({{ $selectedCliente->fecha_nacimiento->age }})</p>
                        @endif

                        <div class="mt-2 space-y-1 text-xs text-zinc-600 dark:text-zinc-400">
                            <p>{{ $selectedCliente->tipo_documento }} {{ $selectedCliente->numero_documento }}</p>
                            @if ($selectedCliente->telefono)
                                <p>{{ $selectedCliente->telefono }}</p>
                            @endif
                            @if ($selectedCliente->biotime_state_bool)
                                <p>Huella verificada</p>
                            @endif
                        </div>

                        <span class="mt-3 rounded-full px-2.5 py-1 text-[11px] font-medium {{ $estadoClienteClass }}">
                            {{ ucfirst($selectedCliente->estado_cliente) }}
                        </span>
                    </div>

                    <div class="mt-4 space-y-2">
                        <a href="{{ route('clientes.index') }}" wire:navigate class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            <flux:icon name="information-circle" class="size-4" /> Ver información
                        </a>

                        @if ($selectedCliente->getWhatsAppUrlWithMessage())
                            <a href="{{ $selectedCliente->getWhatsAppUrlWithMessage() }}" target="_blank" class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                                <flux:icon name="chat-bubble-left-right" class="size-4" /> Escribir a Whatsapp
                            </a>
                        @endif

                        <a href="{{ route('cliente-matriculas.index') }}" wire:navigate class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-violet-200 bg-violet-50 px-3 py-2 text-xs font-medium text-violet-700 transition hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300">
                            <flux:icon name="identification" class="size-4" /> Gestionar membresías
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Gestión</h2>
                        <a href="{{ route('clientes.index') }}" wire:navigate class="text-[11px] text-violet-600 hover:underline dark:text-violet-400">
                            Volver al listado
                        </a>
                    </div>

                    <div class="mb-3 flex items-center gap-2 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                        <span class="text-sm font-bold tracking-wide text-zinc-900 dark:text-zinc-50">{{ strtoupper($nombrePlanActivo) }}</span>
                        @if ($membresiaActiva)
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estadoComercialClass }}">
                                {{ strtoupper($estadoComercial ?: 'SIN ESTADO') }}
                            </span>
                        @endif
                    </div>

                    @if (in_array($estadoComercial, ['vencida', 'vencido'], true))
                        <div class="mb-3 flex items-center justify-center gap-2 rounded-xl bg-red-600 px-3 py-2 text-sm font-semibold text-white">
                            <flux:icon name="exclamation-circle" class="size-5" />
                            Se terminó su contrato.
                            <flux:icon name="exclamation-circle" class="size-5" />
                        </div>
                    @endif

                    <div class="mb-3 grid gap-2 md:grid-cols-2">
                        <div class="rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800">
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ $sinDeudaProducto ? 'SIN DEUDA EN PRODUCTO' : 'DEBE EN PRODUCTO' }}
                            </p>
                        </div>
                        <div class="flex items-center justify-between gap-2 rounded-xl border {{ $deudaMembresia > 0 ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30' : 'border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800' }} px-3 py-2">
                            <p class="text-xs font-semibold {{ $deudaMembresia > 0 ? 'text-red-700 dark:text-red-300' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $deudaMembresia > 0 ? 'DEBE S/ ' . number_format($deudaMembresia, 2) . ' EN MEMBRESÍA' : 'SIN DEUDA EN MEMBRESÍA' }}
                            </p>
                        </div>
                    </div>

                    <div class="mb-4 grid gap-2 md:grid-cols-2 xl:grid-cols-4">
                        <a href="{{ route('cliente-matriculas.index') }}" wire:navigate class="flex items-center justify-center gap-1.5 rounded-xl border border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-medium text-zinc-700 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                            <flux:icon name="identification" class="size-4" /> Matrículas
                        </a>
                        @can('gestion-nutricional.view')
                        <a href="{{ route('gestion-nutricional.salud', $selectedCliente->id) }}" wire:navigate class="flex items-center justify-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-medium text-rose-700 transition hover:bg-rose-100 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">
                            <flux:icon name="heart" class="size-4" /> Salud
                        </a>
                        @endcan
                        @can('crm.view')
                        <a href="{{ route('crm.clientes.etiquetas', $selectedCliente->id) }}" wire:navigate class="flex items-center justify-center gap-1.5 rounded-xl border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-medium text-sky-700 transition hover:bg-sky-100 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300">
                            <flux:icon name="tag" class="size-4" /> Etiquetas CRM
                        </a>
                        @endcan
                        @if ($selectedCliente->getWhatsAppUrlWithMessage())
                        <a href="{{ $selectedCliente->getWhatsAppUrlWithMessage() }}" target="_blank" class="flex items-center justify-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                            <flux:icon name="chat-bubble-left-right" class="size-4" /> WhatsApp
                        </a>
                        @endif
                    </div>

                    <div class="mb-3">
                        <p class="mb-1.5 text-xs font-semibold text-zinc-700 dark:text-zinc-300">Pagos</p>
                        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                            <table class="min-w-full text-xs">
                                <thead class="bg-zinc-50 dark:bg-zinc-950">
                                    <tr class="text-left text-[11px] uppercase tracking-wide text-zinc-500">
                                        <th class="px-3 py-2">Estado</th>
                                        <th class="px-3 py-2">Fecha</th>
                                        <th class="px-3 py-2 text-right">Monto</th>
                                        <th class="px-3 py-2">Comprobante</th>
                                        <th class="px-3 py-2">F. pago</th>
                                        <th class="px-3 py-2">Creador</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                    @forelse ($pagosRecientes as $pago)
                                        <tr class="bg-white dark:bg-zinc-900">
                                            <td class="px-3 py-2">
                                                <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $pago->saldo_pendiente > 0 ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">
                                                    {{ $pago->saldo_pendiente > 0 ? 'Parcial' : 'Activo' }}
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($pago->fecha_pago)->format('d/m/Y g:i A') ?? '—' }}</td>
                                            <td class="px-3 py-2 text-right font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) $pago->monto, 0) }}</td>
                                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $pago->comprobante_numero ?? '—' }}</td>
                                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ ucfirst((string) ($pago->metodo_pago ?? '—')) }}</td>
                                            <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $pago->registradoPor?->name ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-3 py-6 text-center text-zinc-500">Sin pagos registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <h3 class="mb-3 text-sm font-bold text-zinc-900 dark:text-zinc-50">Asistencias</h3>
                    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                        <table class="min-w-full text-xs">
                            <thead class="bg-zinc-50 dark:bg-zinc-950">
                                <tr class="text-left text-[11px] uppercase tracking-wide text-zinc-500">
                                    <th class="px-2 py-2">Asistencia</th>
                                    <th class="px-2 py-2">Hora</th>
                                    <th class="px-2 py-2">Día</th>
                                    <th class="px-2 py-2">Responsable</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                @forelse ($asistenciasRecientes as $asistencia)
                                    <tr class="bg-white dark:bg-zinc-900">
                                        <td class="px-2 py-1.5 text-zinc-700 dark:text-zinc-300">{{ $asistencia->fecha_hora_ingreso->format('d/m/Y') }}</td>
                                        <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">{{ $asistencia->fecha_hora_ingreso->format('g:i:s A') }}</td>
                                        <td class="px-2 py-1.5 capitalize text-zinc-600 dark:text-zinc-400">{{ ucfirst($asistencia->fecha_hora_ingreso->locale('es')->dayName) }}</td>
                                        <td class="px-2 py-1.5 text-zinc-600 dark:text-zinc-400">{{ strtoupper($asistencia->registradaPor?->name ?? '—') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-2 py-4 text-center text-zinc-500">Sin asistencias.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3 rounded-xl bg-zinc-50 p-3 dark:bg-zinc-800">
                        <p class="text-center text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ $asistidas }} asistencias de {{ $totalSesiones }} sesiones
                        </p>
                        <p class="mt-1 text-center text-[11px] text-zinc-500">
                            % Efectividad asistencia: {{ number_format($efectividad, 2) }}%
                            @if ($efectividad < 50)
                                Bajo
                            @endif
                        </p>
                        <div class="mt-3 grid gap-2 text-[11px]">
                            <div class="rounded-lg bg-emerald-50 px-2 py-1 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                Asistidas: {{ $asistidas }}
                            </div>
                            <div class="rounded-lg bg-red-50 px-2 py-1 text-red-600 dark:bg-red-950/30 dark:text-red-300">
                                No asistidas: 0
                            </div>
                            <div class="rounded-lg bg-amber-50 px-2 py-1 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                                Pendientes: {{ $pendientes }}
                            </div>
                            <div class="rounded-lg bg-zinc-100 px-2 py-1 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                Sin reserva: {{ $asistidas }}
                            </div>
                        </div>
                    </div>
                </div>

                @if (collect($selectedCliente->health_summary ?? [])->filter()->isNotEmpty())
                    <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <h3 class="mb-3 text-sm font-bold text-zinc-900 dark:text-zinc-50">Salud</h3>
                        <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                            @if ($selectedCliente->health_summary['enfermedades'] ?? null)
                                <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">Enfermedades:</span> {{ $selectedCliente->health_summary['enfermedades'] }}</p>
                            @endif
                            @if ($selectedCliente->health_summary['alergias'] ?? null)
                                <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">Alergias:</span> {{ $selectedCliente->health_summary['alergias'] }}</p>
                            @endif
                            @if ($selectedCliente->health_summary['medicacion'] ?? null)
                                <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">Medicación:</span> {{ $selectedCliente->health_summary['medicacion'] }}</p>
                            @endif
                            @if ($selectedCliente->health_summary['lesiones'] ?? null)
                                <p><span class="font-semibold text-zinc-900 dark:text-zinc-100">Lesiones:</span> {{ $selectedCliente->health_summary['lesiones'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex border-b border-zinc-200 dark:border-zinc-800">
                @foreach (['membresias' => 'Membresías', 'matriculas' => 'Clases'] as $tabKey => $tabLabel)
                    <button
                        type="button"
                        wire:click="setTab('{{ $tabKey }}')"
                        class="px-5 py-3 text-sm font-medium transition {{ $tabActiva === $tabKey ? 'border-b-2 border-violet-600 text-violet-700 dark:text-violet-300' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                    >
                        {{ $tabLabel }}
                    </button>
                @endforeach
            </div>

            @if ($tabActiva === 'membresias')
                <div class="overflow-auto p-1">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-950">
                            <tr class="text-left text-[11px] uppercase tracking-wide text-zinc-500">
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Plan</th>
                                <th class="px-3 py-2">Tiempo</th>
                                <th class="px-3 py-2">Inscripción</th>
                                <th class="px-3 py-2">F. Inicio</th>
                                <th class="px-3 py-2">F. Fin</th>
                                <th class="px-3 py-2 text-right">Precio</th>
                                <th class="px-3 py-2 text-right">A Cuenta</th>
                                <th class="px-3 py-2 text-right">Saldo</th>
                                <th class="px-3 py-2 text-center">Freezing</th>
                                <th class="px-3 py-2 text-center">Freez. Tom.</th>
                                <th class="px-3 py-2 text-center">Freez. Act.</th>
                                <th class="px-3 py-2 text-center"># Actual</th>
                                <th class="px-3 py-2">Responsable</th>
                                <th class="px-3 py-2">Sede</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($historialMembresias as $mem)
                                @php
                                    $pagados = $mem->pagos->sum('monto');
                                    $saldo = max(0, (float) ($mem->precio_final ?? 0) - (float) $pagados);
                                    $estado = strtolower((string) ($mem->estado ?? ''));
                                    $estadoClass = match ($estado) {
                                        'activa', 'activo' => 'bg-emerald-100 text-emerald-700',
                                        'vencida', 'vencido' => 'bg-red-100 text-red-700',
                                        'congelada', 'congelado' => 'bg-sky-100 text-sky-700',
                                        default => 'bg-zinc-100 text-zinc-600',
                                    };
                                    $planNombre = $mem->membresia->nombre ?? $mem->nombre ?? '—';
                                @endphp
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estadoClass }}">{{ ucfirst($mem->estado ?? '—') }}</span></td>
                                    <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ strtoupper($planNombre) }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $mem->membresia->duracion_dias ?? $mem->sesiones_totales ?? '—' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($mem->fecha_matricula)->format('d/m/Y g:i A') ?? '—' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($mem->fecha_inicio)->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($mem->fecha_fin)->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) ($mem->precio_final ?? 0), 0) }}</td>
                                    <td class="px-3 py-2 text-right text-emerald-700 dark:text-emerald-400">{{ number_format((float) $pagados, 0) }}</td>
                                    <td class="px-3 py-2 text-right {{ $saldo > 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">{{ number_format($saldo, 0) }}</td>
                                    <td class="px-3 py-2 text-center text-zinc-600 dark:text-zinc-400">0</td>
                                    <td class="px-3 py-2 text-center text-zinc-600 dark:text-zinc-400">0</td>
                                    <td class="px-3 py-2 text-center text-zinc-600 dark:text-zinc-400">0</td>
                                    <td class="px-3 py-2 text-center text-zinc-600 dark:text-zinc-400">{{ $mem->membresia->id ?? '—' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ strtoupper($mem->asesor->name ?? '—') }}</td>
                                    <td class="px-3 py-2 text-center text-zinc-600 dark:text-zinc-400">1</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="15" class="px-3 py-10 text-center text-zinc-500">Sin membresías registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @elseif ($tabActiva === 'matriculas')
                <div class="overflow-auto p-1">
                    <table class="min-w-full text-xs">
                        <thead class="bg-zinc-50 dark:bg-zinc-950">
                            <tr class="text-left text-[11px] uppercase tracking-wide text-zinc-500">
                                <th class="px-3 py-2">Tipo</th>
                                <th class="px-3 py-2">Estado</th>
                                <th class="px-3 py-2">Plan</th>
                                <th class="px-3 py-2">Sesiones</th>
                                <th class="px-3 py-2">F. Inicio</th>
                                <th class="px-3 py-2">F. Fin</th>
                                <th class="px-3 py-2 text-right">Precio</th>
                                <th class="px-3 py-2 text-right">A Cuenta</th>
                                <th class="px-3 py-2 text-right">Saldo</th>
                                <th class="px-3 py-2">Responsable</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                            @forelse ($historialClases as $mat)
                                @php
                                    $pagadosMat = $mat->pagos->sum('monto');
                                    $saldoMat = max(0, (float) ($mat->precio_final ?? 0) - (float) $pagadosMat);
                                    $estadoMat = strtolower((string) ($mat->estado ?? ''));
                                    $estadoClassMat = match ($estadoMat) {
                                        'activa', 'activo' => 'bg-emerald-100 text-emerald-700',
                                        'vencida', 'vencido' => 'bg-red-100 text-red-700',
                                        'completada' => 'bg-amber-100 text-amber-700',
                                        default => 'bg-zinc-100 text-zinc-600',
                                    };
                                @endphp
                                <tr class="bg-white dark:bg-zinc-900">
                                    <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-medium bg-violet-100 text-violet-700">{{ $mat->esClase() ? 'Clase' : 'Membresía' }}</span></td>
                                    <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $estadoClassMat }}">{{ ucfirst($mat->estado ?? '—') }}</span></td>
                                    <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ strtoupper($mat->nombre) }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $mat->sesiones_usadas ?? 0 }} / {{ $mat->sesiones_totales ?? '∞' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($mat->fecha_inicio)->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($mat->fecha_fin)->format('d/m/Y') ?? '—' }}</td>
                                    <td class="px-3 py-2 text-right font-medium text-zinc-900 dark:text-zinc-100">{{ number_format((float) ($mat->precio_final ?? 0), 0) }}</td>
                                    <td class="px-3 py-2 text-right text-emerald-700 dark:text-emerald-400">{{ number_format((float) $pagadosMat, 0) }}</td>
                                    <td class="px-3 py-2 text-right {{ $saldoMat > 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">{{ number_format($saldoMat, 0) }}</td>
                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ strtoupper($mat->asesor->name ?? '—') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-3 py-10 text-center text-zinc-500">Sin matrículas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-12 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon name="user-circle" class="mx-auto h-14 w-14 text-zinc-300 dark:text-zinc-600" />
            <h2 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Primero busca un cliente</h2>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Selecciona un cliente desde el buscador para mostrar la ficha completa basada en la vista adjunta.
            </p>
        </div>
    @endif
</div>
