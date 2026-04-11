<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('ProductionBootstrapSeeder ya no orquesta seeders. Usa DatabaseSeeder para entorno local.');
    }
}
