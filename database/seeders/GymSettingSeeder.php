<?php

namespace Database\Seeders;

use App\Models\System\GymSetting;
use Illuminate\Database\Seeder;

class GymSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        GymSetting::firstOrCreate(
            ['id' => 1],
            [
                'nombre_gimnasio' => 'Gimnasio Fitness Pro',
                'ruc' => '20123456789',
                'direccion' => 'Av. Principal 123, Lima, Perú',
                'telefono' => '+51 987 654 321',
                'email' => 'info@gimnasiofitnesspro.com',
                'logo' => null,
                'horarios_acceso' => [
                    'lunes' => ['apertura' => '06:00', 'cierre' => '22:00'],
                    'martes' => ['apertura' => '06:00', 'cierre' => '22:00'],
                    'miercoles' => ['apertura' => '06:00', 'cierre' => '22:00'],
                    'jueves' => ['apertura' => '06:00', 'cierre' => '22:00'],
                    'viernes' => ['apertura' => '06:00', 'cierre' => '22:00'],
                    'sabado' => ['apertura' => '08:00', 'cierre' => '20:00'],
                    'domingo' => ['apertura' => '08:00', 'cierre' => '18:00'],
                ],
                'politicas_acceso' => '1. Los clientes deben presentar su identificación al ingresar.
2. El acceso está permitido solo durante el horario establecido.
3. Se requiere membresía activa para ingresar.
4. Los menores de 16 años deben estar acompañados por un adulto.
5. Se prohíbe el uso de teléfonos móviles en las áreas de entrenamiento.
6. Es obligatorio el uso de toalla y calzado deportivo.
7. Los clientes deben limpiar el equipo después de usarlo.
8. Se reserva el derecho de admisión.',
            ]
        );
    }
}
