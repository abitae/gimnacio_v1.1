<section class="space-y-4">
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Configuracion BioTime por sede</h2>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Cada sede usa su propio token (Bearer / X-BioTime-Secret). El area BioTime autorizada se usa al activar acceso.
        </p>
    </div>

    @forelse ($allowedSucursales as $sucursal)
        @php
            $setting = $settingsBySucursal->get($sucursal->id);
            $revealed = isset($revealedTokenIds[$sucursal->id]);
            $plain = $plainTokens[$sucursal->id] ?? '';
            $masked = filled($setting?->webhook_secret) ? 'bt_••••••••••••••••••••' : '(sin token — regenera uno)';
        @endphp

        <article
            wire:key="biotime-sede-{{ $sucursal->id }}"
            class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-900"
            x-data="{ copied: false }"
        >
            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $sucursal->nombre }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Codigo {{ $sucursal->codigo }}
                        @if ($sucursal->es_principal)
                            · Principal
                        @endif
                    </p>
                </div>
                @php
                    $ops = $opsBySucursal[$sucursal->id] ?? ['pending' => 0, 'failed_24h' => 0, 'heartbeat_stale' => true];
                @endphp
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <span class="rounded-full px-2 py-1 font-medium {{ ($settingForms[$sucursal->id]['enabled'] ?? true) ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300' }}">
                        {{ ($settingForms[$sucursal->id]['enabled'] ?? true) ? 'Habilitada' : 'Deshabilitada' }}
                    </span>
                    <span class="rounded-full px-2 py-1 font-medium {{ ($ops['heartbeat_stale'] ?? true) ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-200' }}">
                        Heartbeat: {{ $setting?->last_heartbeat_at?->diffForHumans() ?? 'nunca' }}
                        @if ($ops['heartbeat_stale'] ?? true)
                            · aviso &gt; 2h
                        @endif
                    </span>
                    <span class="rounded-full px-2 py-1 font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200">
                        Pending {{ $ops['pending'] ?? 0 }}
                    </span>
                    <span class="rounded-full px-2 py-1 font-medium {{ ($ops['failed_24h'] ?? 0) > 0 ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-200' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200' }}">
                        Failed 24h {{ $ops['failed_24h'] ?? 0 }}
                    </span>
                </div>
            </div>

            <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                Ultimo sync recibido: {{ $setting?->last_received_at?->diffForHumans() ?? 'nunca' }}
            </p>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Token del puente</label>
                    <input
                        type="text"
                        readonly
                        x-ref="tokenField"
                        value="{{ $revealed ? $plain : $masked }}"
                        class="mt-2 w-full rounded-lg border border-zinc-300 bg-zinc-50 px-3 py-2 font-mono text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-950 dark:text-zinc-100"
                    >
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        Sync: {{ $setting?->last_received_at?->diffForHumans() ?? 'nunca' }}
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @can('biotime.editar')
                            @if ($revealed && filled($plain))
                                <button
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText($refs.tokenField.value); copied = true; setTimeout(() => copied = false, 1500)"
                                    class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    <span x-text="copied ? 'Copiado' : 'Copiar'"></span>
                                </button>
                                <button type="button" wire:click="hideSucursalToken({{ $sucursal->id }})" class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800">
                                    Ocultar
                                </button>
                            @else
                                <button
                                    type="button"
                                    wire:click="revealSucursalToken({{ $sucursal->id }})"
                                    wire:confirm="Mostrar el token en pantalla. Solo continua si no hay personas no autorizadas cerca."
                                    class="rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                >
                                    Mostrar token
                                </button>
                            @endif
                            <button
                                type="button"
                                wire:click="regenerateSucursalToken({{ $sucursal->id }})"
                                wire:confirm="Regenerar invalidara el token actual del puente en esta sede. Debes actualizar config.yaml."
                                class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                            >
                                Regenerar
                            </button>
                        @else
                            <p class="text-xs text-zinc-500">Solo biotime.editar puede ver o regenerar el token.</p>
                        @endcan
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Area BioTime (autorizada)</label>
                        <input
                            type="number"
                            min="1"
                            wire:model="settingForms.{{ $sucursal->id }}.area_biotime_id"
                            placeholder="ej. 2"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60"
                        >
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Area BioTime denegada</label>
                        <input
                            type="number"
                            min="1"
                            wire:model="settingForms.{{ $sucursal->id }}.denied_area_biotime_id"
                            placeholder="ej. 1"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60"
                        >
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Company BioTime</label>
                        <input type="number" min="1" wire:model="settingForms.{{ $sucursal->id }}.company_biotime_id" placeholder="ej. 1" @disabled(! auth()->user()?->can('biotime.editar')) class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Department BioTime</label>
                        <input type="number" min="1" wire:model="settingForms.{{ $sucursal->id }}.department_biotime_id" placeholder="ej. 1" @disabled(! auth()->user()?->can('biotime.editar')) class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Poll (segundos)</label>
                        <input
                            type="number"
                            min="60"
                            wire:model="settingForms.{{ $sucursal->id }}.poll_interval_seconds"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60"
                        >
                    </div>
                    <div class="sm:col-span-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">URL BioTime (referencia)</label>
                        <input
                            type="text"
                            wire:model="settingForms.{{ $sucursal->id }}.biotime_base_url"
                            placeholder="http://127.0.0.1:8085"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60"
                        >
                    </div>
                    <div>
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Cupo empleados (max)</label>
                        <input
                            type="number"
                            min="1"
                            max="500"
                            wire:model="settingForms.{{ $sucursal->id }}.employee_limit"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="mt-2 w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950 disabled:opacity-60"
                        >
                        @php
                            $cap = $capacityBySucursal[$sucursal->id] ?? null;
                        @endphp
                        @if ($cap)
                            <p class="mt-1 text-xs {{ ($cap['alert'] ?? false) ? 'text-amber-600 dark:text-amber-300' : 'text-zinc-500 dark:text-zinc-400' }}">
                                Ocupados: {{ $cap['occupied'] }}/{{ $cap['limit'] }}
                                @if ($cap['alert'] ?? false)
                                    · alerta ≥90%
                                @endif
                            </p>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                Seleccionados {{ $cap['roster']['selected_count'] ?? 0 }}
                                · En espera {{ $cap['roster']['waiting_count'] ?? 0 }}
                                @if (!($cap['roster']['inventory_ready'] ?? false) && ($cap['roster']['enforcement_enabled'] ?? false))
                                    · Inventario no verificado: altas bloqueadas
                                @endif
                            </p>
                        @endif
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input
                            id="enabled-{{ $sucursal->id }}"
                            type="checkbox"
                            wire:model="settingForms.{{ $sucursal->id }}.enabled"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="rounded border-zinc-300 text-red-600 focus:ring-red-500"
                        >
                        <label for="enabled-{{ $sucursal->id }}" class="text-sm text-zinc-700 dark:text-zinc-200">Sede habilitada para sync / puente</label>
                    </div>
                    <div class="flex items-center gap-2 sm:col-span-2">
                        <input
                            id="enforcement-{{ $sucursal->id }}"
                            type="checkbox"
                            wire:model="settingForms.{{ $sucursal->id }}.capacity_enforcement_enabled"
                            @disabled(! auth()->user()?->can('biotime.editar'))
                            class="rounded border-zinc-300 text-red-600 focus:ring-red-500"
                        >
                        <label for="enforcement-{{ $sucursal->id }}" class="text-sm text-zinc-700 dark:text-zinc-200">
                            Control estricto por reloj (requiere inventario verificado)
                        </label>
                    </div>
                </div>
            </div>

            @can('biotime.editar')
                <div class="mt-4 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        wire:click="reconcileAccess({{ $sucursal->id }})"
                        wire:confirm="Encolar reconciliacion de acceso BioTime para esta sede?"
                        class="rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        Reconciliar acceso
                    </button>
                    <button
                        type="button"
                        wire:click="saveSucursalSetting({{ $sucursal->id }})"
                        class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-white"
                    >
                        Guardar sede
                    </button>
                </div>
            @endcan
        </article>
    @empty
        <div class="rounded-lg border border-dashed border-zinc-300 p-8 text-center text-sm text-zinc-500 dark:border-zinc-700">
            No tienes sedes asignadas para configurar BioTime.
        </div>
    @endforelse
</section>
