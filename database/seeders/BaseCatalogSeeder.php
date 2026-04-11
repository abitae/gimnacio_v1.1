<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('BaseCatalogSeeder ya no orquesta seeders. Ejecuta DatabaseSeeder en entorno local.');
    }
}
