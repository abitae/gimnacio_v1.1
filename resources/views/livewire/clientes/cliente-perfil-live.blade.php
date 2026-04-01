<div class="space-y-6">
    <div class="rounded-xl bg-gradient-to-r from-violet-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-lg bg-white/15">
                    <flux:icon name="user-circle" class="h-7 w-7 text-white" />
                </div>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-white">{{ __('Perfil de cliente') }}</h1>
                    <p class="flex flex-wrap items-center gap-x-1 text-sm text-white/90">
                        <span>{{ __('Busca un cliente y gestiona ficha, matrículas y reservas.') }}</span>
                        <flux:button href="{{ route('clientes.index') }}" wire:navigate variant="ghost" size="sm"
                            class="h-auto min-h-0 border-0 bg-transparent p-0 text-sm font-medium text-white underline decoration-white/40 underline-offset-2 shadow-none hover:bg-white/10 hover:decoration-white">
                            {{ __('Listado de clientes') }}
                        </flux:button>
                    </p>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2.5">
                @can('clientes.create')
                    <flux:button
                        size="sm"
                        icon="plus"
                        variant="ghost"
                        class="min-w-[8.5rem] rounded-xl border border-violet-200 bg-white px-4 font-semibold text-violet-700 shadow-sm hover:bg-violet-50"
                        wire:click="openClienteCreateModal"
                        type="button"
                    >
                        {{ __('Nuevo cliente') }}
                    </flux:button>
                @endcan
                @if ($selectedCliente)
                    @can('clientes.update')
                        <flux:button
                            size="sm"
                            icon="pencil"
                            variant="ghost"
                            class="min-w-[7rem] rounded-xl border border-slate-200 bg-slate-50 px-4 font-medium text-slate-700 shadow-sm hover:bg-slate-100"
                            wire:click="openClienteEditModal({{ $selectedCliente->id }})"
                            type="button"
                        >
                            {{ __('Editar') }}
                        </flux:button>
                        <flux:button
                            size="sm"
                            icon="photo"
                            variant="ghost"
                            class="min-w-[6.5rem] rounded-xl border border-sky-200 bg-sky-50 px-4 font-medium text-sky-700 shadow-sm hover:bg-sky-100"
                            wire:click="openClientePhotoModal({{ $selectedCliente->id }})"
                            type="button"
                        >
                            {{ __('Foto') }}
                        </flux:button>
                    @endcan
                    @can('clientes.delete')
                        <flux:button
                            size="sm"
                            icon="trash"
                            variant="ghost"
                            color="red"
                            class="min-w-[7rem] rounded-xl border border-rose-200 bg-rose-50 px-4 font-semibold text-rose-700 shadow-sm hover:bg-rose-100"
                            wire:click="openClienteDeleteModal({{ $selectedCliente->id }})"
                            type="button"
                        >
                            {{ __('Eliminar') }}
                        </flux:button>
                    @endcan
                @endif
                <div class="hidden rounded-lg bg-white/10 px-3 py-2 text-xs font-medium text-white md:block">
                    {{ now()->format('d/m/Y H:i') }}
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div class="min-w-[min(100%,22rem)] flex-1">
                <x-cliente.search-input
                    :clienteSearch="$clienteSearch"
                    :clientes="$clientes"
                    :selectedCliente="$selectedCliente"
                    :isSearching="$isSearching" />
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <flux:button href="{{ route('clientes.index') }}" wire:navigate variant="ghost" size="xs"
                    class="h-auto min-h-0 px-2 py-1 text-xs font-medium text-zinc-600 hover:text-violet-600 dark:text-zinc-400 dark:hover:text-violet-400">
                    {{ __('Listado de clientes') }}
                </flux:button>
            </div>
        </div>
    </div>

    @if ($selectedCliente)
        @php
            $estadoComercial = strtolower((string) ($membresiaActiva->estado ?? ''));
            $estadoComercialBadgeColor = match ($estadoComercial) {
                'activa', 'activo' => 'green',
                'vencida', 'vencido' => 'red',
                'congelada', 'congelado' => 'zinc',
                'completada' => 'amber',
                default => 'zinc',
            };
            $nombrePlanActivo = $membresiaActiva->membresia->nombre ?? $membresiaActiva->nombre ?? $membresiaActiva->clase->nombre ?? 'Sin plan';
            $deudaMembresiaResumen = max(0, round((float) ($deudaPlanesPendiente ?? 0), 2));
            $stats = $estadisticasAsistencia;
            $efectividad = (float) ($stats['porcentaje_efectividad'] ?? 0);
            $totalSesiones = (int) ($stats['total_sesiones'] ?? 0);
            $asistidas = (int) ($stats['asistencias_completas'] ?? 0);
            $pendientes = (int) ($stats['asistencias_pendientes'] ?? 0);
        @endphp

        <div class="grid gap-4 xl:grid-cols-[minmax(240px,280px)_1fr_290px]">
            <div class="space-y-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-1 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <x-cliente.profile-card
                        :cliente="$selectedCliente"
                        :hide-actions="true"
                        :minimized="$perfilClienteMinimizado"
                        :deuda-total="$deudaProductoPendiente + $deudaMembresiaResumen"
                    />
                </div>
                <div class="space-y-2 px-1">
                    @if ($selectedCliente->getWhatsAppUrlWithMessage())
                        <flux:button href="{{ $selectedCliente->getWhatsAppUrlWithMessage() }}" target="_blank" rel="noopener noreferrer"
                            variant="outline" size="xs" icon="chat-bubble-left-right" class="w-full border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                            {{ __('WhatsApp') }}
                        </flux:button>
                    @endif
                    @can('cliente-matriculas.view')
                        <flux:button href="{{ route('cliente-matriculas.index') }}" wire:navigate variant="outline" size="xs"
                            icon="arrow-top-right-on-square" class="w-full">
                            {{ __('Módulo matrículas') }}
                        </flux:button>
                    @endcan
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-base font-bold text-zinc-900 dark:text-zinc-50">Gestión</h2>
                        <flux:button href="{{ route('clientes.index') }}" wire:navigate variant="ghost" size="xs"
                            class="h-auto min-h-0 px-2 py-0.5 text-[11px] font-medium text-violet-600 hover:underline dark:text-violet-400">
                            {{ __('Volver al listado') }}
                        </flux:button>
                    </div>

                    <div class="mb-3 flex flex-wrap items-center gap-2 rounded-xl bg-zinc-50 px-3 py-2 dark:bg-zinc-800">
                        <span class="text-sm font-bold tracking-wide text-zinc-900 dark:text-zinc-50">{{ strtoupper($nombrePlanActivo) }}</span>
                        @if ($membresiaActiva)
                            <flux:badge :color="$estadoComercialBadgeColor" class="uppercase">
                                {{ strtoupper($estadoComercial ?: __('Sin estado')) }}
                            </flux:badge>
                        @endif
                    </div>

                    @if (in_array($estadoComercial, ['vencida', 'vencido'], true))
                        <div class="mb-3">
                            <flux:callout variant="danger" icon="exclamation-circle" :heading="__('Se terminó su contrato.')" />
                        </div>
                    @endif

                    <div class="mb-3">
                        <flux:subheading size="sm" class="mb-2 font-semibold text-zinc-800 dark:text-zinc-200">{{ __('Resumen de deudas') }}</flux:subheading>
                        <div class="grid gap-2 md:grid-cols-2">
                            <flux:card class="flex flex-col gap-2 p-3 {{ $deudaProductoPendiente > 0 ? 'ring-1 ring-amber-200/80 dark:ring-amber-900/60' : 'ring-1 ring-zinc-200/80 dark:ring-zinc-700' }}">
                                <flux:text class="text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Producto') }}</flux:text>
                                @if ($deudaProductoPendiente > 0)
                                    <flux:badge color="amber" class="w-fit uppercase">{{ __('Debe S/ :monto', ['monto' => number_format($deudaProductoPendiente, 2)]) }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" class="w-fit uppercase">{{ __('Sin deuda en producto') }}</flux:badge>
                                @endif
                            </flux:card>
                            <flux:card class="flex flex-col gap-2 p-3 {{ $deudaMembresiaResumen > 0 ? 'ring-1 ring-red-200/80 dark:ring-red-900/60' : 'ring-1 ring-zinc-200/80 dark:ring-zinc-700' }}">
                                <flux:text class="text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Membresía y planes') }}</flux:text>
                                @if ($deudaMembresiaResumen > 0)
                                    <flux:badge color="red" class="w-fit uppercase">{{ __('Debe S/ :monto en membresía', ['monto' => number_format($deudaMembresiaResumen, 2)]) }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" class="w-fit uppercase">{{ __('Sin deuda en membresía') }}</flux:badge>
                                @endif
                            </flux:card>
                        </div>
                    </div>

                    <div class="mb-4 flex flex-wrap gap-2">
                        @can('cliente-matriculas.create')
                            <flux:button size="xs" icon="plus" variant="primary" type="button" wire:click="openMatriculaCreateModal">
                                {{ __('Matricular') }}
                            </flux:button>
                        @endcan
                        
                        @can('cliente-matriculas.view')
                            <flux:button size="xs" icon="calendar-days" variant="outline" type="button" wire:click="openPrimeraCuotasConPlan">
                                {{ __('Ver cuotas') }}
                            </flux:button>
                        @endcan
                        @can('cliente-matriculas.create')
                            @if ($matriculasSinCronogramaCuotas->isNotEmpty())
                                <flux:button size="xs" icon="document-text" variant="outline" type="button" wire:click="openCrearPlanCuotasModal">
                                    {{ __('Crear plan de cuotas') }}
                                </flux:button>
                            @endif
                        @endcan
                        @can('rentals.create')
                            <flux:button size="xs" icon="building-office-2" variant="outline" type="button" wire:click="openReservaModal">
                                {{ __('Nueva reserva') }}
                            </flux:button>
                        @endcan
                        @can('gestion-nutricional.update')
                            <flux:button size="xs" icon="heart" variant="outline" type="button" wire:click="openSaludModal({{ $selectedCliente->id }})">
                                {{ __('Salud') }}
                            </flux:button>
                        @else
                            @can('gestion-nutricional.view')
                                <flux:button href="{{ route('gestion-nutricional.salud', $selectedCliente->id) }}" wire:navigate variant="outline" size="xs"
                                    icon="arrow-top-right-on-square">
                                    {{ __('Salud') }}
                                </flux:button>
                            @endcan
                        @endcan
                        @can('crm.view')
                            <flux:button href="{{ route('crm.clientes.etiquetas', $selectedCliente->id) }}" wire:navigate variant="outline" size="xs"
                                icon="tag" class="border-sky-200 bg-sky-50 text-sky-700 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-300">
                                {{ __('Etiquetas CRM') }}
                            </flux:button>
                        @endcan
                    </div>

                    <div class="mb-4 overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-800">
                        @can('cliente-matriculas.view')
                        <div class="flex flex-wrap border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950/80">
                            <flux:button type="button" wire:click="$set('perfilFinanzasTab', 'pagos')" variant="ghost" size="xs"
                                class="!rounded-none border-b-2 border-transparent px-4 py-2.5 text-xs font-semibold {{ $perfilFinanzasTab === 'pagos' ? '!border-violet-600 text-violet-700 dark:text-violet-300' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                                {{ __('Pagos') }}
                            </flux:button>
                            <flux:button type="button" wire:click="$set('perfilFinanzasTab', 'cuotas_pendientes')" variant="ghost" size="xs"
                                class="!rounded-none border-b-2 border-transparent px-4 py-2.5 text-xs font-semibold {{ $perfilFinanzasTab === 'cuotas_pendientes' ? '!border-violet-600 text-violet-700 dark:text-violet-300' : 'text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200' }}">
                                {{ __('Cuotas pendientes') }}
                            </flux:button>
                        </div>
                        @else
                        <p class="border-b border-zinc-200 bg-zinc-50 px-3 py-2 text-xs font-semibold text-zinc-700 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-300">{{ __('Pagos') }}</p>
                        @endcan

                        @can('cliente-matriculas.view')
                            @if ($perfilFinanzasTab === 'cuotas_pendientes')
                                <div class="overflow-x-auto">
                                    <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                        @forelse ($matriculasConCuotas as $matriculaCuotas)
                                            @php
                                                $estadoMatricula = strtolower((string) ($matriculaCuotas['estado_matricula'] ?? ''));
                                                $estadoMatriculaClass = match ($estadoMatricula) {
                                                    'activa', 'activo' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
                                                    'vencida', 'vencido' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                                    'congelada', 'congelado' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
                                                    default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                                };
                                            @endphp
                                            <div class="bg-white p-4 dark:bg-zinc-900">
                                                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                                    <div class="space-y-1">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <span class="inline-flex rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-semibold text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                                                {{ $matriculaCuotas['tipo_label'] }}
                                                            </span>
                                                            <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $estadoMatriculaClass }}">
                                                                {{ ucfirst($matriculaCuotas['estado_matricula']) }}
                                                            </span>
                                                        </div>
                                                        <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $matriculaCuotas['plan_nombre'] }}</p>
                                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                            {{ __('Matrícula #:id · Total S/ :total · Saldo S/ :saldo', ['id' => $matriculaCuotas['id'], 'total' => number_format((float) $matriculaCuotas['precio_total'], 2), 'saldo' => number_format((float) $matriculaCuotas['saldo_total'], 2)]) }}
                                                        </p>
                                                    </div>
                                                    <flux:button href="{{ route('clientes.cuotas', ['cliente' => $selectedClienteId, 'matricula' => $matriculaCuotas['id']]) }}" wire:navigate size="xs" variant="ghost" class="min-h-0 px-2 py-1 text-violet-600 hover:underline dark:text-violet-400">
                                                        {{ __('Ver cuotas') }}
                                                    </flux:button>
                                                </div>

                                                @if ($matriculaCuotas['tiene_cronograma'])
                                                    <table class="min-w-full text-xs">
                                                        <thead class="bg-zinc-50 dark:bg-zinc-950">
                                                            <tr class="text-left text-[11px] uppercase tracking-wide text-zinc-500">
                                                                <th class="px-3 py-2">{{ __('Cuota') }}</th>
                                                                <th class="px-3 py-2">{{ __('Vencimiento') }}</th>
                                                                <th class="px-3 py-2 text-right">{{ __('Monto') }}</th>
                                                                <th class="px-3 py-2">{{ __('Estado') }}</th>
                                                                <th class="px-3 py-2 text-right">{{ __('Acciones') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                                            @foreach ($matriculaCuotas['cuotas'] as $cuota)
                                                                @php
                                                                    $estadoCuota = (string) ($cuota['estado'] ?? 'pendiente');
                                                                    $estadoCuotaBadge = match ($estadoCuota) {
                                                                        'pagada' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
                                                                        'vencida' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                                                        'parcial' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                                                                        default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                                                    };
                                                                @endphp
                                                                <tr>
                                                                    <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">#{{ $cuota['numero_cuota'] }}</td>
                                                                    <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($cuota['fecha_vencimiento'])->format('d/m/Y') ?? '—' }}</td>
                                                                    <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $cuota['monto'], 2) }}</td>
                                                                    <td class="px-3 py-2">
                                                                        <span class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-medium {{ $estadoCuotaBadge }}">
                                                                            {{ $cuota['estado_label'] }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="px-3 py-2 text-right">
                                                                        @if ($cuota['puede_pagar'])
                                                                            @can('cliente-matriculas.update')
                                                                                <flux:button type="button" wire:click="openRegistrarPagoCuota({{ $cuota['id'] }})" size="xs" variant="ghost" class="min-h-0 px-2 py-0.5 text-violet-600 hover:underline dark:text-violet-400">
                                                                                    {{ __('Pagar') }}
                                                                                </flux:button>
                                                                            @endcan
                                                                        @else
                                                                            <span class="text-[11px] text-zinc-400">—</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <div class="rounded-lg border border-dashed border-amber-200 bg-amber-50 px-3 py-3 text-xs text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300">
                                                        {{ __('Esta matrícula está en cuotas pero aún no tiene cronograma.') }}
                                                        @if ($matriculasSinCronogramaCuotas->contains('id', $matriculaCuotas['id']))
                                                            @can('cliente-matriculas.create')
                                                                <flux:button type="button" wire:click="openCrearPlanCuotasModal" size="xs" variant="ghost" class="ml-2 min-h-0 px-2 py-0.5 text-amber-700 hover:underline dark:text-amber-300">
                                                                    {{ __('Crear plan de cuotas') }}
                                                                </flux:button>
                                                            @endcan
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="px-3 py-8 text-center text-zinc-500">
                                                {{ __('Este cliente no tiene membresías o clases matriculadas en cuotas.') }}
                                            </div>
                                        @endforelse

                                        @if ($matriculasSinCronogramaCuotas->isNotEmpty())
                                            <div class="border-t border-zinc-200 bg-zinc-50/70 p-4 dark:border-zinc-800 dark:bg-zinc-950/40">
                                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Matrículas en cuotas sin cronograma') }}</p>
                                                <div class="space-y-2">
                                                    @foreach ($matriculasSinCronogramaCuotas as $matriculaSinCronograma)
                                                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-zinc-200 bg-white px-3 py-2 text-xs dark:border-zinc-800 dark:bg-zinc-900">
                                                            <div>
                                                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $matriculaSinCronograma->nombre }}</p>
                                                                <p class="text-zinc-500 dark:text-zinc-400">
                                                                    {{ __('Matrícula #:id · Total S/ :total', ['id' => $matriculaSinCronograma->id, 'total' => number_format((float) $matriculaSinCronograma->precio_final, 2)]) }}
                                                                </p>
                                                            </div>
                                                            @can('cliente-matriculas.create')
                                                                <flux:button type="button" wire:click="openCrearPlanCuotasModal" size="xs" variant="outline">
                                                                    {{ __('Crear plan de cuotas') }}
                                                                </flux:button>
                                                            @endcan
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        @endcan

                        @if (! auth()->user()->can('cliente-matriculas.view') || $perfilFinanzasTab === 'pagos')
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-zinc-50 dark:bg-zinc-950">
                                        <tr class="text-left text-[11px] uppercase tracking-wide text-zinc-500">
                                            <th class="px-3 py-2">{{ __('Tipo') }}</th>
                                            <th class="px-3 py-2">{{ __('Estado') }}</th>
                                            <th class="px-3 py-2">{{ __('Plan') }}</th>
                                            <th class="px-3 py-2">{{ __('Inscripción') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Total matrícula') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Pagado') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Saldo') }}</th>
                                            <th class="px-3 py-2">{{ __('Modalidad') }}</th>
                                            <th class="px-3 py-2 text-right">{{ __('Acciones') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                                        @forelse ($matriculasFinancieras as $matriculaFinanciera)
                                            @php
                                                $estadoFinanciero = strtolower((string) ($matriculaFinanciera['estado_matricula'] ?? ''));
                                                $estadoFinancieroClass = match ($estadoFinanciero) {
                                                    'activa', 'activo' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
                                                    'vencida', 'vencido' => 'bg-red-100 text-red-700 dark:bg-red-950/40 dark:text-red-300',
                                                    'congelada', 'congelado' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
                                                    'completada' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
                                                    default => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300',
                                                };
                                            @endphp
                                            <tr class="bg-white dark:bg-zinc-900">
                                                <td class="px-3 py-2">
                                                    <span class="rounded-full bg-violet-100 px-2 py-0.5 text-[11px] font-medium text-violet-700 dark:bg-violet-950/40 dark:text-violet-300">
                                                        {{ $matriculaFinanciera['tipo_label'] }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <span class="rounded-full px-2 py-0.5 text-[11px] font-medium {{ $estadoFinancieroClass }}">
                                                        {{ ucfirst($matriculaFinanciera['estado_matricula']) }}
                                                    </span>
                                                </td>
                                                <td class="px-3 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $matriculaFinanciera['plan_nombre'] }}</td>
                                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ optional($matriculaFinanciera['fecha_matricula'])->format('d/m/Y') ?? '—' }}</td>
                                                <td class="px-3 py-2 text-right text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $matriculaFinanciera['precio_total'], 2) }}</td>
                                                <td class="px-3 py-2 text-right text-emerald-700 dark:text-emerald-400">S/ {{ number_format((float) $matriculaFinanciera['pagado_total'], 2) }}</td>
                                                <td class="px-3 py-2 text-right {{ (float) $matriculaFinanciera['saldo_total'] > 0 ? 'font-semibold text-red-600 dark:text-red-400' : 'text-zinc-600 dark:text-zinc-400' }}">S/ {{ number_format((float) $matriculaFinanciera['saldo_total'], 2) }}</td>
                                                <td class="px-3 py-2 text-zinc-600 dark:text-zinc-400">{{ $matriculaFinanciera['modalidad_pago'] }}</td>
                                                <td class="px-3 py-2 text-right">
                                                    <div class="flex flex-wrap justify-end gap-1">
                                                        @if ($matriculaFinanciera['usa_plan_cuotas'])
                                                            @can('cliente-matriculas.view')
                                                                <flux:button type="button" wire:click="$set('perfilFinanzasTab', 'cuotas_pendientes')" size="xs" variant="ghost" class="min-h-0 px-2 py-0.5 text-violet-600 hover:underline dark:text-violet-400">
                                                                    {{ __('Cuotas') }}
                                                                </flux:button>
                                                            @endcan
                                                        @elseif ($matriculaFinanciera['accion_cobrar_habilitada'])
                                                            @can('cliente-matriculas.update')
                                                                <flux:button type="button" size="xs" variant="ghost" class="min-h-0 px-2 py-0.5 text-violet-600 hover:underline dark:text-violet-400" wire:click="openCobroMatriculaModal({{ $matriculaFinanciera['id'] }})">
                                                                    {{ __('Cobrar') }}
                                                                </flux:button>
                                                            @endcan
                                                        @else
                                                            <span class="text-[11px] text-zinc-400">—</span>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="px-3 py-6 text-center text-zinc-500">{{ __('Sin matrículas registradas.') }}</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                @canany(['rentals.view', 'rentals.create', 'rentals.update'])
                    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-zinc-100 bg-zinc-50/80 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-950/50">
                            <div>
                                <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-50">{{ __('Reservas de espacios') }}</h3>
                                <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Próximas y recientes en un solo listado.') }}</p>
                            </div>
                            @can('rentals.create')
                                <flux:button size="xs" icon="plus" variant="primary" type="button" wire:click="openReservaModal">{{ __('Nueva reserva') }}</flux:button>
                            @endcan
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead>
                                    <tr class="border-b border-zinc-200 bg-zinc-50 text-left text-[10px] font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                                        <th class="whitespace-nowrap px-4 py-2.5">{{ __('Espacio') }}</th>
                                        <th class="whitespace-nowrap px-4 py-2.5">{{ __('Fecha') }}</th>
                                        <th class="whitespace-nowrap px-4 py-2.5">{{ __('Horario') }}</th>
                                        <th class="whitespace-nowrap px-4 py-2.5 text-right">{{ __('Precio') }}</th>
                                        <th class="whitespace-nowrap px-4 py-2.5">{{ __('Estado') }}</th>
                                        <th class="whitespace-nowrap px-4 py-2.5 text-right">{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @forelse ($reservasEspacios as $r)
                                        @php
                                            $estadoKey = (string) $r->estado;
                                            $estadoBadgeClass = match ($estadoKey) {
                                                'reservado' => 'bg-sky-100 text-sky-800 ring-1 ring-inset ring-sky-200/70 dark:bg-sky-950/40 dark:text-sky-200 dark:ring-sky-800',
                                                'confirmado' => 'bg-violet-100 text-violet-800 ring-1 ring-inset ring-violet-200/70 dark:bg-violet-950/40 dark:text-violet-200 dark:ring-violet-800',
                                                'pagado' => 'bg-emerald-100 text-emerald-800 ring-1 ring-inset ring-emerald-200/70 dark:bg-emerald-950/40 dark:text-emerald-200 dark:ring-emerald-800',
                                                'cancelado' => 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-200/70 dark:bg-red-950/40 dark:text-red-200 dark:ring-red-900',
                                                'finalizado' => 'bg-zinc-200 text-zinc-800 ring-1 ring-inset ring-zinc-300/80 dark:bg-zinc-700 dark:text-zinc-100 dark:ring-zinc-600',
                                                default => 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200/80 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700',
                                            };
                                            $estadoEtiqueta = \App\Models\Core\Rental::ESTADOS[$estadoKey] ?? ucfirst($estadoKey);
                                        @endphp
                                        <tr class="bg-white transition-colors hover:bg-zinc-50/80 dark:bg-zinc-900 dark:hover:bg-zinc-800/60">
                                            <td class="max-w-[10rem] truncate px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100" title="{{ $r->rentableSpace?->nombre ?? '' }}">{{ $r->rentableSpace?->nombre ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-2.5 tabular-nums text-zinc-600 dark:text-zinc-400">{{ $r->fecha?->format('d/m/Y') ?? '—' }}</td>
                                            <td class="whitespace-nowrap px-4 py-2.5 tabular-nums text-zinc-600 dark:text-zinc-400">{{ substr((string) $r->hora_inicio, 0, 5) }} – {{ substr((string) $r->hora_fin, 0, 5) }}</td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums text-zinc-700 dark:text-zinc-300">
                                                @if ($r->precio !== null && (float) $r->precio != 0.0)
                                                    S/ {{ number_format((float) $r->precio, 2) }}
                                                @else
                                                    <span class="text-zinc-400">—</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2.5">
                                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $estadoBadgeClass }}">
                                                    {{ __($estadoEtiqueta) }}
                                                </span>
                                            </td>
                                            <td class="whitespace-nowrap px-4 py-2.5 text-right">
                                                @can('rentals.update')
                                                    <flux:button size="xs" variant="ghost" type="button" wire:click="openReservaModal({{ $r->id }})">{{ __('Editar') }}</flux:button>
                                                @endcan
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sin reservas registradas.') }}</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endcanany
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

                <div class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-zinc-200 pb-2 dark:border-zinc-700">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-50">{{ __('Fidelización') }}</h3>
                        <div class="flex flex-wrap items-center gap-1">
                            <flux:button type="button" icon="eye" variant="ghost" size="xs" wire:click="openFidelizacionHistorialModal" class="text-violet-600 dark:text-violet-400">
                                {{ __('Ver') }}
                            </flux:button>
                            @can('clientes.update')
                                <flux:button type="button" icon="plus" variant="ghost" size="xs" wire:click="openFidelizacionNuevoModal" class="text-violet-600 dark:text-violet-400">
                                    {{ __('Agregar nuevo') }}
                                </flux:button>
                            @endcan
                        </div>
                    </div>
                    <div class="max-h-52 overflow-y-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                        <div class="divide-y divide-zinc-200 p-2 dark:divide-zinc-800">
                            @forelse (array_slice($fidelizacionMensajes, 0, 5) as $msg)
                                <div class="py-2.5 first:pt-0 last:pb-0">
                                    <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                                        {{ __('Incidencia:') }}
                                        <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $msg->prioridad_label }}</span>
                                    </p>
                                    <p class="mt-1 text-xs text-zinc-700 dark:text-zinc-300">{{ $msg->mensaje }}</p>
                                    <div class="mt-2 flex items-center justify-between border-t border-zinc-100 pt-2 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                                        <span>{{ $msg->autor?->name ?? '—' }}</span>
                                        <span class="tabular-nums">{{ $msg->created_at->locale('es')->format('d/m/Y') }} · {{ $msg->created_at->format('g:i A') }}</span>
                                    </div>
                                </div>
                            @empty
                                <p class="py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">{{ __('Sin mensajes de fidelización.') }}</p>
                            @endforelse
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
                    <flux:button
                        type="button"
                        wire:click="setTab('{{ $tabKey }}')"
                        variant="ghost"
                        size="sm"
                        class="!rounded-none border-b-2 border-transparent px-5 py-3 text-sm font-medium {{ $tabActiva === $tabKey ? '!border-violet-600 text-violet-700 dark:text-violet-300' : 'text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-100' }}"
                    >
                        {{ $tabLabel }}
                    </flux:button>
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
                                <th class="px-3 py-2 text-right">{{ __('Acciones') }}</th>
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
                                    <td class="px-3 py-2 text-right">
                                        @if ($mem instanceof \App\Models\Core\ClienteMatricula)
                                            <div class="flex flex-wrap justify-end gap-1">
                                                @can('cliente-matriculas.update')
                                                    @if ($saldo > 0)
                                                        <flux:button size="xs" variant="primary" type="button" wire:click="openCobroMatriculaModal({{ $mem->id }})">{{ __('Cobrar') }}</flux:button>
                                                    @endif
                                                @endcan
                                                @can('cliente-matriculas.view')
                                                    @if ($mem->usaPlanCuotas())
                                                        <flux:button size="xs" variant="outline" type="button" wire:click="openCuotasModal({{ $mem->id }})">{{ __('Cuotas') }}</flux:button>
                                                        <flux:button href="{{ route('clientes.cuotas', ['cliente' => $mem->cliente_id, 'matricula' => $mem->id]) }}" wire:navigate size="xs" variant="outline" class="min-h-0 px-2 py-1 text-[10px] text-violet-600 dark:text-violet-400">
                                                            {{ __('Ver cuotas') }}
                                                        </flux:button>
                                                        @isset($pendienteCuotaPorMatricula[$mem->id])
                                                            @can('cliente-matriculas.update')
                                                                <flux:button type="button" wire:click="openRegistrarPagoCuota({{ $pendienteCuotaPorMatricula[$mem->id]->id }})" size="xs" variant="outline" class="min-h-0 border-violet-200 bg-violet-50 px-2 py-1 text-[10px] text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300">
                                                                    {{ __('Pagar cuota') }}
                                                                </flux:button>
                                                            @endcan
                                                        @endisset
                                                    @endif
                                                @endcan
                                                @can('cliente-matriculas.update')
                                                    @if (strtolower((string) ($mem->estado ?? '')) === 'activa' && ($mem->membresia?->permite_congelacion ?? false))
                                                        <flux:button size="xs" variant="outline" type="button" icon="pause-circle"
                                                            wire:click="openCongelarMatriculaModal({{ $mem->id }})">{{ __('Congelar') }}</flux:button>
                                                    @endif
                                                    <flux:button size="xs" variant="ghost" type="button" wire:click="openMatriculaEditModal({{ $mem->id }})">{{ __('Editar') }}</flux:button>
                                                @endcan
                                                @can('cliente-matriculas.delete')
                                                    <flux:button size="xs" variant="ghost" type="button" wire:click="openMatriculaDeleteModal({{ $mem->id }})">{{ __('Eliminar') }}</flux:button>
                                                @endcan
                                            </div>
                                        @else
                                            <span class="text-[10px] text-zinc-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="16" class="px-3 py-10 text-center text-zinc-500">Sin membresías registradas.</td>
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
                                <th class="px-3 py-2 text-right">{{ __('Acciones') }}</th>
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
                                    <td class="px-3 py-2 text-right">
                                        <div class="flex flex-wrap justify-end gap-1">
                                            @can('cliente-matriculas.update')
                                                @if ($saldoMat > 0)
                                                    <flux:button size="xs" variant="primary" type="button" wire:click="openCobroMatriculaModal({{ $mat->id }})">{{ __('Cobrar') }}</flux:button>
                                                @endif
                                            @endcan
                                            @can('cliente-matriculas.view')
                                                @if ($mat->usaPlanCuotas())
                                                    <flux:button size="xs" variant="outline" type="button" wire:click="openCuotasModal({{ $mat->id }})">{{ __('Cuotas') }}</flux:button>
                                                    <flux:button href="{{ route('clientes.cuotas', ['cliente' => $mat->cliente_id, 'matricula' => $mat->id]) }}" wire:navigate size="xs" variant="outline" class="min-h-0 px-2 py-1 text-[10px] text-violet-600 dark:text-violet-400">
                                                        {{ __('Ver cuotas') }}
                                                    </flux:button>
                                                    @isset($pendienteCuotaPorMatricula[$mat->id])
                                                        @can('cliente-matriculas.update')
                                                            <flux:button type="button" wire:click="openRegistrarPagoCuota({{ $pendienteCuotaPorMatricula[$mat->id]->id }})" size="xs" variant="outline" class="min-h-0 border-violet-200 bg-violet-50 px-2 py-1 text-[10px] text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300">
                                                                {{ __('Pagar cuota') }}
                                                            </flux:button>
                                                        @endcan
                                                    @endisset
                                                @endif
                                            @endcan
                                            @can('cliente-matriculas.update')
                                                <flux:button size="xs" variant="ghost" type="button" wire:click="openMatriculaEditModal({{ $mat->id }})">{{ __('Editar') }}</flux:button>
                                            @endcan
                                            @can('cliente-matriculas.delete')
                                                <flux:button size="xs" variant="ghost" type="button" wire:click="openMatriculaDeleteModal({{ $mat->id }})">{{ __('Eliminar') }}</flux:button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-3 py-10 text-center text-zinc-500">Sin matrículas registradas.</td>
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

    @include('livewire.clientes.partials.perfil-modals')
    @include('livewire.cliente-matriculas.partials.matricula-modals')
</div>
