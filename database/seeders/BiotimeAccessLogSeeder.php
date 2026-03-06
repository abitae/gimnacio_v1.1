<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Integration\BiotimeAccessLog;
use Illuminate\Database\Seeder;

class BiotimeAccessLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clientes = Cliente::where('biotime_state', true)->get();

        if ($clientes->isEmpty()) {
            $this->command->warn('Ningún cliente está sincronizado con BioTime. Sincronízalos desde Sincronizar BioTime para poder generar logs de acceso.');
            return;
        }

        foreach ($clientes as $cliente) {
            $empCode = (string) $cliente->id;
            // Entrada exitosa
            BiotimeAccessLog::create([
                'biotime_user_id' => $empCode,
                'cliente_id' => $cliente->id,
                'device_id' => 'DEV001',
                'event_time' => now()->subDays(2)->setTime(7, 15),
                'event_type' => 'entry',
                'result' => 'success',
                'raw_payload' => [
                    'device_id' => 'DEV001',
                    'user_id' => $empCode,
                    'event_time' => now()->subDays(2)->setTime(7, 15)->toIso8601String(),
                    'event_type' => 1, // 1 = entry
                    'punch_state' => 0,
                ],
            ]);

            // Salida exitosa
            BiotimeAccessLog::create([
                'biotime_user_id' => $empCode,
                'cliente_id' => $cliente->id,
                'device_id' => 'DEV001',
                'event_time' => now()->subDays(2)->setTime(9, 30),
                'event_type' => 'exit',
                'result' => 'success',
                'raw_payload' => [
                    'device_id' => 'DEV001',
                    'user_id' => $empCode,
                    'event_time' => now()->subDays(2)->setTime(9, 30)->toIso8601String(),
                    'event_type' => 2, // 2 = exit
                    'punch_state' => 0,
                ],
            ]);

            // Intento de acceso denegado (membresía vencida)
            BiotimeAccessLog::create([
                'biotime_user_id' => $empCode,
                'cliente_id' => $cliente->id,
                'device_id' => 'DEV001',
                'event_time' => now()->subDays(1)->setTime(8, 0),
                'event_type' => 'entry',
                'result' => 'denied',
                'raw_payload' => [
                    'device_id' => 'DEV001',
                    'user_id' => $empCode,
                    'event_time' => now()->subDays(1)->setTime(8, 0)->toIso8601String(),
                    'event_type' => 1,
                    'punch_state' => 0,
                    'error_code' => 'MEMBERSHIP_EXPIRED',
                ],
            ]);
        }
    }
}
