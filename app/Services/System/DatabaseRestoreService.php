<?php

namespace App\Services\System;

use Database\Seeders\AdminUserSeeder;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use RuntimeException;

class DatabaseRestoreService
{
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
            'cancel_requested' => false,
            'progress' => 0,
            'executed_statements' => 0,
            'total_statements' => 0,
            'current_part' => 0,
            'total_parts' => 0,
            'is_large_restore' => false,
            'file_size_bytes' => File::size($storedAbsolute),
            'current_command' => null,
            'current_step' => 'En cola',
            'file_path' => $storedAbsolute,
            'original_name' => $file->getClientOriginalName(),
            'error' => null,
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now()->toDateTimeString(),
        ];

        $this->writeStatus($restoreId, $status);
        $this->appendLog($restoreId, 'Restore en cola para archivo: '.$file->getClientOriginalName());
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
        $status['current_step'] = 'Inicializando restauracion';
        $status['started_at'] = now()->toDateTimeString();
        $status['error'] = null;
        $this->writeStatus($restoreId, $status);
        $this->appendLog($restoreId, 'Inicio de restauracion.');

        try {
            $this->backupService->restoreFromPath($filePath, function (array $progress) use ($restoreId): void {
                $status = $this->readStatusOrFail($restoreId);
                if (($status['cancel_requested'] ?? false) === true) {
                    $status['status'] = 'cancelled';
                    $status['current_step'] = 'Cancelando restauracion';
                    $status['finished_at'] = now()->toDateTimeString();
                    $status['error'] = null;
                    $this->writeStatus($restoreId, $status);
                    $this->appendLog($restoreId, 'Cancelacion solicitada por el usuario.');

                    throw new RestoreCancelledException('Restauracion cancelada por el usuario.');
                }

                $status['status'] = 'running';
                $status['progress'] = $progress['progress'];
                $status['executed_statements'] = $progress['executed_statements'];
                $status['total_statements'] = $progress['total_statements'];
                $status['current_part'] = $progress['current_part'] ?? $status['current_part'] ?? 0;
                $status['total_parts'] = $progress['total_parts'] ?? $status['total_parts'] ?? 0;
                $status['is_large_restore'] = $progress['is_large_restore'] ?? $status['is_large_restore'] ?? false;
                $status['file_size_bytes'] = $progress['file_size_bytes'] ?? $status['file_size_bytes'] ?? null;
                $status['current_command'] = $progress['current_command'];
                $status['current_step'] = $progress['current_step'];
                $this->writeStatus($restoreId, $status);

                if (! empty($progress['log'])) {
                    $this->appendLog($restoreId, $progress['log']);
                }
            });

            $status = $this->readStatusOrFail($restoreId);
            $status['current_step'] = 'Restaurando credenciales del super-admin';
            $status['current_command'] = 'Database\\Seeders\\BaseCatalogSeeder + Database\\Seeders\\AdminUserSeeder';
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restaurando catalogos base y credenciales del super-admin.');

            $this->restoreSuperAdminCredentials();

            $status = $this->readStatusOrFail($restoreId);
            $status['status'] = 'completed';
            $status['progress'] = 100;
            $status['current_step'] = 'Restauracion completada';
            $status['current_part'] = $status['total_parts'] ?? $status['current_part'] ?? 0;
            $status['finished_at'] = now()->toDateTimeString();
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restauracion completada correctamente.');
        } catch (RestoreCancelledException $e) {
            $status = $this->readStatusOrFail($restoreId);
            $status['status'] = 'cancelled';
            $status['current_step'] = 'Restauracion cancelada';
            $status['finished_at'] = now()->toDateTimeString();
            $status['error'] = null;
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'Restauracion cancelada correctamente.');
        } catch (\Throwable $e) {
            $status = $this->readStatusOrFail($restoreId);
            $status['status'] = 'failed';
            $status['error'] = $e->getMessage();
            $status['current_step'] = 'Restauracion fallida';
            $status['finished_at'] = now()->toDateTimeString();
            $this->writeStatus($restoreId, $status);
            $this->appendLog($restoreId, 'ERROR: '.$e->getMessage());

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

    private function dispatchBackgroundRestore(string $restoreId): void
    {
        $phpBinary = PHP_BINARY;
        $artisanPath = base_path('artisan');
        $workingDirectory = base_path();

        if (DIRECTORY_SEPARATOR === '\\') {
            $launcherPath = $this->createWindowsLauncherScript($restoreId, $phpBinary, $artisanPath, $workingDirectory);
            $command = 'cmd /c start "" /B "'.$launcherPath.'"';
            $this->appendLog($restoreId, 'Lanzando proceso en segundo plano con script CMD.');
            pclose(popen($command, 'r'));

            return;
        }

        $launcherPath = $this->createUnixLauncherScript($restoreId, $phpBinary, $artisanPath, $workingDirectory);
        $launcher = escapeshellarg($launcherPath);
        $this->appendLog($restoreId, 'Lanzando proceso en segundo plano con nohup.');
        exec("nohup sh {$launcher} > /dev/null 2>&1 &");
    }

    private function restoreSuperAdminCredentials(): void
    {
        app(BaseCatalogSeeder::class)->run();
        app(AdminUserSeeder::class)->run();
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

    private function launcherScriptPath(string $restoreId): string
    {
        $extension = DIRECTORY_SEPARATOR === '\\' ? '.cmd' : '.sh';

        return $this->restoreDirectory().DIRECTORY_SEPARATOR.$restoreId.$extension;
    }

    private function createWindowsLauncherScript(string $restoreId, string $phpBinary, string $artisanPath, string $workingDirectory): string
    {
        $launcherPath = $this->launcherScriptPath($restoreId);
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
        $launcherPath = $this->launcherScriptPath($restoreId);
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
        File::put($this->statusPath($restoreId), json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    private function appendLog(string $restoreId, string $line): void
    {
        File::ensureDirectoryExists($this->restoreDirectory());
        File::append($this->logPath($restoreId), '['.now()->format('Y-m-d H:i:s').'] '.$line.PHP_EOL);
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
