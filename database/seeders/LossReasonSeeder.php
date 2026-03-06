<?php

namespace Database\Seeders;

use App\Models\Crm\LossReason;
use Illuminate\Database\Seeder;

class LossReasonSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            'Precio alto',
            'No tiene tiempo',
            'Eligió otra opción',
            'No responde',
            'Sin interés',
            'Ubicación',
            'Otro',
        ];

        foreach ($reasons as $nombre) {
            LossReason::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
