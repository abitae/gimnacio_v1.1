<?php

namespace Database\Seeders;

use App\Models\System\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('No hay usuarios. Ejecuta DatabaseSeeder primero.');
            return;
        }

        // Creación de cliente
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'cliente.created',
            'entity_type' => 'App\Models\Core\Cliente',
            'entity_id' => 1,
            'payload_before' => null,
            'payload_after' => [
                'tipo_documento' => 'DNI',
                'numero_documento' => '12345678',
                'nombres' => 'Juan',
                'apellidos' => 'Pérez García',
            ],
            'ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => now()->subDays(10),
        ]);

        // Actualización de membresía
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'cliente_membresia.updated',
            'entity_type' => 'App\Models\Core\ClienteMembresia',
            'entity_id' => 1,
            'payload_before' => [
                'estado' => 'activa',
                'fecha_fin' => now()->addDays(20)->toDateString(),
            ],
            'payload_after' => [
                'estado' => 'congelada',
                'fecha_fin' => now()->addDays(30)->toDateString(),
            ],
            'ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => now()->subDays(5),
        ]);

        // Eliminación de pago (soft delete o cancelación)
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'pago.deleted',
            'entity_type' => 'App\Models\Core\Pago',
            'entity_id' => 1,
            'payload_before' => [
                'monto' => 150.00,
                'metodo_pago' => 'efectivo',
                'comprobante_numero' => 'B001-000001',
            ],
            'payload_after' => null,
            'ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => now()->subDays(3),
        ]);

        // Cambio de configuración del gimnasio
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'gym_setting.updated',
            'entity_type' => 'App\Models\System\GymSetting',
            'entity_id' => 1,
            'payload_before' => [
                'horarios_acceso' => [
                    'lunes' => ['apertura' => '06:00', 'cierre' => '21:00'],
                ],
            ],
            'payload_after' => [
                'horarios_acceso' => [
                    'lunes' => ['apertura' => '06:00', 'cierre' => '22:00'],
                ],
            ],
            'ip' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'created_at' => now()->subDays(1),
        ]);

        // Acción sin usuario (sistema automático)
        AuditLog::create([
            'user_id' => null,
            'action' => 'system.membership_expired',
            'entity_type' => 'App\Models\Core\ClienteMembresia',
            'entity_id' => 3,
            'payload_before' => [
                'estado' => 'activa',
            ],
            'payload_after' => [
                'estado' => 'vencida',
            ],
            'ip' => null,
            'user_agent' => 'Laravel Scheduler',
            'created_at' => now()->subHours(12),
        ]);
    }
}
