<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Bootstrap base obligatorio para una instalación limpia.
        $this->call([
            BaseCatalogSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Datos demo funcionales: php artisan db:seed --class=DemoDataSeeder
        // Seeders legacy/especiales: ClienteMembresiaSeeder, PagoSeeder, EvaluacionFisicaSeeder
        // Escenarios/volumen: ScenarioSeeder, MassiveRootSeeder, EdgeCaseSeeder
    }
}
