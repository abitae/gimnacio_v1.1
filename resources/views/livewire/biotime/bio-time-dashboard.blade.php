<div class="space-y-6 p-4 sm:p-6 lg:p-8">
    <div data-app-page-header class="rounded-xl p-6 shadow-lg {{ $pageHeaderGradientClass ?? 'bg-gradient-to-r from-red-600 to-red-700' }}">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-white">BioTime</h1>
                <p class="mt-1 text-sm text-white/80">Recepcion, homologacion y monitoreo del agente ZKTeco por sede.</p>
            </div>
            <div class="inline-flex items-center gap-2 rounded-lg bg-white/10 px-3 py-2 text-sm text-white">
                <span class="h-2.5 w-2.5 rounded-full {{ $isHealthy ? 'bg-emerald-300' : 'bg-rose-300' }}"></span>
                {{ $isHealthy ? 'Agente saludable' : 'Sin actividad reciente' }}
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
        <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['Clientes enlazados', $stats['clientes'], 'Activos con BioTime ID'],
                ['Departamentos', $stats['departments'], 'Catalogo sincronizado'],
                ['Areas mapeadas', $stats['areasMapped'], 'Homologadas a sucursal'],
                ['Dispositivos online', $stats['devicesOnline'], 'Estado reciente'],
                ['Marcajes de hoy', $stats['todayPunches'], 'Transacciones recibidas'],
            ] as [$label, $value, $hint])
                <div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                    <p class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ $label }}</p>
                    <p class="mt-3 text-3xl font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($value) }}</p>
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $hint }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Estado de salud</h2>
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Ultimo heartbeat / sync de sedes permitidas:
                        <span class="font-medium">{{ $lastReceivedAt ? $lastReceivedAt->diffForHumans() : 'sin registros' }}</span>
                    </p>
                </div>
                <div class="rounded-lg px-3 py-2 text-sm font-medium {{ $isHealthy ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' }}">
                    {{ $isHealthy ? 'Actividad en los ultimos 5 minutos' : 'Sin datos recientes' }}
                </div>
            </div>
            <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                Configura tokens y areas por sede en la pestana <button type="button" wire:click="setTab('sedes')" class="font-medium text-red-600 underline dark:text-red-300">Sedes</button>.
            </p>
        </section>

        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Operacion por sede</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                        <tr>
                            <th class="px-3 py-2 text-left">Sede</th>
                            <th class="px-3 py-2 text-left">Estado</th>
                            <th class="px-3 py-2 text-left">Heartbeat</th>
                            <th class="px-3 py-2 text-left">Ultimo sync</th>
                            <th class="px-3 py-2 text-right">Pending</th>
                            <th class="px-3 py-2 text-right">Failed 24h</th>
                            <th class="px-3 py-2 text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                        @forelse ($allowedSucursales as $sucursal)
                            @php
                                $setting = $settingsBySucursal->get($sucursal->id);
                                $ops = $opsBySucursal[$sucursal->id] ?? ['pending' => 0, 'failed_24h' => 0, 'heartbeat_stale' => true];
                            @endphp
                            <tr wire:key="ops-{{ $sucursal->id }}" class="{{ ($ops['heartbeat_stale'] ?? true) ? 'bg-rose-50/60 dark:bg-rose-950/20' : '' }}">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $sucursal->nombre }}</div>
                                    <div class="text-xs text-zinc-500">{{ $sucursal->codigo }}</div>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="rounded-full px-2 py-1 text-xs font-medium {{ $setting?->enabled ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                                        {{ $setting?->enabled ? 'enabled' : 'disabled' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <span class="text-xs font-medium {{ ($ops['heartbeat_stale'] ?? true) ? 'text-rose-600 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300' }}">
                                        @if ($ops['heartbeat_stale'] ?? true)
                                            Aviso &gt; 2h
                                        @endif
                                        {{ $setting?->last_heartbeat_at?->diffForHumans() ?? 'nunca' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-zinc-600 dark:text-zinc-400">
                                    {{ $setting?->last_received_at?->diffForHumans() ?? 'nunca' }}
                                </td>
                                <td class="px-3 py-2 text-right font-mono text-xs">{{ $ops['pending'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-right font-mono text-xs {{ ($ops['failed_24h'] ?? 0) > 0 ? 'text-rose-600 dark:text-rose-300' : '' }}">{{ $ops['failed_24h'] ?? 0 }}</td>
                                <td class="px-3 py-2 text-right">
                                    @can('biotime.editar')
                                        <button
                                            type="button"
                                            wire:click="reconcileAccess({{ $sucursal->id }})"
                                            wire:confirm="Encolar reconciliacion de acceso BioTime para esta sede?"
                                            class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-zinc-100 dark:text-zinc-900"
                                        >
                                            Reconciliar acceso
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-3 py-6 text-center text-zinc-500">Sin sedes permitidas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($tab === 'sedes')
        @include('livewire.biotime.partials.sucursal-settings')
    @endif

    @if ($tab === 'mapping')
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

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Dispositivos BioTime</h2>
                    <input wire:model.live.debounce.300ms="deviceSearch" placeholder="Buscar reloj" class="w-44 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                @include('livewire.biotime.partials.sucursal-mapping-table', ['rows' => $devices, 'type' => 'device', 'targets' => 'deviceTargets', 'nameField' => 'alias', 'codeField' => 'serial_number'])
            </div>

            <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Clientes/Empleados</h2>
                    <input wire:model.live.debounce.300ms="employeeSearch" placeholder="Buscar codigo o nombre" class="w-52 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                        <thead class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                            <tr>
                                <th class="px-3 py-2 text-left">BioTime</th>
                                <th class="px-3 py-2 text-left">Empleado</th>
                                <th class="px-3 py-2 text-left">Cliente</th>
                                <th class="px-3 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @forelse ($employees as $employee)
                                <tr>
                                    <td class="px-3 py-2 font-mono text-xs">{{ $employee->biotime_id }}</td>
                                    <td class="px-3 py-2">
                                        <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $employee->emp_code }}</div>
                                        <div class="text-xs text-zinc-500">{{ trim(($employee->first_name ?? '').' '.($employee->last_name ?? '')) ?: '-' }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select wire:model="employeeTargets.{{ $employee->biotime_id }}" class="w-64 rounded-lg border border-zinc-300 px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                            <option value="">Sin cliente</option>
                                            @foreach ($clientes as $cliente)
                                                <option value="{{ $cliente->id }}">{{ $cliente->codigo }} - {{ $cliente->nombres }} {{ $cliente->apellidos }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2 text-right">
                                        @can('biotime.editar')
                                            <button type="button" wire:click="saveEmployeeMapping({{ $employee->biotime_id }})" class="rounded-md bg-zinc-900 px-2.5 py-1.5 text-xs font-medium text-white dark:bg-zinc-100 dark:text-zinc-900">Guardar</button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="px-3 py-6 text-center text-zinc-500">Sin empleados sincronizados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    @if ($tab === 'history')
        <section class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row">
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
                            <tr><td colspan="5" class="px-3 py-8 text-center text-zinc-500">Sin registros de sincronizacion.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $logs->links() }}</div>
        </section>
    @endif
</div>
