<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Seeders demo funcionales para poblar un entorno local/manual.
     */
    public function run(): void
    {
        $this->call([
            MembresiaSeeder::class,
            ClienteSeeder::class,
            ProductoSeeder::class,
            ServicioExternoSeeder::class,
            ClaseSeeder::class,
            RentableSpaceSeeder::class,
            DiscountCouponSeeder::class,
            CajaSeeder::class,
            EmployeeSeeder::class,
            ClienteMatriculaDemoSeeder::class,
            TrainerSeeder::class,
            EvaluacionMedidasNutricionSeeder::class,
            CitaSeeder::class,
            SeguimientoNutricionSeeder::class,
            HealthRecordSeeder::class,
            NutritionGoalSeeder::class,
            CrmMensajeSeeder::class,
            BiotimeAccessLogSeeder::class,
            IntegrationErrorLogSeeder::class,
            AuditLogSeeder::class,
            ExerciseSeeder::class,
            RoutineTemplateSeeder::class,
        ]);
    }
}
