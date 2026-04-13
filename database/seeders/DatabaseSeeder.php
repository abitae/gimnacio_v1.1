<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Producción: solo catálogos y configuración mínima (firstOrCreate / datos de referencia).
     * No incluye usuarios demo, Faker, escenarios de prueba ni volúmenes masivos.
     *
     * Tras migrar en producción: crear el primer administrador desde la aplicación o con
     * `php artisan` — evitar AdminUserSeeder en servidores reales (credenciales en el repo).
     */
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
            MembresiaSeeder::class,
            ExerciseSeeder::class,
            RoutineTemplateSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Solo local / staging: datos ficticios, demos y pruebas de carga.
        // php artisan db:seed → en producción no ejecuta lo siguiente (descomentar solo en dev).
        //
        // $this->call([
        //     AdminUserSeeder::class,
        //     TrainerSeeder::class,
        //     ClienteSeeder::class,
        //     ProductoSeeder::class,
        //     ServicioExternoSeeder::class,
        //     ClaseSeeder::class,
        //     RentableSpaceSeeder::class,
        //     DiscountCouponSeeder::class,
        //     CajaSeeder::class,
        //     EmployeeSeeder::class,
        //     ClienteMembresiaSeeder::class,
        //     PagoSeeder::class,
        //     CajaMovimientoSeeder::class,
        //     AsistenciaSeeder::class,
        //     ClienteMatriculaDemoSeeder::class,
        //     EvaluacionMedidasNutricionSeeder::class,
        //     CitaSeeder::class,
        //     SeguimientoNutricionSeeder::class,
        //     HealthRecordSeeder::class,
        //     NutritionGoalSeeder::class,
        //     CrmMensajeSeeder::class,
        //     BiotimeAccessLogSeeder::class,
        //     IntegrationErrorLogSeeder::class,
        //     AuditLogSeeder::class,
        //     ScenarioSeeder::class,
        //     EdgeCaseSeeder::class,
        //     MassiveRootSeeder::class,
        // ]);
    }
}
