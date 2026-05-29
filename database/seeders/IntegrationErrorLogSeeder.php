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
        IntegrationErrorLog::create([
            'source' => 'api',
            'payload' => [
                'endpoint' => '/api/external/sync',
                'method' => 'POST',
                'request_data' => [
                    'resource_id' => 'EXT001',
                    'action' => 'update',
                ],
            ],
            'error_message' => 'Connection timeout after 30 seconds. No se pudo conectar con el servicio externo.',
            'resolved_at' => null,
        ]);

        IntegrationErrorLog::create([
            'source' => 'webhook',
            'payload' => [
                'event' => 'external_event',
                'data' => 'invalid_json_string',
            ],
            'error_message' => 'JSON malformado en el payload del webhook. Error de sintaxis en linea 3.',
            'resolved_at' => now()->subDays(2),
        ]);
    }
}
