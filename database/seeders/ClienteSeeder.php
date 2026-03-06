<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Faker\Factory as Faker;

class ClienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_PE'); // Faker en español de Perú
        
        // Obtener el primer usuario para created_by
        $user = User::first();
        if (!$user) {
            $this->command->error('No hay usuarios. Ejecuta UserSeeder primero.');
            return;
        }
        
        $tiposDocumento = ['DNI', 'CE'];
        $sexos = ['masculino', 'femenino'];
        $relaciones = ['Esposa', 'Esposo', 'Hermano', 'Hermana', 'Padre', 'Madre', 'Hijo', 'Hija', 'Amigo', 'Amiga'];
        
        $alergias = ['Ninguna', 'Polen', 'Ácaros', 'Lactosa', 'Gluten', 'Mariscos', 'Maní'];
        $medicamentos = ['Ninguno', 'Antihistamínico', 'Analgésico', 'Antiinflamatorio', 'Antibiótico'];
        $lesiones = ['Ninguna', 'Rodilla izquierda', 'Rodilla derecha', 'Hombro', 'Espalda', 'Tobillo'];
        
        // Usar transacción para mejor rendimiento
        DB::transaction(function () use ($faker, $user, $tiposDocumento, $sexos, $relaciones, $alergias, $medicamentos, $lesiones) {
            $documentosUsados = [];
            
            for ($i = 1; $i <= 500; $i++) {
                $tipoDocumento = $faker->randomElement($tiposDocumento);
                
                // Generar número de documento único
                do {
                    if ($tipoDocumento === 'DNI') {
                        $numeroDocumento = str_pad($faker->numberBetween(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                    } else {
                        $numeroDocumento = 'CE' . str_pad($faker->numberBetween(100000, 999999), 6, '0', STR_PAD_LEFT);
                    }
                    $key = $tipoDocumento . '-' . $numeroDocumento;
                } while (isset($documentosUsados[$key]));
                
                $documentosUsados[$key] = true;
                
                // Generar datos de salud
                $datosSalud = [
                    'alergias' => $faker->randomElement($alergias),
                    'medicamentos' => $faker->randomElement($medicamentos),
                    'lesiones' => $faker->randomElement($lesiones),
                ];
                
                // Generar datos de emergencia (70% de probabilidad de tener contacto)
                $datosEmergencia = null;
                if ($faker->boolean(70)) {
                    $datosEmergencia = [
                        'nombre_contacto' => $faker->name(),
                        'telefono_contacto' => '9' . $faker->numerify('########'),
                        'relacion' => $faker->randomElement($relaciones),
                    ];
                }
                
                // Generar consentimientos
                $consentimientos = [
                    'uso_imagen' => $faker->boolean(80),
                    'tratamiento_datos' => $faker->boolean(95),
                    'fecha_consentimiento' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
                ];
                
                Cliente::create([
                    'tipo_documento' => $tipoDocumento,
                    'numero_documento' => $numeroDocumento,
                    'nombres' => $faker->firstName(),
                    'apellidos' => $faker->lastName() . ' ' . $faker->lastName(),
                    'telefono' => $faker->boolean(85) ? '9' . $faker->numerify('########') : null,
                    'email' => $faker->boolean(75) ? $faker->unique()->safeEmail() : null,
                    'direccion' => $faker->boolean(90) ? $faker->address() : null,
                    'estado_cliente' => 'inactivo',
                    'foto' => null,
                    'sexo' => $faker->randomElement($sexos),
                    'datos_salud' => $datosSalud,
                    'datos_emergencia' => $datosEmergencia,
                    'consentimientos' => $consentimientos,
                    'biotime_state' => false,
                    'biotime_update' => false,
                    'created_by' => $user->id,
                    'created_at' => $faker->dateTimeBetween('-2 years', 'now'),
                    'updated_at' => now(),
                ]);
                
                // Mostrar progreso cada 100 registros
                if ($i % 100 === 0) {
                    $this->command->info("Generados {$i} clientes...");
                }
            }
        });
        
        $this->command->info('✅ Se generaron 500 clientes exitosamente.');
    }
}
