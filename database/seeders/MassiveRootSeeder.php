<?php

namespace Database\Seeders;

use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\Employee;
use App\Models\Core\Membresia;
use App\Models\Core\Producto;
use App\Models\Core\RentableSpace;
use App\Models\Core\ServicioExterno;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class MassiveRootSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BaseCatalogSeeder::class]);

        $clientCount = (int) env('SEED_MASSIVE_CLIENTES', 300);
        $leadCount = (int) env('SEED_MASSIVE_LEADS', 120);
        $productCount = (int) env('SEED_MASSIVE_PRODUCTOS', 80);
        $serviceCount = (int) env('SEED_MASSIVE_SERVICIOS', 40);
        $employeeCount = (int) env('SEED_MASSIVE_EMPLEADOS', 25);
        $spaceCount = (int) env('SEED_MASSIVE_ESPACIOS', 12);
        $membershipCount = (int) env('SEED_MASSIVE_MEMBRESIAS', 12);
        $classCount = (int) env('SEED_MASSIVE_CLASES', 16);

        if (User::count() < 8) {
            User::factory()->count(8 - User::count())->create();
        }

        Membresia::factory()->count($membershipCount)->create();
        Clase::factory()->count($classCount)->create();
        Cliente::factory()->count($clientCount)->create();
        Producto::factory()->count($productCount)->create();
        ServicioExterno::factory()->count($serviceCount)->create();
        Employee::factory()->count($employeeCount)->create();
        RentableSpace::factory()->count($spaceCount)->create();

        $stageIds = CrmStage::query()->pluck('id');

        Lead::factory()
            ->count($leadCount)
            ->state(fn () => [
                'stage_id' => $stageIds->random(),
                'created_by' => User::query()->inRandomOrder()->value('id'),
                'assigned_to' => User::query()->inRandomOrder()->value('id'),
            ])
            ->create();
    }
}
