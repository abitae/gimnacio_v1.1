<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario de prueba primero (necesario para otros seeders)
        User::firstOrCreate(
            ['email' => 'abel.arana@hotmail.com'],
            [
                'name' => 'Abel Arana',
                'password' => bcrypt('lobomalo123'),
                'email_verified_at' => now(),
                'estado' => 'activo',
            ]
        );

        // Seeders en orden de dependencias
        $this->call([
            RoleSeeder::class,                // Roles y permisos (Spatie); asigna admin al usuario inicial
            MembresiaSeeder::class,           // Sin dependencias
            ClienteSeeder::class,             // Sin dependencias
            GymSettingSeeder::class,          // Sin dependencias
            BiotimeSettingSeeder::class,       // Sin dependencias (config BioTime)
            // Seeders del sistema POS (antes de cajas para tener productos)
            CategoriaProductoSeeder::class,   // Sin dependencias
            ProductoSeeder::class,            // Depende de CategoriaProducto
            CategoriaServicioSeeder::class,   // Sin dependencias
            ServicioExternoSeeder::class,     // Depende de CategoriaServicio
            ClaseSeeder::class,               // Depende de User (instructores)
            ComprobanteConfigSeeder::class,   // Sin dependencias
            // Seeders de caja (necesario para pagos)
            CajaSeeder::class,                // Depende de User
            // ClienteMembresiaSeeder::class, // Deshabilitado: clientes sin matrículas/membresías
            // PagoSeeder::class,             // Depende de ClienteMembresia y Caja
            // AsistenciaSeeder::class,       // Depende de ClienteMembresia
            TrainerSeeder::class,             // Usuarios con rol trainer (después de RoleSeeder)
            EvaluacionMedidasNutricionSeeder::class,   // Depende de Cliente
            CitaSeeder::class,                // Depende de Cliente, EvaluacionMedidasNutricion
            SeguimientoNutricionSeeder::class, // Depende de Cliente, Cita, User (nutricionista)
            CrmMensajeSeeder::class,          // Depende de Cliente, User
            CrmStageSeeder::class,             // Etapas CRM (antes de leads)
            LossReasonSeeder::class,          // Motivos de pérdida CRM
            BiotimeAccessLogSeeder::class,   // Depende de Cliente
            IntegrationErrorLogSeeder::class, // Sin dependencias
            AuditLogSeeder::class,            // Depende de User
            ExerciseSeeder::class,            // Catálogo de ejercicios (módulo Ejercicios y Rutinas)
            RoutineTemplateSeeder::class,    // Rutinas base demo (depende de ExerciseSeeder)
            // Nota: CajaMovimientoSeeder se ejecuta después si hay ventas
            // Se puede ejecutar manualmente después de crear ventas en el POS
        ]);
    }
}
