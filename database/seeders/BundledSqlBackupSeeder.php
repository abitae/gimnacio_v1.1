<?php

namespace Database\Seeders;

use App\Services\System\DatabaseBackupService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;

class BundledSqlBackupSeeder extends Seeder
{
    /**
     * Importa las partes SQL empaquetadas en orden y recompone el bootstrap actual.
     *
     * Requiere MySQL y los archivos database/data/backup_part[1-3].sql.
     * No ejecutar junto a BaseCatalogSeeder (el dump ya incluye catálogo y datos).
     *
     * Uso: php artisan db:seed --class=BundledSqlBackupSeeder
     *      php artisan migrate:fresh --seeder=BundledSqlBackupSeeder
     */
    public function run(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'BundledSqlBackupSeeder solo es compatible con MySQL. Conexión actual: '.DB::connection()->getDriverName().'.'
            );
        }

        $paths = [
            database_path('data/backup_part1.sql'),
            database_path('data/backup_part2.sql'),
            database_path('data/backup_part3.sql'),
        ];

        foreach ($paths as $path) {
            if (! File::isFile($path)) {
                throw new RuntimeException('No se encontró el archivo SQL: '.$path);
            }
        }

        $backupService = app(DatabaseBackupService::class);

        foreach ($paths as $path) {
            $backupService->restoreFromPath($path);
        }

        $this->command?->warn('BundledSqlBackupSeeder ya no ejecuta seeders adicionales. Si necesitas bootstrap local posterior, ejecuta DatabaseSeeder manualmente.');
    }
}
