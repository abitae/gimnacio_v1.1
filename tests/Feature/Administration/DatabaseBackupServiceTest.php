<?php

use App\Models\User;
use App\Services\System\DatabaseBackupService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\DB;
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

it('creates a manifest backup with parts and data', function () {
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

    expect($backup['filename'])->toEndWith('.backup.json');
    expect(file_exists($backup['path']))->toBeTrue();
    expect($backup['part_count'])->toBeGreaterThanOrEqual(1);
    expect($backup['storage_type'])->toBe('multipart_manifest');

    $manifest = $service->readManifest($backup['filename']);
    $contents = collect($manifest['parts'])
        ->map(fn (array $part) => file_get_contents(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$part['filename'])))
        ->implode('');

    expect($contents)->toContain('CREATE TABLE');
    expect($contents)->toContain('INSERT INTO');
    expect($contents)->toContain('backup-user@example.test');
    expect($contents)->not->toContain('super-admin@example.test');
});

it('restores the database from a generated manifest backup', function () {
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

    $service->restoreFromManifestPath($backup['path']);

    expect(User::query()->where('email', 'original-user@example.test')->exists())->toBeTrue();
    expect(User::query()->where('email', 'extra-user@example.test')->exists())->toBeFalse();
});

it('deletes a generated backup lot manually', function () {
    $service = app(DatabaseBackupService::class);

    $backup = $service->createBackup();

    expect(file_exists($backup['path']))->toBeTrue();
    foreach ($backup['parts'] as $part) {
        expect(file_exists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$part['filename'])))->toBeTrue();
    }

    $service->deleteBackup($backup['filename']);

    expect(file_exists($backup['path']))->toBeFalse();
    foreach ($backup['parts'] as $part) {
        expect(file_exists(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$part['filename'])))->toBeFalse();
    }
});

it('lists multipart backups as a single lot entry', function () {
    $service = app(DatabaseBackupService::class);

    $backup = $service->createBackup();
    $listed = $service->listBackups();

    expect($listed)->toHaveCount(1);
    expect($listed[0]['filename'])->toBe($backup['filename']);
    expect($listed[0]['part_count'])->toBe($backup['part_count']);
    expect($listed[0]['storage_type'])->toBe('multipart_manifest');
});

it('splits large backups into multiple parts below the configured limit', function () {
    DB::statement('CREATE TABLE chunked_backup_payloads (id INTEGER PRIMARY KEY AUTOINCREMENT, payload TEXT)');

    $payload = str_repeat('X', 14000);
    for ($i = 0; $i < 180; $i++) {
        DB::table('chunked_backup_payloads')->insert(['payload' => $payload.$i]);
    }

    $service = app(DatabaseBackupService::class);
    $backup = $service->createBackup();

    expect($backup['part_count'])->toBeGreaterThan(1);
    foreach ($backup['parts'] as $part) {
        expect($part['size_bytes'])->toBeLessThanOrEqual(1572864);
    }
});

it('fails cleanly when a manifest part is missing during restore assembly', function () {
    $service = app(DatabaseBackupService::class);
    $backup = $service->createBackup();
    $manifest = $service->readManifest($backup['filename']);

    File::delete(storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.$manifest['parts'][0]['filename']));

    $targetPath = storage_path('app'.DIRECTORY_SEPARATOR.'backups'.DIRECTORY_SEPARATOR.'missing_manifest_restore.sql');

    expect(fn () => $service->materializeManifestToSql($backup['path'], $targetPath))
        ->toThrow(RuntimeException::class);

    expect(File::exists($targetPath))->toBeFalse();
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
