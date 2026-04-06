<?php

use App\Models\User;
use App\Services\System\DatabaseBackupService;
use App\Support\PermissionCatalog;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
});

afterEach(function () {
    File::ensureDirectoryExists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
    File::cleanDirectory(storage_path('app'.DIRECTORY_SEPARATOR.'backups'));
});

it('creates a sql backup file with schema and data', function () {
    $superAdmin = User::factory()->create([
        'name' => 'Super Admin',
        'email' => 'super-admin@example.test',
        'estado' => 'activo',
    ]);
    $superAdmin->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    $user = User::factory()->create([
        'name' => 'Backup User',
        'email' => 'backup-user@example.test',
        'estado' => 'activo',
    ]);

    $service = app(DatabaseBackupService::class);
    $backup = $service->createBackup();

    expect($backup['filename'])->toEndWith('.sql');
    expect(file_exists($backup['path']))->toBeTrue();

    $contents = file_get_contents($backup['path']);

    expect($contents)->toContain('CREATE TABLE');
    expect($contents)->toContain('INSERT INTO');
    expect($contents)->toContain('backup-user@example.test');
    expect($contents)->not->toContain('super-admin@example.test');
});

it('restores the database from a generated backup', function () {
    $service = app(DatabaseBackupService::class);

    $original = User::factory()->create([
        'name' => 'Original User',
        'email' => 'original-user@example.test',
    ]);

    $backup = $service->createBackup();

    $extra = User::factory()->create([
        'name' => 'Extra User',
        'email' => 'extra-user@example.test',
    ]);

    expect(User::query()->where('email', 'extra-user@example.test')->exists())->toBeTrue();

    $service->restoreFromPath($backup['path']);

    expect(User::query()->where('email', 'original-user@example.test')->exists())->toBeTrue();
    expect(User::query()->where('email', 'extra-user@example.test')->exists())->toBeFalse();
});

it('deletes a generated backup file manually', function () {
    $service = app(DatabaseBackupService::class);

    $backup = $service->createBackup();

    expect(file_exists($backup['path']))->toBeTrue();

    $service->deleteBackup($backup['filename']);

    expect(file_exists($backup['path']))->toBeFalse();
});

it('restores large sql files in parts and reports chunked progress', function () {
    $service = app(DatabaseBackupService::class);

    $tempPath = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'large_restore_test.sql');
    File::ensureDirectoryExists(dirname($tempPath));

    $payload = str_repeat('A', 20000);
    $sql = "CREATE TABLE restore_chunk_test (id INTEGER PRIMARY KEY AUTOINCREMENT, payload TEXT);\n";
    for ($i = 1; $i <= 320; $i++) {
        $sql .= "INSERT INTO restore_chunk_test (payload) VALUES ('{$payload}{$i}');\n";
    }
    File::put($tempPath, $sql);

    $observedLargeRestore = false;
    $observedMultipleParts = false;

    try {
        $service->restoreFromPath($tempPath, function (array $progress) use (&$observedLargeRestore, &$observedMultipleParts): void {
            $observedLargeRestore = $observedLargeRestore || (bool) ($progress['is_large_restore'] ?? false);
            $observedMultipleParts = $observedMultipleParts || (int) ($progress['total_parts'] ?? 0) > 1;
        });

        expect($observedLargeRestore)->toBeTrue();
        expect($observedMultipleParts)->toBeTrue();
        expect(\Illuminate\Support\Facades\DB::table('restore_chunk_test')->count())->toBe(320);
    } finally {
        File::delete($tempPath);
    }
});
