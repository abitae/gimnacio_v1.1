<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Para cargar el dump completo empaquetado (MySQL, database/data/backup_part*.sql) y
     * el super admin, usar en su lugar: php artisan db:seed --class=BundledSqlBackupSeeder
     * (no combinar con BaseCatalogSeeder en el mismo run).
     */
    public function run(): void
    {
        // Bootstrap base obligatorio para una instalación limpia.
        /* $this->call([
            BaseCatalogSeeder::class,
            AdminUserSeeder::class,
        ]); */
        $this->call(BundledSqlBackupSeeder::class);
        $this->call(AdminUserSeeder::class);
        // Datos demo funcionales: php artisan db:seed --class=DemoDataSeeder
        // Seeders legacy/especiales: ClienteMembresiaSeeder, PagoSeeder, EvaluacionFisicaSeeder
        // Escenarios/volumen: ScenarioSeeder, MassiveRootSeeder, EdgeCaseSeeder
    }
}
