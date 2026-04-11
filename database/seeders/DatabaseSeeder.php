<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            GymSettingSeeder::class,
            BiotimeSettingSeeder::class,
            ComprobanteConfigSeeder::class,
            PaymentMethodSeeder::class,
            CategoriaProductoSeeder::class,
            CategoriaServicioSeeder::class,
            CrmStageSeeder::class,
            LossReasonSeeder::class,
            CompanyBranchSeeder::class,
            AdminUserSeeder::class,
            MembresiaSeeder::class,
            TrainerSeeder::class,
            ClienteSeeder::class,
            ProductoSeeder::class,
            ServicioExternoSeeder::class,
            ClaseSeeder::class,
            RentableSpaceSeeder::class,
            DiscountCouponSeeder::class,
            CajaSeeder::class,
            EmployeeSeeder::class,
            ClienteMembresiaSeeder::class,
            PagoSeeder::class,
            CajaMovimientoSeeder::class,
            AsistenciaSeeder::class,
            ClienteMatriculaDemoSeeder::class,
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
            ScenarioSeeder::class,
            EdgeCaseSeeder::class,
            MassiveRootSeeder::class,
        ]);
    }
}
