<div class="space-y-6 p-4 sm:p-6 lg:p-8">
    <div data-app-page-header class="rounded-xl p-6 shadow-lg {{ $pageHeaderGradientClass ?? 'bg-gradient-to-r from-red-600 to-red-700' }}">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">BioTime</h1>
                <p class="mt-1 text-sm text-white/80">Acceso biométrico y puente ZKTeco por sede.</p>
            </div>
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                @if ($tab !== 'sedes' && $allowedSucursales->isNotEmpty())
                    <label class="sr-only" for="biotime-sede-select">Sede</label>
                    <select
                        id="biotime-sede-select"
                        wire:model.live="selectedSucursalId"
                        class="rounded-lg border-0 bg-white/15 px-3 py-2 text-sm text-white backdrop-blur focus:ring-2 focus:ring-white/40"
                    >
                        @foreach ($allowedSucursales as $sucursal)
                            <option value="{{ $sucursal->id }}" class="text-zinc-900">{{ $sucursal->nombre }} ({{ $sucursal->codigo }})</option>
                        @endforeach
                    </select>
                @endif
                <div class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm text-white">
                    <span class="h-2.5 w-2.5 rounded-full {{ $isHealthy ? 'bg-emerald-300' : 'bg-rose-300' }}"></span>
                    {{ $isHealthy ? 'Agente saludable' : 'Sin actividad reciente' }}
                </div>
            </div>
        </div>
    </div>

    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex flex-wrap gap-2">
            @foreach ([
                'dashboard' => 'Dashboard',
                'sedes' => 'Sedes',
                'mapping' => 'Mapeo',
                'history' => 'Historial',
            ] as $key => $label)
                <button
                    type="button"
                    wire:click="setTab('{{ $key }}')"
                    class="border-b-2 px-3 py-2 text-sm font-medium {{ $tab === $key ? 'border-red-600 text-red-600 dark:border-red-400 dark:text-red-300' : 'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    @if ($tab === 'dashboard')
        @if (! $selectedSucursal)
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
                No hay sedes disponibles. Asigna sucursales al usuario o configura BioTime en Sedes.
            </div>
        @else
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Vista de <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedSucursal->nombre }}</span>
                · {{ $selectedSetting?->enabled ? 'habilitada' : 'deshabilitada' }}
                · cupo {{ $selectedCapacity['occupied'] ?? 0 }}/{{ $selectedCapacity['limit'] ?? 500 }}
                @if ($selectedCapacity['alert'] ?? false)
                    <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">≥90%</span>
                @endif
            </p>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                @foreach ([
                    ['Clientes enlazados', $stats['clientes'], 'Automático: emp_code = numero_documento'],
                    ['Departamentos', $stats['departments'], 'Mapeados a esta sede'],
                    ['Areas mapeadas', $stats['areasMapped'], 'Homologadas a esta sede'],
                    ['Dispositivos online', $stats['devicesOnline'], 'Mapeados y activos'],
                    ['Marcajes de hoy', $stats['todayPunches'], 'Clientes de esta sede'],
                ] as [$label, $value, $hint])
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                        <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                        <p class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($value) }}</p>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
                    </div>
                @endforeach
            </section>

            <section class="grid gap-4 lg:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Salud del puente</h2>
                    <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Heartbeat:
                        <span class="font-medium {{ ($selectedOps['heartbeat_stale'] ?? true) ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                            @if ($selectedOps['heartbeat_stale'] ?? true)
                                Aviso &gt; 2h ·
                            @endif
                            {{ $selectedSetting?->last_heartbeat_at?->diffForHumans() ?? 'nunca' }}
                        </span>
                    </p>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Ultimo sync: {{ $selectedSetting?->last_received_at?->diffForHumans() ?? 'nunca' }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 dark:bg-zinc-800">Pending {{ $selectedOps['pending'] ?? 0 }}</span>
                        <span class="rounded-full px-2.5 py-1 {{ ($selectedOps['failed_24h'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' : 'bg-zinc-100 dark:bg-zinc-800' }}">
                            Failed 24h {{ $selectedOps['failed_24h'] ?? 0 }}
                        </span>
                    </div>
                    @can('biotime.editar')
                        <button
                            type="button"
                            wire:click="reconcileAccess({{ $selectedSucursal->id }})"
                            wire:confirm="Encolar reconciliacion de acceso BioTime para {{ $selectedSucursal->nombre }}?"
                            class="mt-4 rounded-md bg-zinc-900 px-3 py-2 text-sm font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
                        >
                            Reconciliar acceso
                        </button>
                    @endcan
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Todas las sedes</h2>
                    <p class="mt-1 text-xs text-zinc-500">Resumen rapido. Cambia la sede arriba o ve a la pestana Sedes.</p>
                    <div class="mt-3 overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                            <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                                <tr>
                                    <th class="px-2 py-2 text-left">Sede</th>
                                    <th class="px-2 py-2 text-left">Cupo</th>
                                    <th class="px-2 py-2 text-left">Heartbeat</th>
                                    <th class="px-2 py-2 text-right">Pend.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                @foreach ($allowedSucursales as $sucursal)
                                    @php
                                        $ops = $opsBySucursal[$sucursal->id] ?? [];
                                        $cap = $capacityBySucursal[$sucursal->id] ?? [];
                                        $setting = $settingsBySucursal->get($sucursal->id);
                                    @endphp
                                    <tr
                                        wire:key="dash-ops-{{ $sucursal->id }}"
                                        class="cursor-pointer {{ (int) $selectedSucursalId === (int) $sucursal->id ? 'bg-red-50/80 dark:bg-red-950/20' : '' }}"
                                        wire:click="$set('selectedSucursalId', {{ $sucursal->id }})"
                                    >
                                        <td class="px-2 py-2 font-medium">{{ $sucursal->nombre }}</td>
                                        <td class="px-2 py-2 font-mono text-xs {{ ($cap['alert'] ?? false) ? 'text-amber-600' : '' }}">{{ $cap['occupied'] ?? 0 }}/{{ $cap['limit'] ?? 500 }}</td>
                                        <td class="px-2 py-2 text-xs">{{ $setting?->last_heartbeat_at?->diffForHumans() ?? 'nunca' }}</td>
                                        <td class="px-2 py-2 text-right font-mono text-xs">{{ $ops['pending'] ?? 0 }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif
    @endif

    @if ($tab === 'sedes')
        @include('livewire.biotime.partials.sucursal-settings')
    @endif

    @if ($tab === 'mapping')
        @if (! $selectedSucursal)
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">Selecciona una sede.</div>
        @else
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Mapeo para <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedSucursal->nombre }}</span>
                (áreas, departamentos y dispositivos de esta sede o sin asignar).
                Los clientes se enlazan solos: <span class="font-medium text-zinc-800 dark:text-zinc-200">emp_code</span> BioTime =
                <span class="font-medium text-zinc-800 dark:text-zinc-200">codigo</span> Laravel.
            </p>
            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Areas BioTime</h2>
                        <input wire:model.live.debounce.300ms="areaSearch" placeholder="Buscar area" class="w-44 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                    @include('livewire.biotime.partials.sucursal-mapping-table', ['rows' => $areas, 'type' => 'area', 'targets' => 'areaTargets', 'nameField' => 'area_name', 'codeField' => 'area_code'])
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Departamentos BioTime</h2>
                        <input wire:model.live.debounce.300ms="departmentSearch" placeholder="Buscar depto." class="w-44 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                    @include('livewire.biotime.partials.sucursal-mapping-table', ['rows' => $departments, 'type' => 'department', 'targets' => 'departmentTargets', 'nameField' => 'dept_name', 'codeField' => 'dept_code'])
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900 xl:col-span-2">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Dispositivos BioTime</h2>
                        <input wire:model.live.debounce.300ms="deviceSearch" placeholder="Buscar reloj" class="w-44 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                    </div>
                    @include('livewire.biotime.partials.device-mapping-table', [
                        'rows' => $devices,
                        'desiredSelected' => $selectedCapacity['roster']['selected_count'] ?? 0,
                    ])
                </div>
            </section>
        @endif
    @endif

    @if ($tab === 'history')
        @if (! $selectedSucursal)
            <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">Selecciona una sede.</div>
        @else
            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                Historial de <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $selectedSucursal->nombre }}</span>
            </p>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <h2 class="mb-3 text-base font-semibold text-zinc-900 dark:text-zinc-100">Comandos de acceso</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Fecha</th>
                                <th class="px-3 py-2 text-left">Cliente</th>
                                <th class="px-3 py-2 text-left">Accion</th>
                                <th class="px-3 py-2 text-left">Estado</th>
                                <th class="px-3 py-2 text-left">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($accessCommands as $cmd)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap text-xs">{{ $cmd->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-3 py-2">
                                        <div class="font-mono text-xs">{{ $cmd->emp_code }}</div>
                                        <div class="text-xs text-zinc-500">{{ $cmd->cliente?->nombres }} {{ $cmd->cliente?->apellidos }}</div>
                                    </td>
                                    <td class="px-3 py-2">{{ $cmd->action }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $cmd->status === 'acked' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40' : ($cmd->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40') }}">
                                            {{ $cmd->status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-xs text-rose-600 dark:text-rose-300">{{ $cmd->last_error ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-6 text-center text-zinc-500">Sin comandos para esta sede.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $accessCommands->links() }}</div>
            </section>

            <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Sync logs</h2>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <select wire:model.live="historyEntity" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Todas las entidades</option>
                            <option value="transactions">Marcaciones</option>
                            <option value="employees">Clientes/Empleados</option>
                            <option value="devices">Relojes</option>
                            <option value="areas">Areas</option>
                            <option value="departments">Departamentos</option>
                        </select>
                        <select wire:model.live="historyStatus" class="rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                            <option value="">Todos los estados</option>
                            <option value="success">Exito</option>
                            <option value="pending">Pendiente</option>
                            <option value="failed">Fallido</option>
                        </select>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-3 py-2 text-left">Fecha</th>
                                <th class="px-3 py-2 text-left">Entidad</th>
                                <th class="px-3 py-2 text-left">Estado</th>
                                <th class="px-3 py-2 text-left">BioTime ID</th>
                                <th class="px-3 py-2 text-left">Detalle</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="px-3 py-2 whitespace-nowrap">{{ $log->processed_at?->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-3 py-2">{{ $log->entity }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded-full px-2 py-1 text-xs font-medium {{ $log->status === 'success' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : ($log->status === 'failed' ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-200') }}">
                                            {{ $log->status }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $log->biotime_id ?? '-' }}</td>
                                    <td class="px-3 py-2">
                                        <details>
                                            <summary class="cursor-pointer text-red-600 dark:text-red-300">Payload</summary>
                                            @if ($log->error_message)
                                                <p class="mt-2 text-xs text-rose-600 dark:text-rose-300">{{ $log->error_message }}</p>
                                            @endif
                                            <pre class="mt-2 max-h-64 overflow-auto rounded-lg bg-zinc-950 p-3 text-xs text-zinc-100">{{ json_encode($log->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </details>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-3 py-8 text-center text-zinc-500">Sin sync logs para esta sede.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $logs->links() }}</div>
            </section>
        @endif
    @endif
</div>
