<?php

namespace Database\Seeders;

use App\Models\Integration\BiotimeSetting;
use Illuminate\Database\Seeder;

class BiotimeSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Crea un registro inicial de configuración BioTime si la tabla está vacía.
     * Los valores se toman de config/services.php (env BIOTIME_*).
     */
    public function run(): void
    {
        if (BiotimeSetting::exists()) {
            return;
        }

        $config = config('services.biotime', []);

        BiotimeSetting::create([
            'base_url' => $config['base_url'] ? rtrim($config['base_url'], '/') : null,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'auth_type' => $config['auth_type'] ?? 'jwt',
            'enabled' => true,
        ]);

        $this->command->info('Configuración BioTime creada (usa .env BIOTIME_* si está definido).');
    }
}
