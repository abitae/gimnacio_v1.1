<?php

use App\Models\User;
use App\Services\System\DatabaseBackupService;
use App\Services\System\DatabaseRestoreService;
use App\Support\PermissionCatalog;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores'));
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads'));
});

afterEach(function () {
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores'));
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads'));
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

    $backupService = app(DatabaseBackupService::class);
    $backup = $backupService->createBackup();
    User::query()->where('email', 'abel.arana@hotmail.com')->delete();

    $restoreId = 'restore_test_super_admin';
    $restoreDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    $uploadsDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads');
    File::ensureDirectoryExists($restoreDirectory);
    File::ensureDirectoryExists($uploadsDirectory);

    $filePath = $uploadsDirectory.DIRECTORY_SEPARATOR.$restoreId.'.sql';
    $backupService->materializeArchiveToSql($backup['path'], $filePath);

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
        'original_name' => $backup['filename'],
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
    expect($restoredAdmin->estado)->toBe('activo');
    expect($restoredAdmin->email_verified_at)->not->toBeNull();
    expect($status)->not->toBeNull();
    expect($status['status'])->toBe('completed');
    expect($status['current_step'])->toBe('Restauracion completada');
    expect($status['stage'])->toBe('completed');

    $eventsPayload = $service->readEvents($restoreId, 0, 10000);
    $stages = collect($eventsPayload['events'])->pluck('stage')->filter()->values()->all();

    expect($stages)->toContain('running');
    expect($stages)->toContain('restoring_super_admin');
    expect($stages)->toContain('completed');
});

it('repairs an existing incomplete super admin during restore', function () {
    $service = app(DatabaseRestoreService::class);

    $superAdmin = User::factory()->create([
        'name' => 'Usuario Dañado',
        'email' => 'abel.arana@hotmail.com',
        'estado' => 'inactivo',
        'email_verified_at' => null,
        'password' => 'temporal',
    ]);
    $superAdmin->syncRoles([]);

    $backupService = app(DatabaseBackupService::class);
    $backup = $backupService->createBackup();

    $restoreId = 'restore_test_super_admin_repair';
    $restoreDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    $uploadsDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'uploads');
    File::ensureDirectoryExists($restoreDirectory);
    File::ensureDirectoryExists($uploadsDirectory);

    $filePath = $uploadsDirectory.DIRECTORY_SEPARATOR.$restoreId.'.sql';
    $backupService->materializeArchiveToSql($backup['path'], $filePath);

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
        'original_name' => $backup['filename'],
        'error' => null,
        'started_at' => null,
        'finished_at' => null,
        'created_at' => now()->toDateTimeString(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $service->runRestore($restoreId);

    $restoredAdmin = User::query()->where('email', 'abel.arana@hotmail.com')->first();

    expect($restoredAdmin)->not->toBeNull();
    expect($restoredAdmin->name)->toBe('Administrador');
    expect($restoredAdmin->estado)->toBe('activo');
    expect($restoredAdmin->email_verified_at)->not->toBeNull();
    expect($restoredAdmin->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME))->toBeTrue();
    expect(User::query()->where('email', 'abel.arana@hotmail.com')->count())->toBe(1);
});

it('reads restore events incrementally', function () {
    $service = app(DatabaseRestoreService::class);

    $restoreId = 'restore_test_events';
    $restoreDirectory = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'restores');
    File::ensureDirectoryExists($restoreDirectory);

    File::put($restoreDirectory.DIRECTORY_SEPARATOR.$restoreId.'.jsonl', implode(PHP_EOL, [
        json_encode(['timestamp' => now()->toDateTimeString(), 'level' => 'phase', 'stage' => 'queued', 'message' => 'queued']),
        json_encode(['timestamp' => now()->toDateTimeString(), 'level' => 'phase', 'stage' => 'running', 'message' => 'running']),
        json_encode(['timestamp' => now()->toDateTimeString(), 'level' => 'success', 'stage' => 'completed', 'message' => 'completed']),
        '',
    ]));

    $first = $service->readEvents($restoreId, 0, 2);
    $second = $service->readEvents($restoreId, $first['next_offset'], 2);

    expect($first['events'])->toHaveCount(2);
    expect($first['next_offset'])->toBeGreaterThan(0);
    expect($second['events'])->toHaveCount(1);
    expect($second['events'][0]['stage'])->toBe('completed');
});

it('builds platform specific launcher scripts', function () {
    $service = app(DatabaseRestoreService::class);
    $reflection = new ReflectionClass($service);

    $windowsMethod = $reflection->getMethod('createWindowsLauncherScript');
    $windowsMethod->setAccessible(true);
    $windowsPath = $windowsMethod->invoke($service, 'restore_test_windows', 'C:\\php\\php.exe', 'E:\\app\\artisan', 'E:\\app');

    $unixMethod = $reflection->getMethod('createUnixLauncherScript');
    $unixMethod->setAccessible(true);
    $unixPath = $unixMethod->invoke($service, 'restore_test_unix', '/usr/bin/php', '/var/www/artisan', '/var/www');

    expect($windowsPath)->toEndWith('.cmd');
    expect(File::get($windowsPath))->toContain('backup:restore-run --id="restore_test_windows"');
    expect($unixPath)->toEndWith('.sh');
    expect(File::get($unixPath))->toContain("/usr/bin/php");
    expect(File::get($unixPath))->toContain('backup:restore-run --id="restore_test_unix"');

    File::delete([$windowsPath, $unixPath]);
});

it('resolves the cli php binary when running from php cgi on windows', function () {
    if (DIRECTORY_SEPARATOR !== '\\') {
        $this->markTestSkipped('La resolucion de php-cgi.exe solo aplica a Windows.');
    }

    $cgiBinary = 'C:\\Users\\abela\\.config\\herd\\bin\\php83\\php-cgi.exe';
    $expectedCliBinary = 'C:\\Users\\abela\\.config\\herd\\bin\\php83\\php.exe';

    if (! File::exists($expectedCliBinary)) {
        $this->markTestSkipped('No hay binario php.exe disponible para validar el reemplazo de php-cgi.exe.');
    }

    $service = new class(app(\App\Services\System\DatabaseBackupService::class)) extends DatabaseRestoreService {
        private string $forcedBinary = '';

        public function __construct($backupService)
        {
            parent::__construct($backupService);
        }

        public function setForcedBinary(string $binary): void
        {
            $this->forcedBinary = $binary;
        }

        protected function currentPhpBinary(): string
        {
            return $this->forcedBinary !== '' ? $this->forcedBinary : PHP_BINARY;
        }
    };

    $service->setForcedBinary($cgiBinary);

    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('resolveCliPhpBinary');
    $method->setAccessible(true);

    expect($method->invoke($service))->toBe($expectedCliBinary);
});
