<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('TestDataSeeder ya no orquesta seeders. Usa DatabaseSeeder para entorno local.');
    }
}
