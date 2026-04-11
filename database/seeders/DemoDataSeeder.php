<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('DemoDataSeeder ya no orquesta seeders. Usa DatabaseSeeder para cargar datos locales.');
    }
}
