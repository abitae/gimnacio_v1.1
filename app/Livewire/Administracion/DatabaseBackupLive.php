<?php

namespace App\Livewire\Administracion;

use App\Livewire\Concerns\FlashesToast;
use App\Services\System\DatabaseBackupService;
use App\Services\System\DatabaseRestoreService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class DatabaseBackupLive extends Component
{
    use FlashesToast;
    use WithFileUploads;

    public ?TemporaryUploadedFile $restoreFile = null;

    public string $restoreConfirmation = '';

    public array $backups = [];

    public ?string $restoreJobId = null;

    public ?array $restoreStatus = null;

    public array $restoreEvents = [];

    public int $restoreEventOffset = 0;

    public bool $showRestoreMonitorModal = false;

    public function mount(DatabaseBackupService $backupService, DatabaseRestoreService $restoreService): void
    {
        abort_unless(
            Auth::user()?->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME),
            403
        );

        $this->refreshBackups($backupService);
        $this->safeRefreshRestoreState($restoreService, true);
    }

    public function exportBackup(DatabaseBackupService $backupService)
    {
        $backup = $backupService->createBackup();
        $this->refreshBackups($backupService);
        $this->flashToast(
            'success',
            'Backup generado correctamente en un archivo ZIP. Descargalo desde la tabla de backups generados.'
        );
    }

    public function downloadBackup(string $filename, DatabaseBackupService $backupService)
    {
        $path = $backupService->absolutePathFor($filename);

        return response()->download($path, basename($path));
    }

    public function deleteBackup(string $filename, DatabaseBackupService $backupService): void
    {
        $backupService->deleteBackup($filename);
        $this->refreshBackups($backupService);
        $this->flashToast('success', 'Backup eliminado correctamente.');
    }

    public function restoreBackup(DatabaseRestoreService $restoreService): void
    {
        $maxKb = $this->restoreMaxUploadKb();

        $this->validate([
            'restoreFile' => ['required', 'file', 'mimes:zip,sql,txt', 'max:'.$maxKb],
            'restoreConfirmation' => 'required|string|in:RESTAURAR',
        ], [
            'restoreFile.required' => 'Debes seleccionar un ZIP o un archivo SQL y esperar a que termine de subirse.',
            'restoreFile.mimes' => 'El archivo debe ser .zip, .sql o .txt.',
            'restoreFile.max' => 'El archivo no puede superar '.config('backups.restore_max_mb').' MB.',
            'restoreConfirmation.in' => 'Debes escribir RESTAURAR para confirmar.',
        ]);

        if (! $this->restoreFile instanceof TemporaryUploadedFile) {
            $this->addError('restoreFile', 'Debes seleccionar un ZIP o un archivo SQL y esperar a que termine de subirse.');

            return;
        }

        $this->restoreJobId = $restoreService->queueRestoreFromUploadedFile($this->restoreFile);
        $this->restoreStatus = $restoreService->readStatus($this->restoreJobId);
        $this->restoreEvents = [];
        $this->restoreEventOffset = 0;
        $this->syncRestoreEvents($restoreService, true);
        $this->showRestoreMonitorModal = true;

        $this->reset(['restoreFile', 'restoreConfirmation']);
        $this->flashToast('success', 'Restauracion iniciada. Se mostrara el avance en esta pantalla.');
    }

    public function restoreGeneratedBackup(string $filename, DatabaseRestoreService $restoreService): void
    {
        $this->restoreJobId = $restoreService->queueRestoreFromBackupFilename($filename);
        $this->restoreStatus = $restoreService->readStatus($this->restoreJobId);
        $this->restoreEvents = [];
        $this->restoreEventOffset = 0;
        $this->syncRestoreEvents($restoreService, true);
        $this->showRestoreMonitorModal = true;

        $this->flashToast('success', 'Restauracion iniciada desde el backup generado. Se mostrara el avance en esta pantalla.');
    }

    public function refreshRestoreStatus(DatabaseRestoreService $restoreService): void
    {
        $this->safeRefreshRestoreState($restoreService);
    }

    public function openRestoreMonitorModal(DatabaseRestoreService $restoreService): void
    {
        $this->safeRefreshRestoreState($restoreService);
        $this->showRestoreMonitorModal = true;
    }

    private function safeRefreshRestoreState(DatabaseRestoreService $restoreService, bool $resetEvents = false): void
    {
        $previousRestoreId = $this->restoreJobId;

        try {
            if ($this->restoreJobId) {
                $restoreService->flagQueuedDelayWarning($this->restoreJobId);
            }

            $this->restoreStatus = $restoreService->readStatus($this->restoreJobId) ?? $restoreService->latestStatus();
            $this->restoreJobId = $this->restoreStatus['id'] ?? null;
            $this->syncRestoreEvents($restoreService, $resetEvents || $previousRestoreId !== $this->restoreJobId);
        } catch (\Throwable) {
            if ($resetEvents) {
                $this->restoreStatus = null;
                $this->restoreJobId = null;
                $this->restoreEvents = [];
                $this->restoreEventOffset = 0;
            }
        }
    }

    public function cancelRestore(DatabaseRestoreService $restoreService): void
    {
        if (! $this->restoreJobId) {
            $this->flashToast('error', 'No hay una restauracion activa para cancelar.');

            return;
        }

        try {
            $restoreService->cancelRestore($this->restoreJobId);
            $this->refreshRestoreStatus($restoreService);
            $this->flashToast('info', 'Se solicito cancelar la restauracion. El proceso se detendra en la siguiente sentencia segura.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeRestoreMonitorModal(): void
    {
        $this->showRestoreMonitorModal = false;
    }

    public function render()
    {
        return view('livewire.administracion.database-backup-live');
    }

    private function refreshBackups(DatabaseBackupService $backupService): void
    {
        $this->backups = $backupService->listBackups();
    }

    private function syncRestoreEvents(DatabaseRestoreService $restoreService, bool $reset = false): void
    {
        if (! $this->restoreJobId) {
            if ($reset) {
                $this->restoreEvents = [];
                $this->restoreEventOffset = 0;
            }

            return;
        }

        $afterOffset = $reset ? 0 : $this->restoreEventOffset;
        $payload = $restoreService->readEvents($this->restoreJobId, $afterOffset);
        $events = $payload['events'] ?? [];

        if ($reset) {
            $this->restoreEvents = $events;
        } elseif ($events !== []) {
            $this->restoreEvents = array_slice(array_merge($this->restoreEvents, $events), -500);
        }

        $this->restoreEventOffset = (int) ($payload['next_offset'] ?? $this->restoreEventOffset);
    }

    private function restoreMaxUploadKb(): int
    {
        return max(1, (int) config('backups.restore_max_mb')) * 1024;
    }
}
