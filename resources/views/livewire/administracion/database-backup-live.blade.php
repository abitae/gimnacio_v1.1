@php
    $restoreIsRunning = in_array($restoreStatus['status'] ?? null, ['queued', 'running'], true);
    $restoreIsDone = in_array($restoreStatus['status'] ?? null, ['completed', 'failed', 'cancelled'], true);
    $cancelRequested = (bool) ($restoreStatus['cancel_requested'] ?? false);
    $progress = (int) ($restoreStatus['progress'] ?? 0);
    $platformLauncher = $restoreStatus['platform_launcher'] ?? '-';
    $estimatedMode = $restoreStatus['estimated_mode'] ?? (!empty($restoreStatus['is_large_restore']) ? 'chunked' : 'single_part');
    $currentPart = max(1, (int) ($restoreStatus['current_part'] ?? 1));
    $totalParts = max(1, (int) ($restoreStatus['total_parts'] ?? 1));
    $eventCount = count($restoreEvents);
@endphp

<div class="space-y-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Backups de base de datos</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Exporta un backup completo en SQL y restaura toda la base desde un archivo generado por el sistema.</p>
        </div>

        <div class="flex items-center gap-2">
            @if($restoreStatus)
                <flux:button icon="command-line" variant="ghost" size="xs" wire:click="openRestoreMonitorModal">
                    Ver ejecucion
                </flux:button>
            @endif
            <flux:button icon="arrow-down-tray" color="purple" variant="primary" size="xs" wire:click="exportBackup" :disabled="$restoreIsRunning">
                Exportar backup
            </flux:button>
        </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
        <p class="font-semibold">Operacion sensible</p>
        <p class="mt-1">La restauracion reemplaza toda la base de datos actual. Antes de restaurar, exporta un backup reciente y asegurate de que nadie este operando el sistema.</p>
    </div>

    @if($restoreStatus)
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Ultima restauracion</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $restoreStatus['current_step'] ?? 'Sin actividad' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $progress }}%</span>
                    <span @class([
                        'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => ($restoreStatus['status'] ?? null) === 'completed',
                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => ($restoreStatus['status'] ?? null) === 'failed',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => ($restoreStatus['status'] ?? null) === 'cancelled',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => in_array($restoreStatus['status'] ?? null, ['queued', 'running'], true),
                    ])>
                        {{ strtoupper($restoreStatus['status'] ?? 'sin estado') }}
                    </span>
                </div>
            </div>
            <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                <div class="h-full bg-blue-600 transition-all" style="width: {{ $progress }}%"></div>
            </div>
        </div>
    @endif

    <div class="grid gap-4 xl:grid-cols-[1.35fr_0.95fr]">
        <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Backups generados</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Se guardan en <span class="font-mono">storage/app/backups</span>.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Archivo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tamano</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha de creacion</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($backups as $backup)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2.5 font-mono text-xs text-zinc-900 dark:text-zinc-100">{{ $backup['filename'] }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $backup['size_human'] }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $backup['created_at'] }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="flex flex-wrap gap-2">
                                        <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadBackup('{{ $backup['filename'] }}')" :disabled="$restoreIsRunning">
                                            Descargar
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="deleteBackup('{{ $backup['filename'] }}')" wire:confirm="Eliminar este backup SQL?" :disabled="$restoreIsRunning">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Todavia no hay backups generados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Restaurar base de datos</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sube un archivo <span class="font-mono">.sql</span> exportado desde este modulo.</p>

            <form wire:submit="restoreBackup" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>Archivo SQL</flux:label>
                    <input type="file" wire:model="restoreFile" accept=".sql,.txt"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-xs file:font-medium dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:file:bg-zinc-700" />
                    <flux:error name="restoreFile" />
                </flux:field>

                <flux:field>
                    <flux:label>Confirmacion</flux:label>
                    <flux:input wire:model="restoreConfirmation" placeholder="Escribe RESTAURAR" />
                    <flux:error name="restoreConfirmation" />
                </flux:field>

                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                    Esta accion elimina el estado actual y carga por completo el contenido del archivo.
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" color="red" variant="primary" icon="arrow-path" :disabled="$restoreIsRunning">
                        Restaurar base de datos
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    <flux:modal name="restore-monitor" wire:model="showRestoreMonitorModal" focusable class="max-w-6xl w-full">
        <div
            class="p-4 sm:p-5"
            @if($showRestoreMonitorModal && $restoreJobId) wire:poll.2s="refreshRestoreStatus" @endif
            x-data="{ stickToBottom: true, scrollToEnd() { if (this.stickToBottom && this.$refs.term) { this.$nextTick(() => { this.$refs.term.scrollTop = this.$refs.term.scrollHeight; }); } }, handleScroll() { if (! this.$refs.term) return; this.stickToBottom = (this.$refs.term.scrollTop + this.$refs.term.clientHeight) >= (this.$refs.term.scrollHeight - 24); } }"
            x-init="scrollToEnd()"
            x-effect="if ({{ $eventCount }} >= 0) { scrollToEnd(); }"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Terminal de restauracion</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Monitor en tiempo real del proceso de restauracion.</p>
                </div>
                @if($restoreStatus)
                    <span @class([
                        'inline-flex rounded-full px-2 py-1 text-xs font-medium',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => ($restoreStatus['status'] ?? null) === 'completed',
                        'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => ($restoreStatus['status'] ?? null) === 'failed',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => ($restoreStatus['status'] ?? null) === 'cancelled',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => in_array($restoreStatus['status'] ?? null, ['queued', 'running'], true),
                    ])>
                        {{ strtoupper($restoreStatus['status'] ?? 'sin estado') }}
                    </span>
                @endif
            </div>

            @if($restoreStatus)
                <div class="mt-4 space-y-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-center justify-between gap-3 text-xs text-zinc-600 dark:text-zinc-400">
                            <span>{{ $restoreStatus['current_step'] ?? 'Sin actividad' }}</span>
                            <span>{{ $progress }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full bg-blue-600 transition-all" style="width: {{ $progress }}%"></div>
                        </div>

                        <div class="mt-4 grid gap-3 md:grid-cols-4">
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Archivo</p>
                                <p class="mt-1 font-mono text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['original_name'] ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Plataforma</p>
                                <p class="mt-1 font-semibold uppercase text-zinc-900 dark:text-zinc-100">{{ $platformLauncher }}</p>
                            </div>
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Modo</p>
                                <p class="mt-1 font-semibold uppercase text-zinc-900 dark:text-zinc-100">{{ $estimatedMode }}</p>
                            </div>
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Partes</p>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $currentPart }} / {{ $totalParts }}</p>
                            </div>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Sentencias</p>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['executed_statements'] ?? 0 }} / {{ $restoreStatus['total_statements'] ?? 0 }}</p>
                            </div>
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Inicio</p>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['started_at'] ?? '-' }}</p>
                            </div>
                            <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                                <p class="text-zinc-500 dark:text-zinc-400">Ultimo evento</p>
                                <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['last_event_at'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>

                    @if(!empty($restoreStatus['error']))
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                            {{ $restoreStatus['error'] }}
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-xl border border-zinc-800 bg-[#0a0f1c] shadow-inner">
                        <div class="flex items-center justify-between border-b border-zinc-800 px-4 py-2">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-red-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                                <span class="h-2.5 w-2.5 rounded-full bg-green-400"></span>
                            </div>
                            <p class="text-[11px] font-medium uppercase tracking-[0.18em] text-zinc-400">restore@fitcenter terminal</p>
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded border border-zinc-700 px-2 py-1 text-[11px] text-zinc-300 transition hover:bg-zinc-800"
                                    x-on:click="if ($refs.term) { $refs.term.scrollTop = $refs.term.scrollHeight; stickToBottom = true; }"
                                >
                                    Volver al final
                                </button>
                            </div>
                        </div>

                        <div x-ref="term" @scroll="handleScroll()" class="max-h-[26rem] overflow-auto px-4 py-3 font-mono text-[12px] leading-6 text-zinc-100">
                            @forelse($restoreEvents as $event)
                                @php
                                    $level = $event['level'] ?? 'info';
                                    $lineClass = match ($level) {
                                        'success' => 'text-emerald-300',
                                        'error' => 'text-rose-300',
                                        'warn' => 'text-amber-300',
                                        'phase' => 'text-sky-300',
                                        'command' => 'text-violet-300',
                                        default => 'text-zinc-200',
                                    };
                                @endphp
                                <div class="flex gap-3">
                                    <span class="shrink-0 text-zinc-500">[{{ $event['timestamp'] ?? '--' }}]</span>
                                    <span class="shrink-0 text-zinc-400">restore@fitcenter:~$</span>
                                    <div class="min-w-0 flex-1">
                                        <div class="{{ $lineClass }}">
                                            {{ $event['message'] ?? '' }}
                                            @if(!empty($event['stage']))
                                                <span class="ml-2 text-zinc-500"># {{ $event['stage'] }}</span>
                                            @endif
                                        </div>
                                        @if(!empty($event['command']))
                                            <div class="break-all text-zinc-400">{{ $event['command'] }}</div>
                                        @endif
                                        @if(!empty($event['statement_index']) || !empty($event['part']))
                                            <div class="text-[11px] text-zinc-500">
                                                @if(!empty($event['statement_index']))
                                                    sentencia {{ $event['statement_index'] }} / {{ $event['total_statements'] ?? '?' }}
                                                @endif
                                                @if(!empty($event['part']))
                                                    <span class="ml-2">parte {{ $event['part'] }} / {{ $event['total_parts'] ?? '?' }}</span>
                                                @endif
                                                @if(isset($event['progress']) && $event['progress'] !== null)
                                                    <span class="ml-2">{{ $event['progress'] }}%</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-zinc-500">Sin eventos registrados todavia.</div>
                            @endforelse
                        </div>
                    </div>

                    @if($restoreIsDone)
                        <div @class([
                            'rounded-lg p-3 text-xs',
                            'border border-green-200 bg-green-50 text-green-800 dark:border-green-900/40 dark:bg-green-950/20 dark:text-green-300' => ($restoreStatus['status'] ?? null) === 'completed',
                            'border border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/40 dark:bg-amber-950/20 dark:text-amber-300' => ($restoreStatus['status'] ?? null) === 'cancelled',
                            'border border-red-200 bg-red-50 text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300' => ($restoreStatus['status'] ?? null) === 'failed',
                        ])>
                            @if(($restoreStatus['status'] ?? null) === 'completed')
                                Restauracion completada.
                            @elseif(($restoreStatus['status'] ?? null) === 'cancelled')
                                Restauracion cancelada.
                            @elseif(($restoreStatus['status'] ?? null) === 'failed')
                                Restauracion fallida.
                            @endif
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                            @if($restoreIsRunning)
                                Actualizacion automatica activa.
                            @else
                                El historial queda disponible aunque cierres este modal.
                            @endif
                        </div>

                        <div class="flex items-center gap-2">
                            @if($restoreIsRunning)
                                @if(!$cancelRequested)
                                    <flux:button
                                        color="amber"
                                        variant="primary"
                                        icon="stop"
                                        wire:click="cancelRestore"
                                        wire:confirm="Cancelar la restauracion en curso?"
                                    >
                                        Cancelar restauracion
                                    </flux:button>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        Cancelacion solicitada
                                    </span>
                                @endif
                            @endif

                            <flux:button variant="ghost" wire:click="closeRestoreMonitorModal">
                                Cerrar
                            </flux:button>
                        </div>
                    </div>
                </div>
            @else
                <div class="mt-4 rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Aun no se ha iniciado ninguna restauracion.
                </div>
            @endif
        </div>
    </flux:modal>
</div>
