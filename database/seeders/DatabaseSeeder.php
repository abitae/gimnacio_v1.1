<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PermissionCatalog;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Usuario de prueba (super-admin se asigna tras RoleSeeder en BaseCatalog; el rol debe existir ya)
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
            BaseCatalogSeeder::class,         // Catálogos y permisos base
            MembresiaSeeder::class,           // Sin dependencias
            ClienteSeeder::class,             // Sin dependencias
            // Seeders del sistema POS (antes de cajas para tener productos)
            ProductoSeeder::class,            // Depende de CategoriaProducto
            ServicioExternoSeeder::class,     // Depende de CategoriaServicio
            ClaseSeeder::class,               // Depende de User (instructores)
            RentableSpaceSeeder::class,       // Espacios para alquiler (sin dependencias)
            DiscountCouponSeeder::class,      // Cupones de descuento (sin dependencias)
            // Seeders de caja (necesario para pagos)
            CajaSeeder::class,                // Depende de User
            EmployeeSeeder::class,            // Empleados (depende de User)
            ClienteMatriculaDemoSeeder::class, // Matrículas demo (cliente_matriculas + pagos vía servicio)
            // ClienteMembresiaSeeder::class, // Legacy cliente_membresias
            // PagoSeeder::class,             // Legacy: requiere ClienteMembresiaSeeder
            // AsistenciaSeeder::class,       // Depende de ClienteMembresia
            TrainerSeeder::class,             // Usuarios con rol trainer (después de RoleSeeder)
            EvaluacionMedidasNutricionSeeder::class,   // Depende de Cliente
            CitaSeeder::class,                // Depende de Cliente, EvaluacionMedidasNutricion
            SeguimientoNutricionSeeder::class, // Depende de Cliente, Cita, User (nutricionista)
            HealthRecordSeeder::class,        // Datos de salud (depende de Cliente)
            NutritionGoalSeeder::class,       // Objetivos nutricionales (depende de Cliente, User)
            CrmMensajeSeeder::class,          // Depende de Cliente, User
            BiotimeAccessLogSeeder::class,   // Depende de Cliente
            IntegrationErrorLogSeeder::class, // Sin dependencias
            AuditLogSeeder::class,            // Depende de User
            ExerciseSeeder::class,            // Catálogo de ejercicios (módulo Ejercicios y Rutinas)
            RoutineTemplateSeeder::class,    // Rutinas base demo (depende de ExerciseSeeder)
            // Nota: CajaMovimientoSeeder se ejecuta después si hay ventas
            // Se puede ejecutar manualmente después de crear ventas en el POS
        ]);

        $superAdminUser = User::query()->where('email', 'abel.arana@hotmail.com')->first();
        if ($superAdminUser !== null) {
            $superAdminUser->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
        }

        // Datos masivos / escenarios / casos límite: php artisan db:seed --class=NombreSeeder
    }
}
