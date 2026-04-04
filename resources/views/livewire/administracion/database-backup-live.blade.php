@php
    $restoreIsRunning = in_array($restoreStatus['status'] ?? null, ['queued', 'running'], true);
    $restoreIsDone = in_array($restoreStatus['status'] ?? null, ['completed', 'failed', 'cancelled'], true);
    $cancelRequested = (bool) ($restoreStatus['cancel_requested'] ?? false);
    $progress = (int) ($restoreStatus['progress'] ?? 0);
@endphp

<div class="space-y-4 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Backups de base de datos</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Exporta un backup completo en SQL y restaura toda la base desde un archivo generado por el sistema.</p>
        </div>

        <div class="flex items-center gap-2">
            @if($restoreStatus)
                <flux:button icon="command-line" variant="ghost" size="xs" wire:click="openRestoreMonitorModal">
                    Ver ejecución
                </flux:button>
            @endif
            <flux:button icon="arrow-down-tray" color="purple" variant="primary" size="xs" wire:click="exportBackup" :disabled="$restoreIsRunning">
                Exportar backup
            </flux:button>
        </div>
    </div>

    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/40 dark:text-amber-200">
        <p class="font-semibold">Operación sensible</p>
        <p class="mt-1">La restauración reemplaza toda la base de datos actual. Antes de restaurar, exporta un backup reciente y asegúrate de que nadie esté operando el sistema.</p>
    </div>

    @if($restoreStatus)
        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Última restauración</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $restoreStatus['current_step'] ?? 'Sin actividad' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">{{ $progress }}%</span>
                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                        {{ ($restoreStatus['status'] ?? null) === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                        {{ ($restoreStatus['status'] ?? null) === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                        {{ ($restoreStatus['status'] ?? null) === 'cancelled' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                        {{ in_array($restoreStatus['status'] ?? null, ['queued', 'running'], true) ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
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
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tamaño</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha de creación</th>
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
                                        <flux:button size="xs" variant="ghost" color="red" icon="trash" wire:click="deleteBackup('{{ $backup['filename'] }}')" wire:confirm="¿Eliminar este backup SQL?" :disabled="$restoreIsRunning">
                                            Eliminar
                                        </flux:button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">Todavía no hay backups generados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Restaurar base de datos</h2>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Sube un archivo <span class="font-mono">.sql</span> exportado desde este módulo.</p>

            <form wire:submit="restoreBackup" class="mt-4 space-y-4">
                <flux:field>
                    <flux:label>Archivo SQL</flux:label>
                    <input type="file" wire:model="restoreFile" accept=".sql,.txt"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm file:mr-3 file:rounded-md file:border-0 file:bg-zinc-100 file:px-3 file:py-2 file:text-xs file:font-medium dark:border-zinc-600 dark:bg-zinc-900 dark:text-zinc-100 dark:file:bg-zinc-700" />
                    <flux:error name="restoreFile" />
                </flux:field>

                <flux:field>
                    <flux:label>Confirmación</flux:label>
                    <flux:input wire:model="restoreConfirmation" placeholder="Escribe RESTAURAR" />
                    <flux:error name="restoreConfirmation" />
                </flux:field>

                <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900/50 dark:bg-red-950/30 dark:text-red-300">
                    Esta acción elimina el estado actual y carga por completo el contenido del archivo.
                </div>

                <div class="flex justify-end">
                    <flux:button type="submit" color="red" variant="primary" icon="arrow-path" :disabled="$restoreIsRunning">
                        Restaurar base de datos
                    </flux:button>
                </div>
            </form>
        </div>
    </div>

    <flux:modal name="restore-monitor" wire:model="showRestoreMonitorModal" focusable class="max-w-5xl w-full">
        <div class="p-4 sm:p-5" @if($showRestoreMonitorModal && $restoreJobId) wire:poll.2s="refreshRestoreStatus" @endif>
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Ejecución de restauración</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Se muestran el avance y los comandos SQL ejecutados.</p>
                </div>
                @if($restoreStatus)
                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium
                        {{ ($restoreStatus['status'] ?? null) === 'completed' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                        {{ ($restoreStatus['status'] ?? null) === 'failed' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : '' }}
                        {{ ($restoreStatus['status'] ?? null) === 'cancelled' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : '' }}
                        {{ in_array($restoreStatus['status'] ?? null, ['queued', 'running'], true) ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : '' }}">
                        {{ strtoupper($restoreStatus['status'] ?? 'sin estado') }}
                    </span>
                @endif
            </div>

            @if($restoreStatus)
                <div class="mt-4 space-y-3">
                    <div class="flex items-center justify-between text-xs text-zinc-600 dark:text-zinc-400">
                        <span>{{ $restoreStatus['current_step'] ?? 'Sin actividad' }}</span>
                        <span>{{ $progress }}%</span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-700">
                        <div class="h-full bg-blue-600 transition-all" style="width: {{ $progress }}%"></div>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                            <p class="text-zinc-500 dark:text-zinc-400">Archivo</p>
                            <p class="mt-1 font-mono text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['original_name'] ?? '-' }}</p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                            <p class="text-zinc-500 dark:text-zinc-400">Sentencias</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['executed_statements'] ?? 0 }} / {{ $restoreStatus['total_statements'] ?? 0 }}</p>
                        </div>
                        <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                            <p class="text-zinc-500 dark:text-zinc-400">Inicio</p>
                            <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">{{ $restoreStatus['started_at'] ?? '-' }}</p>
                        </div>
                    </div>

                    @if(!empty($restoreStatus['is_large_restore']))
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-xs text-blue-900 dark:border-blue-900/40 dark:bg-blue-950/20 dark:text-blue-200">
                            <p class="font-semibold">Restauración por partes</p>
                            <p class="mt-1">
                                Archivo grande detectado. Se está ejecutando por partes para reducir el uso de memoria.
                                Parte {{ max(1, (int) ($restoreStatus['current_part'] ?? 1)) }}
                                de {{ max(1, (int) ($restoreStatus['total_parts'] ?? 1)) }}.
                            </p>
                        </div>
                    @endif

                    <div class="rounded-lg border border-zinc-200 p-3 text-xs dark:border-zinc-700">
                        <p class="text-zinc-500 dark:text-zinc-400">Comando actual</p>
                        <p class="mt-1 font-mono text-zinc-900 dark:text-zinc-100 break-all">{{ $restoreStatus['current_command'] ?? 'Aún no se ha ejecutado ningún comando.' }}</p>
                    </div>

                    @if($restoreIsRunning)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/40 dark:bg-amber-950/20">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div class="text-xs text-amber-900 dark:text-amber-200">
                                    <p class="font-semibold">Cancelar restauración</p>
                                    <p class="mt-1">
                                        @if($cancelRequested)
                                            Ya se solicitó la cancelación. El proceso se detendrá en cuanto termine la sentencia actual.
                                        @else
                                            La cancelación se aplica de forma segura entre sentencias SQL. Puede tardar unos segundos en reflejarse.
                                        @endif
                                    </p>
                                </div>
                                @if(!$cancelRequested)
                                    <flux:button
                                        color="amber"
                                        variant="primary"
                                        icon="stop"
                                        wire:click="cancelRestore"
                                        wire:confirm="¿Cancelar la restauración en curso?"
                                    >
                                        Cancelar restauración
                                    </flux:button>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                                        Cancelación solicitada
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endif

                    @if(!empty($restoreStatus['error']))
                        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-xs text-red-800 dark:border-red-900/40 dark:bg-red-950/20 dark:text-red-300">
                            {{ $restoreStatus['error'] }}
                        </div>
                    @endif

                    <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div class="mb-2 flex items-center justify-between">
                            <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300">Log de ejecución</p>
                            @if($restoreIsRunning)
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Actualización automática</span>
                            @elseif($restoreIsDone)
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">Ejecución finalizada</span>
                            @endif
                        </div>
                        <pre class="max-h-80 overflow-auto rounded-md bg-zinc-950 p-3 text-[11px] leading-5 text-zinc-100">{{ $restoreLog !== '' ? $restoreLog : 'Sin eventos registrados todavía.' }}</pre>
                    </div>
                </div>
            @else
                <div class="mt-4 rounded-lg border border-dashed border-zinc-300 p-4 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                    Aún no se ha iniciado ninguna restauración.
                </div>
            @endif

            <div class="mt-4 flex justify-end">
                <flux:button variant="ghost" wire:click="closeRestoreMonitorModal">
                    Cerrar
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
