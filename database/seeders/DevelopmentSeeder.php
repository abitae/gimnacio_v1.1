<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->warn('DevelopmentSeeder ya no orquesta seeders. Usa DatabaseSeeder para desarrollo local.');
    }
}
