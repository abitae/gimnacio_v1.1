<?php

namespace Database\Seeders;

use App\Models\Integration\IntegrationErrorLog;
use Illuminate\Database\Seeder;

class IntegrationErrorLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Error de BioTime - Webhook inválido
        IntegrationErrorLog::create([
            'source' => 'biotime',
            'payload' => [
                'event' => 'access_log',
                'device_id' => 'DEV001',
                'user_id' => 'INVALID_USER',
                'timestamp' => now()->subDays(5)->toIso8601String(),
            ],
            'error_message' => 'Usuario no encontrado en el sistema. biotime_user_id: INVALID_USER',
            'resolved_at' => now()->subDays(4),
        ]);

        // Error de API - Timeout
        IntegrationErrorLog::create([
            'source' => 'api',
            'payload' => [
                'endpoint' => '/api/biotime/users/sync',
                'method' => 'POST',
                'request_data' => [
                    'user_id' => 'BT001',
                    'action' => 'update',
                ],
            ],
            'error_message' => 'Connection timeout after 30 seconds. No se pudo conectar con el servidor BioTime.',
            'resolved_at' => null,
        ]);

        // Error de Webhook - Payload malformado
        IntegrationErrorLog::create([
            'source' => 'webhook',
            'payload' => [
                'event' => 'access_log',
                'data' => 'invalid_json_string',
            ],
            'error_message' => 'JSON malformado en el payload del webhook. Error de sintaxis en línea 3.',
            'resolved_at' => now()->subDays(2),
        ]);

        // Error de BioTime - Dispositivo no encontrado
        IntegrationErrorLog::create([
            'source' => 'biotime',
            'payload' => [
                'event' => 'access_log',
                'device_id' => 'DEV999',
                'user_id' => 'BT001',
                'timestamp' => now()->subDays(1)->toIso8601String(),
            ],
            'error_message' => 'Dispositivo DEV999 no está registrado en el sistema.',
            'resolved_at' => null,
        ]);
    }
}
