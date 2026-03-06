<?php

namespace Database\Seeders;

use App\Models\System\ComprobanteConfig;
use Illuminate\Database\Seeder;

class ComprobanteConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configuraciones = [
            [
                'tipo' => 'ticket',
                'serie' => 'T001',
                'numero_actual' => 0,
                'numero_inicial' => 1,
                'numero_final' => 999999,
                'estado' => 'activo',
            ],
            [
                'tipo' => 'boleta',
                'serie' => 'B001',
                'numero_actual' => 0,
                'numero_inicial' => 1,
                'numero_final' => 999999,
                'estado' => 'activo',
            ],
            [
                'tipo' => 'factura',
                'serie' => 'F001',
                'numero_actual' => 0,
                'numero_inicial' => 1,
                'numero_final' => 999999,
                'estado' => 'activo',
            ],
        ];

        foreach ($configuraciones as $config) {
            ComprobanteConfig::firstOrCreate(
                ['tipo' => $config['tipo']],
                $config
            );
        }
    }
}
