<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Core\CrmMensaje;
use App\Models\User;
use Illuminate\Database\Seeder;

class CrmMensajeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Depende de: Cliente, User.
     */
    public function run(): void
    {
        if (CrmMensaje::exists()) {
            $this->command->info('Ya existen mensajes CRM. CrmMensajeSeeder se omite.');
            return;
        }

        $clientes = Cliente::whereNotNull('telefono')->limit(3)->get();
        if ($clientes->isEmpty()) {
            $this->command->warn('No hay clientes con teléfono. CrmMensajeSeeder se omite.');
            return;
        }

        $user = User::first();

        CrmMensaje::create([
            'cliente_id' => $clientes[0]->id,
            'canal' => 'whatsapp',
            'destino' => $clientes[0]->telefono ?? '+51999999999',
            'contenido' => 'Hola, te recordamos tu cita de evaluación mañana. ¡Nos vemos en el gimnasio!',
            'estado' => 'enviado',
            'enviado_at' => now()->subDays(2),
            'error_mensaje' => null,
            'created_by' => $user?->id,
        ]);

        CrmMensaje::create([
            'cliente_id' => $clientes[0]->id,
            'canal' => 'whatsapp',
            'destino' => $clientes[0]->telefono ?? '+51999999999',
            'contenido' => 'Tu plan de seguimiento está listo. Pasa por recepción cuando puedas.',
            'estado' => 'enviado',
            'enviado_at' => now()->subDay(),
            'error_mensaje' => null,
            'created_by' => $user?->id,
        ]);

        if ($clientes->count() > 1 && $clientes[1]->telefono) {
            CrmMensaje::create([
                'cliente_id' => $clientes[1]->id,
                'canal' => 'whatsapp',
                'destino' => $clientes[1]->telefono,
                'contenido' => 'Recordatorio: renovación de membresía la próxima semana.',
                'estado' => 'fallido',
                'enviado_at' => null,
                'error_mensaje' => 'Número no registrado en WhatsApp',
                'created_by' => $user?->id,
            ]);
        }

        $this->command->info('Mensajes CRM de ejemplo creados.');
    }
}
