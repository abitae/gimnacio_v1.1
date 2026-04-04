<?php

use App\Livewire\Administracion\DatabaseBackupLive;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
});

it('allows super admin to access database backups screen', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    $this->actingAs($user)
        ->get(route('administracion.backups.index'))
        ->assertOk()
        ->assertSee('Backups de base de datos');
});

it('forbids non super admin users from accessing database backups screen', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $user->assignRole('administrador');

    $this->actingAs($user)
        ->get(route('administracion.backups.index'))
        ->assertForbidden();
});

it('loads terminal events for the latest restore and can reopen the monitor', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
    $this->actingAs($user);

    $restoreId = 'restore_livewire_latest';
    $restoreDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    File::ensureDirectoryExists($restoreDirectory);

    File::put($restoreDirectory.DIRECTORY_SEPARATOR.$restoreId.'.json', json_encode([
        'id' => $restoreId,
        'status' => 'running',
        'stage' => 'executing_sql',
        'cancel_requested' => false,
        'progress' => 42,
        'executed_statements' => 42,
        'total_statements' => 100,
        'current_part' => 1,
        'total_parts' => 1,
        'is_large_restore' => false,
        'estimated_mode' => 'single_part',
        'platform_launcher' => 'windows_cmd',
        'file_size_bytes' => 2048,
        'current_command' => 'SELECT 1;',
        'current_step' => 'Ejecutando sentencia 42 de 100',
        'file_path' => 'fake.sql',
        'original_name' => 'fake.sql',
        'error' => null,
        'started_at' => now()->toDateTimeString(),
        'finished_at' => null,
        'created_at' => now()->toDateTimeString(),
        'last_event_at' => now()->toDateTimeString(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    File::put($restoreDirectory.DIRECTORY_SEPARATOR.$restoreId.'.jsonl', implode(PHP_EOL, [
        json_encode(['timestamp' => now()->toDateTimeString(), 'level' => 'phase', 'stage' => 'queued', 'message' => 'Restore en cola']),
        json_encode(['timestamp' => now()->toDateTimeString(), 'level' => 'command', 'stage' => 'executing_sql', 'message' => 'Ejecutando sentencia 42 de 100', 'command' => 'SELECT 1;']),
        '',
    ]));

    Livewire::test(DatabaseBackupLive::class)
        ->call('openRestoreMonitorModal')
        ->assertSet('showRestoreMonitorModal', true)
        ->assertSet('restoreJobId', $restoreId)
        ->assertSet('restoreEventOffset', 2)
        ->assertSee('Terminal de restauracion')
        ->assertSee('Restore en cola')
        ->assertSee('SELECT 1;');
});
