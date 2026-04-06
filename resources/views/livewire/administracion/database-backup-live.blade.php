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
    $startedAt = !empty($restoreStatus['started_at']) ? \Illuminate\Support\Carbon::parse($restoreStatus['started_at']) : null;
    $finishedAt = !empty($restoreStatus['finished_at']) ? \Illuminate\Support\Carbon::parse($restoreStatus['finished_at']) : null;
    $durationSeconds = $startedAt ? $startedAt->diffInSeconds($finishedAt ?? now()) : null;
    $launcherLabel = match ($platformLauncher) {
        'windows_cmd_start' => 'Windows CMD',
        'linux_nohup_sh' => 'Linux shell',
        default => strtoupper(str_replace('_', ' ', $platformLauncher)),
    };
@endphp

<div class="space-y-4 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Backups de base de datos</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Exporta backups manuales en ZIP con un solo SQL interno y restaura toda la base desde un ZIP del sistema o un SQL legado.</p>
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
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Se guardan en <span class="font-mono">storage/app/public/backups</span> y solo se generan manualmente desde este modulo.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Backup</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tamano total</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Partes</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha de creacion</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($backups as $backup)
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                <td class="px-4 py-2.5">
                                        <div class="space-y-1">
                                            <div class="font-mono text-xs text-zinc-900 dark:text-zinc-100">{{ $backup['display_name'] }}</div>
                                            <div class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                            @if(($backup['storage_type'] ?? null) === 'zip_bundle')
                                                ZIP: <span class="font-mono">{{ $backup['filename'] }}</span>
                                            @else
                                                SQL legado: <span class="font-mono">{{ $backup['filename'] }}</span>
                                            @endif
                                            </div>
                                        </div>
                                </td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $backup['total_size_human'] ?? $backup['size_human'] }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $backup['part_count'] }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $backup['created_at'] }}</td>
                                <td class="px-4 py-2.5">
                                    <div class="space-y-2">
                                        <div class="flex flex-wrap gap-2">
                                            <flux:button size="xs" variant="ghost" icon="arrow-down-tray" wire:click="downloadBackup('{{ $backup['filename'] }}')" :disabled="$restoreIsRunning">
                                                Descargar
                                            </flux:button>
                                        </div>

                                        <div class="flex flex-wrap gap-2">
                                        <flux:button
                                            size="xs"
                                            variant="ghost"
                                            color="amber"
                                            icon="arrow-path"
                                            wire:click="restoreGeneratedBackup('{{ $backup['filename'] }}')"
                                            wire:confirm="Restaurar directamente este backup reemplazara toda la base de datos actual. Continuar?"
                                            :disabled="$restoreIsRunning"
                                        >
                                            Restaurar
                                        </flux:button>
                                        <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="deleteBackup('{{ $backup['filename'] }}')" wire:confirm="Eliminar este backup y todas sus partes asociadas?" :disabled="$restoreIsRunning">
                                            Eliminar
                                        </flux:button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Todavia no hay backups generados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Restaurar base de datos</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sube un <span class="font-mono">.zip</span> generado por el sistema o un <span class="font-mono">.sql</span> legado.</p>

            <form wire:submit="restoreBackup" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>Archivo ZIP o SQL</flux:label>
                    <input type="file" wire:model="restoreFile" accept=".zip,.sql,.txt"
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

    <flux:modal name="restore-monitor" wire:model="showRestoreMonitorModal" focusable class="max-w-4xl w-full">
        <div
            class="p-3"
            @if($showRestoreMonitorModal && $restoreJobId) wire:poll.2s="refreshRestoreStatus" @endif
            x-data="{ stickToBottom: true, newLines: false, lastCount: {{ $eventCount }}, scrollToEnd() { if (this.$refs.term) { this.$nextTick(() => { this.$refs.term.scrollTop = this.$refs.term.scrollHeight; this.stickToBottom = true; this.newLines = false; }); } }, handleScroll() { if (! this.$refs.term) return; this.stickToBottom = (this.$refs.term.scrollTop + this.$refs.term.clientHeight) >= (this.$refs.term.scrollHeight - 24); if (this.stickToBottom) this.newLines = false; } }"
            x-init="scrollToEnd()"
            x-effect="if ({{ $eventCount }} !== lastCount) { if (stickToBottom) { scrollToEnd(); } else { newLines = true; } lastCount = {{ $eventCount }}; }"
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
                <div class="mt-3 space-y-2.5">
                    <div class="rounded-xl border border-zinc-200 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-[11px] text-zinc-600 dark:text-zinc-400">
                            <div class="flex min-w-0 items-center gap-2">
                                <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['current_step'] ?? 'Sin actividad' }}</span>
                                <span class="rounded-full bg-zinc-100 px-1.5 py-0.5 font-mono text-[10px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    {{ $restoreStatus['executed_statements'] ?? 0 }}/{{ $restoreStatus['total_statements'] ?? '?' }}
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5 font-mono text-[10px]">
                                <span>{{ $launcherLabel }}</span>
                                <span class="text-zinc-400">|</span>
                                <span class="uppercase">{{ $estimatedMode }}</span>
                                <span class="text-zinc-400">|</span>
                                <span>{{ $currentPart }}/{{ $totalParts }}</span>
                                <span class="text-zinc-400">|</span>
                                <span>{{ $progress }}%</span>
                            </div>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-full bg-blue-600 transition-all" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    @if(!empty($restoreStatus['error']))
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                            {{ $restoreStatus['error'] }}
                        </div>
                    @endif

                    <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-[#050816] shadow-[0_20px_80px_-40px_rgba(15,23,42,0.9)]">
                        <div class="border-b border-zinc-800 bg-[radial-gradient(circle_at_top_left,_rgba(56,189,248,0.18),_transparent_34%),linear-gradient(180deg,_rgba(15,23,42,0.98),_rgba(2,6,23,0.96))]">
                            <div class="flex items-center justify-between gap-3 px-3 py-2.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-2">
                                        <span class="h-2.5 w-2.5 rounded-full bg-red-400 shadow-[0_0_12px_rgba(248,113,113,0.65)]"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400 shadow-[0_0_12px_rgba(251,191,36,0.65)]"></span>
                                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_12px_rgba(52,211,153,0.65)]"></span>
                                    </div>
                                    <div>
                                        <p class="font-mono text-[10px] uppercase tracking-[0.24em] text-zinc-400">restore@fitcenter terminal</p>
                                        <p class="mt-0.5 text-[10px] text-zinc-500">Monitoreo operativo en tiempo real</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <span
                                        x-show="newLines"
                                        x-cloak
                                        class="rounded-full border border-sky-400/30 bg-sky-500/10 px-2 py-0.5 text-[10px] font-medium text-sky-300"
                                    >
                                        Nuevas lineas disponibles
                                    </span>
                                    <button
                                        type="button"
                                        class="rounded-lg border border-zinc-700 bg-zinc-900/60 px-2 py-1 text-[10px] font-medium text-zinc-200 transition hover:border-zinc-600 hover:bg-zinc-800"
                                        x-on:click="scrollToEnd()"
                                    >
                                        Volver al final
                                    </button>
                                </div>
                            </div>

                            <div class="border-t border-zinc-800/90 px-3 py-2 font-mono text-[10px] text-zinc-500">
                                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                    <span>id={{ $restoreJobId ?? '-' }}</span>
                                    <span>file={{ $restoreStatus['original_name'] ?? '-' }}</span>
                                    @if(!empty($restoreStatus['manifest_filename']))
                                        <span>manifest={{ $restoreStatus['manifest_filename'] }}</span>
                                    @endif
                                    @if(!empty($restoreStatus['part_count']))
                                        <span>lote={{ $restoreStatus['part_count'] }} parte(s)</span>
                                    @endif
                                    <span>start={{ $restoreStatus['started_at'] ?? '-' }}</span>
                                    <span>last={{ $restoreStatus['last_event_at'] ?? '-' }}</span>
                                    <span>admin={{ !empty($restoreStatus['super_admin_restored']) ? 'ok' : 'pending' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-zinc-800">
                            <div class="relative min-w-0 overflow-hidden bg-[linear-gradient(180deg,_rgba(2,6,23,0.96),_rgba(3,7,18,1))]">
                                <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_bottom,_rgba(255,255,255,0.02)_1px,_transparent_1px)] bg-[size:100%_28px] opacity-40"></div>
                                <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(56,189,248,0.08),_transparent_36%)]"></div>

                                <div class="border-b border-zinc-800/90 px-3 py-2 font-mono text-[10px] text-zinc-500">
                                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                                        <span>archivo={{ $restoreStatus['original_name'] ?? '-' }}</span>
                                        <span>launcher={{ $platformLauncher }}</span>
                                        <span>modo={{ $estimatedMode }}</span>
                                        <span>estado={{ $restoreStatus['status'] ?? 'sin_estado' }}</span>
                                    </div>
                                </div>

                                <div x-ref="term" @scroll="handleScroll()" class="max-h-[24rem] overflow-auto px-3 py-3 font-mono text-[11px] leading-5 text-zinc-100">
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
                                            $badgeClass = match ($level) {
                                                'success' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300',
                                                'error' => 'border-rose-500/30 bg-rose-500/10 text-rose-300',
                                                'warn' => 'border-amber-500/30 bg-amber-500/10 text-amber-300',
                                                'phase' => 'border-sky-500/30 bg-sky-500/10 text-sky-300',
                                                'command' => 'border-violet-500/30 bg-violet-500/10 text-violet-300',
                                                default => 'border-zinc-700 bg-zinc-900/60 text-zinc-300',
                                            };
                                            $prompt = match ($level) {
                                                'command' => 'sql@restore:~$',
                                                'error' => 'error@restore:~$',
                                                'warn' => 'warn@restore:~$',
                                                default => 'restore@fitcenter:~$',
                                            };
                                        @endphp
                                        <div class="grid grid-cols-[2.25rem_minmax(0,1fr)] gap-2 border-l border-zinc-800/80 pl-2.5">
                                            <div class="pt-0.5 text-right text-[9px] text-zinc-600">{{ $loop->iteration }}</div>
                                            <div class="pb-1.5">
                                                <div class="flex flex-wrap items-center gap-1.5">
                                                    <span class="text-zinc-500">[{{ $event['timestamp'] ?? '--' }}]</span>
                                                    <span class="text-zinc-400">{{ $prompt }}</span>
                                                    <span class="inline-flex rounded-full border px-1.5 py-0.5 text-[9px] font-semibold uppercase tracking-[0.14em] {{ $badgeClass }}">
                                                        {{ $level }}
                                                    </span>
                                                    @if(!empty($event['stage']))
                                                        <span class="text-[9px] uppercase tracking-[0.12em] text-zinc-500">{{ str_replace('_', ' ', $event['stage']) }}</span>
                                                    @endif
                                                </div>

                                                <div class="mt-0.5 {{ $lineClass }}">
                                                    {{ $event['message'] ?? '' }}
                                                </div>

                                                @if(!empty($event['command']))
                                                    <div class="mt-1.5 overflow-hidden rounded-lg border border-violet-500/20 bg-violet-500/5">
                                                        <div class="border-b border-violet-500/10 px-2.5 py-1 text-[9px] uppercase tracking-[0.14em] text-violet-300">
                                                            comando
                                                        </div>
                                                        <div class="break-all px-2.5 py-1.5 text-violet-200">
                                                            {{ $event['command'] }}
                                                        </div>
                                                    </div>
                                                @endif

                                                @if(!empty($event['statement_index']) || !empty($event['part']) || isset($event['progress']))
                                                    <div class="mt-1.5 flex flex-wrap gap-1.5 text-[9px] uppercase tracking-[0.12em] text-zinc-500">
                                                        @if(!empty($event['statement_index']))
                                                            <span class="rounded-full border border-zinc-800 bg-zinc-950/80 px-1.5 py-0.5">
                                                                sentencia {{ $event['statement_index'] }} / {{ $event['total_statements'] ?? '?' }}
                                                            </span>
                                                        @endif
                                                        @if(!empty($event['part']))
                                                            <span class="rounded-full border border-zinc-800 bg-zinc-950/80 px-1.5 py-0.5">
                                                                parte {{ $event['part'] }} / {{ $event['total_parts'] ?? '?' }}
                                                            </span>
                                                        @endif
                                                        @if(isset($event['progress']) && $event['progress'] !== null)
                                                            <span class="rounded-full border border-zinc-800 bg-zinc-950/80 px-1.5 py-0.5">
                                                                {{ $event['progress'] }}% completado
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="flex min-h-[16rem] items-center justify-center text-zinc-500">
                                            Esperando eventos de restauracion...
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($restoreIsDone)
                        <div @class([
                            'rounded-lg p-2.5 text-[11px]',
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
                            <div class="mt-1.5 flex flex-wrap gap-x-3 gap-y-1">
                                <div>Duracion: <span class="font-semibold">{{ $durationSeconds !== null ? $durationSeconds.' s' : '-' }}</span></div>
                                <div>Sentencias: <span class="font-semibold">{{ $restoreStatus['executed_statements'] ?? 0 }}</span></div>
                                <div>Partes: <span class="font-semibold">{{ $currentPart }} / {{ $totalParts }}</span></div>
                                <div>Modo: <span class="font-semibold uppercase">{{ $estimatedMode }}</span></div>
                                <div>Super-admin: <span class="font-semibold">{{ !empty($restoreStatus['super_admin_restored']) ? 'OK' : 'Pendiente' }}</span></div>
                            </div>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <div class="text-[10px] text-zinc-500 dark:text-zinc-400">
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
