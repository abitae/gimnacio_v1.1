<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class BaseCatalogSeeder extends Seeder
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
        ]);
    }
}
