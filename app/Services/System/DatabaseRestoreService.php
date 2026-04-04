<?php

namespace App\Services\System;

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DatabaseRestoreService
{
    private const QUEUE_WARNING_SECONDS = 8;

    public function __construct(
        private readonly DatabaseBackupService $backupService,
    ) {}

    public function queueRestoreFromUploadedFile(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['sql', 'txt'], true)) {
            throw new RuntimeException('Solo se permiten archivos .sql o .txt.');
        }

        $restoreId = uniqid('restore_', true);
        File::ensureDirectoryExists($this->restoreDirectory());
        $uploadsDirectory = $this->uploadsDirectory();
        File::ensureDirectoryExists($uploadsDirectory);
        $storedAbsolute = $uploadsDirectory.DIRECTORY_SEPARATOR.$restoreId.'.sql';
        File::copy($file->getRealPath(), $storedAbsolute);

        $status = [
            'id' => $restoreId,
            'status' => 'queued',
            'stage' => 'queued',
            'cancel_requested' => false,
            'queue_warning_emitted' => false,
            'progress' => 0,
            'executed_statements' => 0,
            'total_statements' => 0,
            'current_part' => 0,
            'total_parts' => 0,
            'is_large_restore' => false,
            'estimated_mode' => File::size($storedAbsolute) >= (5 * 1024 * 1024) ? 'chunked' : 'single_part',
            'platform_launcher' => DIRECTORY_SEPARATOR === '\\' ? 'windows_cmd' : 'unix_sh',
            'file_size_bytes' => File::size($storedAbsolute),
            'current_command' => null,
            'current_step' => 'En cola',
            'file_path' => $storedAbsolute,
            'original_name' => $file->getClientOriginalName(),
            'error' => null,
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now()->toDateTimeString(),
            'last_event_at' => now()->toDateTimeString(),
        ];

        $this->writeStatus($restoreId, $status);
        $this->appendLog($restoreId, 'Restore en cola para archivo: '.$file->getClientOriginalName());
        $this->appendEvent($restoreId, [
            'level' => 'phase',
            'stage' => 'queued',
            'message' => 'Restore en cola para archivo: '.$file->getClientOriginalName(),
            'progress' => 0,
        ]);
        $this->dispatchBackgroundRestore($restoreId);

        return $restoreId;
    }

    public function runRestore(string $restoreId): void
    {
        $status = $this->readStatusOrFail($restoreId);
        $filePath = $status['file_path'] ?? null;

        if (! is_string($filePath) || ! File::exists($filePath)) {
            throw new RuntimeException('No se encontro el archivo temporal de restauracion.');
        }

        if (($status['cancel_requested'] ?? false) === true) {
            $status['status'] = 'cancelled';
            $status['current_step'] = 'Restauracion cancelada antes de iniciar';
            $status['finished_at'] = now()->toDateTimeString();
            $status['error'] = null;
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restauracion cancelada antes de iniciar.');

            File::delete($filePath);

            return;
        }

        $status['status'] = 'running';
        $status['stage'] = 'running';
        $status['current_step'] = 'Inicializando restauracion';
        $status['started_at'] = now()->toDateTimeString();
        $status['error'] = null;
        $this->writeStatus($restoreId, $status);
        $this->appendLog($restoreId, 'Inicio de restauracion.');
        $this->appendEvent($restoreId, [
            'level' => 'phase',
            'stage' => 'running',
            'message' => 'Inicio de restauracion.',
            'progress' => 0,
        ]);

        try {
            $this->backupService->restoreFromPath($filePath, function (array $progress) use ($restoreId): void {
                $status = $this->readStatusOrFail($restoreId);
                if (($status['cancel_requested'] ?? false) === true) {
                    $status['status'] = 'cancelled';
                    $status['stage'] = 'cancelled';
                    $status['current_step'] = 'Cancelando restauracion';
                    $status['finished_at'] = now()->toDateTimeString();
                    $status['error'] = null;
                    $this->writeStatus($restoreId, $status);
                    $this->appendLog($restoreId, 'Cancelacion solicitada por el usuario.');
                    $this->appendEvent($restoreId, [
                        'level' => 'warn',
                        'stage' => 'cancel_requested',
                        'message' => 'Cancelacion solicitada por el usuario.',
                        'progress' => $status['progress'] ?? 0,
                    ]);

                    throw new RestoreCancelledException('Restauracion cancelada por el usuario.');
                }

                $updatedStatus = $status;
                $updatedStatus['status'] = 'running';
                $updatedStatus['stage'] = $progress['stage'] ?? 'executing_sql';
                $updatedStatus['progress'] = $progress['progress'];
                $updatedStatus['executed_statements'] = $progress['executed_statements'];
                $updatedStatus['total_statements'] = $progress['total_statements'];
                $updatedStatus['current_part'] = $progress['current_part'] ?? $status['current_part'] ?? 0;
                $updatedStatus['total_parts'] = $progress['total_parts'] ?? $status['total_parts'] ?? 0;
                $updatedStatus['is_large_restore'] = $progress['is_large_restore'] ?? $status['is_large_restore'] ?? false;
                $updatedStatus['file_size_bytes'] = $progress['file_size_bytes'] ?? $status['file_size_bytes'] ?? null;
                $updatedStatus['current_command'] = $progress['current_command'];
                $updatedStatus['current_step'] = $progress['current_step'];
                $updatedStatus['estimated_mode'] = ! empty($updatedStatus['is_large_restore']) ? 'chunked' : 'single_part';

                if ($this->shouldPersistProgress($status, $updatedStatus)) {
                    $this->writeStatus($restoreId, $updatedStatus);
                }

                if (! empty($progress['log'])) {
                    $this->appendLog($restoreId, $progress['log']);
                }

                $eventLevel = ! empty($progress['current_command']) ? 'command' : 'info';
                $this->appendEvent($restoreId, [
                    'level' => $eventLevel,
                    'stage' => $progress['stage'] ?? 'executing_sql',
                    'message' => $progress['current_step'],
                    'command' => $progress['current_command'] ?? null,
                    'progress' => $progress['progress'] ?? null,
                    'part' => $progress['current_part'] ?? null,
                    'total_parts' => $progress['total_parts'] ?? null,
                    'statement_index' => $progress['statement_index'] ?? null,
                    'total_statements' => $progress['total_statements'] ?? null,
                ]);
            });

            $status = $this->readStatusOrFail($restoreId);
            $status['stage'] = 'restoring_super_admin';
            $status['current_step'] = 'Restaurando credenciales del super-admin';
            $status['current_command'] = 'Database\\Seeders\\BaseCatalogSeeder + Database\\Seeders\\AdminUserSeeder';
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restaurando catalogos base y credenciales del super-admin.');
            $this->appendEvent($restoreId, [
                'level' => 'phase',
                'stage' => 'restoring_super_admin',
                'message' => 'Restaurando catalogos base y credenciales del super-admin.',
                'command' => 'Database\\Seeders\\BaseCatalogSeeder + Database\\Seeders\\AdminUserSeeder',
                'progress' => $status['progress'] ?? null,
            ]);

            $this->restoreSuperAdminCredentials($restoreId);

            $status = $this->readStatusOrFail($restoreId);
            $status['status'] = 'completed';
            $status['stage'] = 'completed';
            $status['progress'] = 100;
            $status['current_step'] = 'Restauracion completada';
            $status['current_part'] = $status['total_parts'] ?? $status['current_part'] ?? 0;
            $status['finished_at'] = now()->toDateTimeString();
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restauracion completada correctamente.');
            $this->appendEvent($restoreId, [
                'level' => 'success',
                'stage' => 'completed',
                'message' => 'Restauracion completada correctamente.',
                'progress' => 100,
            ]);
        } catch (RestoreCancelledException $e) {
            $status = $this->readStatusOrFail($restoreId);
            $status['status'] = 'cancelled';
            $status['stage'] = 'cancelled';
            $status['current_step'] = 'Restauracion cancelada';
            $status['finished_at'] = now()->toDateTimeString();
            $status['error'] = null;
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restauracion cancelada correctamente.');
            $this->appendEvent($restoreId, [
                'level' => 'warn',
                'stage' => 'cancelled',
                'message' => 'Restauracion cancelada correctamente.',
                'progress' => $status['progress'] ?? null,
            ]);
        } catch (\Throwable $e) {
            $status = $this->readStatusOrFail($restoreId);
            $status['status'] = 'failed';
            $status['stage'] = 'failed';
            $status['error'] = $e->getMessage();
            $status['current_step'] = 'Restauracion fallida';
            $status['finished_at'] = now()->toDateTimeString();
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'ERROR: '.$e->getMessage());
            $this->appendEvent($restoreId, [
                'level' => 'error',
                'stage' => 'failed',
                'message' => $e->getMessage(),
                'progress' => $status['progress'] ?? null,
            ]);

            throw $e;
        } finally {
            if (isset($filePath) && is_string($filePath)) {
                File::delete($filePath);
            }

            File::delete($this->launcherScriptPath($restoreId));
        }
    }

    public function cancelRestore(string $restoreId): void
    {
        $status = $this->readStatusOrFail($restoreId);
        $currentStatus = $status['status'] ?? null;

        if (! in_array($currentStatus, ['queued', 'running'], true)) {
            throw new RuntimeException('Solo se puede cancelar una restauracion en cola o en ejecucion.');
        }

        if (($status['cancel_requested'] ?? false) === true) {
            return;
        }

        $status['cancel_requested'] = true;
        $status['current_step'] = $currentStatus === 'queued'
            ? 'Cancelacion solicitada antes de iniciar'
            : 'Cancelacion solicitada';
        $this->writeStatus($restoreId, $status);
        $this->appendLog($restoreId, 'Se solicito cancelar la restauracion.');
        $this->appendEvent($restoreId, [
            'level' => 'warn',
            'stage' => 'cancel_requested',
            'message' => 'Se solicito cancelar la restauracion.',
            'progress' => $status['progress'] ?? null,
        ]);
    }

    public function latestStatus(): ?array
    {
        $files = collect(File::glob($this->restoreDirectory().DIRECTORY_SEPARATOR.'*.json'))
            ->sortDesc()
            ->values();

        if ($files->isEmpty()) {
            return null;
        }

        return $this->readStatus(basename((string) $files->first(), '.json'));
    }

    public function readStatus(?string $restoreId): ?array
    {
        if (! $restoreId) {
            return null;
        }

        $path = $this->statusPath($restoreId);
        if (! File::exists($path)) {
            return null;
        }

        $decoded = json_decode(File::get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    public function readLog(?string $restoreId, int $maxLines = 200): string
    {
        if (! $restoreId) {
            return '';
        }

        $path = $this->logPath($restoreId);
        if (! File::exists($path)) {
            return '';
        }

        $content = File::get($path);
        $lines = preg_split("/\\r\\n|\\n|\\r/", $content) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line) => $line !== ''));

        return implode(PHP_EOL, array_slice($lines, -1 * $maxLines));
    }

    /**
     * @return array{events:list<array<string, mixed>>,next_offset:int}
     */
    public function readEvents(?string $restoreId, ?int $afterOffset = null, int $limit = 250): array
    {
        if (! $restoreId) {
            return ['events' => [], 'next_offset' => 0];
        }

        $path = $this->eventsPath($restoreId);
        if (! File::exists($path)) {
            return ['events' => [], 'next_offset' => 0];
        }

        $lines = preg_split("/\\r\\n|\\n|\\r/", File::get($path)) ?: [];
        $lines = array_values(array_filter($lines, fn (string $line) => trim($line) !== ''));
        $start = max(0, (int) ($afterOffset ?? 0));
        $slice = array_slice($lines, $start, $limit);

        $events = array_values(array_filter(array_map(function (string $line): ?array {
            $decoded = json_decode($line, true);

            return is_array($decoded) ? $decoded : null;
        }, $slice)));

        return [
            'events' => $events,
            'next_offset' => $start + count($slice),
        ];
    }

    public function flagQueuedDelayWarning(string $restoreId, int $seconds = self::QUEUE_WARNING_SECONDS): void
    {
        $status = $this->readStatus($restoreId);
        if (! is_array($status)) {
            return;
        }

        if (($status['status'] ?? null) !== 'queued' || ($status['queue_warning_emitted'] ?? false) === true) {
            return;
        }

        $createdAt = isset($status['created_at']) ? strtotime((string) $status['created_at']) : false;
        if (! is_int($createdAt) || $createdAt <= 0) {
            return;
        }

        if ((time() - $createdAt) < $seconds) {
            return;
        }

        $status['queue_warning_emitted'] = true;
        $status['current_step'] = 'Aun en cola, esperando que el proceso de fondo inicie';
        $this->writeStatus($restoreId, $status);
        $this->appendLog($restoreId, 'WARN: la restauracion sigue en cola mas tiempo de lo esperado.');
        $this->appendEvent($restoreId, [
            'level' => 'warn',
            'stage' => 'launching_background_process',
            'message' => 'La restauracion sigue en cola mas tiempo de lo esperado. Revise el lanzador en segundo plano.',
            'progress' => 0,
        ]);
    }

    private function dispatchBackgroundRestore(string $restoreId): void
    {
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');
        $workingDirectory = base_path();

        if (DIRECTORY_SEPARATOR === '\\') {
            $launcherPath = $this->createWindowsLauncherScript($restoreId, $phpBinary, $artisanPath, $workingDirectory);
            $command = 'cmd /c start "" /B "'.$launcherPath.'"';
            $this->appendLog($restoreId, 'Lanzando proceso en segundo plano con script CMD.');
            $this->appendEvent($restoreId, [
                'level' => 'phase',
                'stage' => 'launching_background_process',
                'message' => 'Lanzando proceso en segundo plano con script CMD.',
                'command' => $command,
                'progress' => 0,
            ]);
            pclose(popen($command, 'r'));

            return;
        }

        $launcherPath = $this->createUnixLauncherScript($restoreId, $phpBinary, $artisanPath, $workingDirectory);
        $launcher = escapeshellarg($launcherPath);
        $this->appendLog($restoreId, 'Lanzando proceso en segundo plano con nohup.');
        $this->appendEvent($restoreId, [
            'level' => 'phase',
            'stage' => 'launching_background_process',
            'message' => 'Lanzando proceso en segundo plano con nohup.',
            'command' => "nohup sh {$launcher} > /dev/null 2>&1 &",
            'progress' => 0,
        ]);
        exec("nohup sh {$launcher} > /dev/null 2>&1 &");
    }

    private function restoreSuperAdminCredentials(string $restoreId): void
    {
        $this->appendLog($restoreId, 'Sincronizando catalogos base.');
        app(BaseCatalogSeeder::class)->run();

        $this->appendLog($restoreId, 'Creando o actualizando usuario super-admin.');
        $user = app(AdminUserSeeder::class)->upsertAdmin();

        if (! $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
            throw new RuntimeException('No se pudo asignar el rol super-admin al usuario restaurado.');
        }

        if ($user->estado !== 'activo') {
            throw new RuntimeException('El usuario super-admin restaurado no quedo activo.');
        }

        if ($user->email !== AdminUserSeeder::ADMIN_EMAIL) {
            throw new RuntimeException('El usuario restaurado no coincide con el email canonico del super-admin.');
        }

        if ($user->email_verified_at === null) {
            throw new RuntimeException('El usuario super-admin restaurado no quedo con email verificado.');
        }

        $this->appendLog(
            $restoreId,
            'Super-admin restaurado correctamente: '.$user->email.' (id '.$user->id.').'
        );
        $this->appendEvent($restoreId, [
            'level' => 'success',
            'stage' => 'restoring_super_admin',
            'message' => 'Super-admin restaurado correctamente: '.$user->email.' (id '.$user->id.').',
            'progress' => null,
        ]);
    }

    private function restoreDirectory(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    }

    private function uploadsDirectory(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads');
    }

    private function statusPath(string $restoreId): string
    {
        return $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.'.json';
    }

    private function logPath(string $restoreId): string
    {
        return $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.'.log';
    }

    private function eventsPath(string $restoreId): string
    {
        return $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.'.jsonl';
    }

    private function launcherScriptPath(string $restoreId): string
    {
        $extension = DIRECTORY_SEPARATOR === '\\' ? '.cmd' : '.sh';

        return $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.$extension;
    }

    private function createWindowsLauncherScript(string $restoreId, string $phpBinary, string $artisanPath, string $workingDirectory): string
    {
        $launcherPath = $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.'.cmd';
        $script = implode(PHP_EOL, [
            '@echo off',
            'cd /d "'.$workingDirectory.'"',
            '"'.$phpBinary.'" "'.$artisanPath.'" backup:restore-run --id="'.$restoreId.'"',
            '',
        ]);

        File::put($launcherPath, $script);

        return $launcherPath;
    }

    private function createUnixLauncherScript(string $restoreId, string $phpBinary, string $artisanPath, string $workingDirectory): string
    {
        $launcherPath = $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.'.sh';
        $script = implode(PHP_EOL, [
            '#!/usr/bin/env sh',
            'cd '.escapeshellarg($workingDirectory),
            escapeshellarg($phpBinary).' '.escapeshellarg($artisanPath).' backup:restore-run --id='.escapeshellarg($restoreId),
            '',
        ]);

        File::put($launcherPath, $script);
        @chmod($launcherPath, 0755);

        return $launcherPath;
    }

    /**
     * @param  array<string, mixed>  $status
     */
    private function writeStatus(string $restoreId, array $status): void
    {
        File::ensureDirectoryExists($this->restoreDirectory());
        $status['last_event_at'] ??= now()->toDateTimeString();
        File::put($this->statusPath($restoreId), json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function appendLog(string $restoreId, string $line): void
    {
        File::ensureDirectoryExists($this->restoreDirectory());
        File::append($this->logPath($restoreId), '['.now()->format('Y-m-d H:i:s').'] '.$line.PHP_EOL);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function appendEvent(string $restoreId, array $event): void
    {
        File::ensureDirectoryExists($this->restoreDirectory());

        $payload = [
            'timestamp' => now()->toDateTimeString(),
            'level' => $event['level'] ?? 'info',
            'stage' => $event['stage'] ?? null,
            'message' => $event['message'] ?? '',
            'command' => $event['command'] ?? null,
            'progress' => $event['progress'] ?? null,
            'part' => $event['part'] ?? null,
            'total_parts' => $event['total_parts'] ?? null,
            'statement_index' => $event['statement_index'] ?? null,
            'total_statements' => $event['total_statements'] ?? null,
        ];

        File::append($this->eventsPath($restoreId), json_encode($payload, JSON_UNESCAPED_UNICODE).PHP_EOL);

        $status = $this->readStatus($restoreId);
        if (is_array($status)) {
            $status['last_event_at'] = $payload['timestamp'];
            $this->writeStatus($restoreId, $status);
        }
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $next
     */
    private function shouldPersistProgress(array $current, array $next): bool
    {
        foreach ([
            'status',
            'stage',
            'progress',
            'executed_statements',
            'total_statements',
            'current_part',
            'total_parts',
            'current_command',
            'current_step',
            'is_large_restore',
            'estimated_mode',
        ] as $key) {
            if (($current[$key] ?? null) !== ($next[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function readStatusOrFail(string $restoreId): array
    {
        $status = $this->readStatus($restoreId);
        if (! is_array($status)) {
            throw new RuntimeException('No se encontro el estado de restauracion solicitado.');
        }

        return $status;
    }
}

class RestoreCancelledException extends RuntimeException {}
