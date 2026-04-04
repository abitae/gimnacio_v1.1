<?php

use App\Models\User;
use App\Services\System\DatabaseRestoreService;
use App\Support\PermissionCatalog;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
});

it('cancels a queued restore before execution starts', function () {
    $service = app(DatabaseRestoreService::class);

    $restoreId = 'restore_test_cancelled';
    $restoreDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    $uploadsDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads');
    File::ensureDirectoryExists($restoreDirectory);
    File::ensureDirectoryExists($uploadsDirectory);

    $filePath = $uploadsDirectory.DIRECTORY_SEPARATOR.$restoreId.'.sql';
    File::put($filePath, 'CREATE TABLE restore_cancel_test (id INTEGER);');

    $statusPath = $restoreDirectory.DIRECTORY_SEPARATOR.$restoreId.'.json';
    File::put($statusPath, json_encode([
        'id' => $restoreId,
        'status' => 'queued',
        'cancel_requested' => false,
        'progress' => 0,
        'executed_statements' => 0,
        'total_statements' => 0,
        'current_command' => null,
        'current_step' => 'En cola',
        'file_path' => $filePath,
        'original_name' => 'restore_cancel_test.sql',
        'error' => null,
        'started_at' => null,
        'finished_at' => null,
        'created_at' => now()->toDateTimeString(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $service->cancelRestore($restoreId);
    $service->runRestore($restoreId);

    $status = $service->readStatus($restoreId);

    expect($status)->not->toBeNull();
    expect($status['status'])->toBe('cancelled');
    expect($status['finished_at'])->not->toBeNull();
    expect($status['cancel_requested'])->toBeTrue();
    expect(File::exists($filePath))->toBeFalse();
});

it('restores super admin credentials after a successful restore', function () {
    $service = app(DatabaseRestoreService::class);

    $superAdmin = User::factory()->create([
        'name' => 'Super Admin Original',
        'email' => 'abel.arana@hotmail.com',
        'estado' => 'activo',
    ]);
    $superAdmin->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    $backupPath = app(\App\Services\System\DatabaseBackupService::class)->createBackup()['path'];
    User::query()->where('email', 'abel.arana@hotmail.com')->delete();

    $restoreId = 'restore_test_super_admin';
    $restoreDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    $uploadsDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads');
    File::ensureDirectoryExists($restoreDirectory);
    File::ensureDirectoryExists($uploadsDirectory);

    $filePath = $uploadsDirectory.DIRECTORY_SEPARATOR.$restoreId.'.sql';
    File::copy($backupPath, $filePath);

    $statusPath = $restoreDirectory.DIRECTORY_SEPARATOR.$restoreId.'.json';
    File::put($statusPath, json_encode([
        'id' => $restoreId,
        'status' => 'queued',
        'cancel_requested' => false,
        'progress' => 0,
        'executed_statements' => 0,
        'total_statements' => 0,
        'current_part' => 0,
        'total_parts' => 0,
        'is_large_restore' => false,
        'file_size_bytes' => File::size($filePath),
        'current_command' => null,
        'current_step' => 'En cola',
        'file_path' => $filePath,
        'original_name' => 'super_admin_restore.sql',
        'error' => null,
        'started_at' => null,
        'finished_at' => null,
        'created_at' => now()->toDateTimeString(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $service->runRestore($restoreId);

    $restoredAdmin = User::query()->where('email', 'abel.arana@hotmail.com')->first();
    $status = $service->readStatus($restoreId);

    expect($restoredAdmin)->not->toBeNull();
    expect($restoredAdmin->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME))->toBeTrue();
    expect($status)->not->toBeNull();
    expect($status['status'])->toBe('completed');
    expect($status['current_step'])->toBe('Restauracion completada');
});
