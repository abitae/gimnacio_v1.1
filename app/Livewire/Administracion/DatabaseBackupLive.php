<?php

namespace App\Livewire\Administracion;

use App\Livewire\Concerns\FlashesToast;
use App\Services\System\DatabaseBackupService;
use App\Services\System\DatabaseRestoreService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class DatabaseBackupLive extends Component
{
    use FlashesToast;
    use WithFileUploads;

    public $restoreFile = null;
    public string $restoreConfirmation = '';
    public array $backups = [];
    public ?string $restoreJobId = null;
    public ?array $restoreStatus = null;
    public string $restoreLog = '';
    public bool $showRestoreMonitorModal = false;

    public function mount(DatabaseBackupService $backupService, DatabaseRestoreService $restoreService): void
    {
        abort_unless(
            Auth::user()?->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME),
            403
        );

        $this->refreshBackups($backupService);
        $this->restoreStatus = $restoreService->latestStatus();
        $this->restoreJobId = $this->restoreStatus['id'] ?? null;
        $this->restoreLog = $restoreService->readLog($this->restoreJobId);
    }

    public function exportBackup(DatabaseBackupService $backupService)
    {
        $backup = $backupService->createBackup();
        $this->refreshBackups($backupService);
        $this->flashToast('success', 'Backup generado correctamente.');

        return response()->download($backup['path'], $backup['filename']);
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
        $this->validate([
            'restoreFile' => 'required|file|max:512000',
            'restoreConfirmation' => 'required|string|in:RESTAURAR',
        ], [
            'restoreFile.required' => 'Debes seleccionar un archivo SQL.',
            'restoreConfirmation.in' => 'Debes escribir RESTAURAR para confirmar.',
        ]);

        $this->restoreJobId = $restoreService->queueRestoreFromUploadedFile($this->restoreFile);
        $this->restoreStatus = $restoreService->readStatus($this->restoreJobId);
        $this->restoreLog = $restoreService->readLog($this->restoreJobId);
        $this->showRestoreMonitorModal = true;

        $this->reset(['restoreFile', 'restoreConfirmation']);
        $this->flashToast('success', 'Restauracion iniciada. Se mostrara el avance en esta pantalla.');
    }

    public function refreshRestoreStatus(DatabaseRestoreService $restoreService): void
    {
        $this->restoreStatus = $restoreService->readStatus($this->restoreJobId) ?? $restoreService->latestStatus();
        $this->restoreJobId = $this->restoreStatus['id'] ?? null;
        $this->restoreLog = $restoreService->readLog($this->restoreJobId);
    }

    public function openRestoreMonitorModal(DatabaseRestoreService $restoreService): void
    {
        $this->refreshRestoreStatus($restoreService);
        $this->showRestoreMonitorModal = true;
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
}
