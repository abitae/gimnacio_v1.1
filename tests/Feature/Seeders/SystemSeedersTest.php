<?php

use App\Models\Core\Caja;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\DiscountCoupon;
use App\Models\Core\HealthRecord;
use App\Models\Core\Producto;
use Database\Seeders\EdgeCaseSeeder;
use Database\Seeders\MassiveRootSeeder;
use Database\Seeders\ScenarioSeeder;

it('builds root stress data with configurable volumes', function () {
    putenv('SEED_MASSIVE_CLIENTES=6');
    putenv('SEED_MASSIVE_LEADS=4');
    putenv('SEED_MASSIVE_PRODUCTOS=5');
    putenv('SEED_MASSIVE_SERVICIOS=3');
    putenv('SEED_MASSIVE_EMPLEADOS=2');
    putenv('SEED_MASSIVE_ESPACIOS=2');
    putenv('SEED_MASSIVE_MEMBRESIAS=2');
    putenv('SEED_MASSIVE_CLASES=2');

    $this->seed(MassiveRootSeeder::class);

    expect(\App\Models\Core\Cliente::count())->toBeGreaterThanOrEqual(6);
    expect(\App\Models\Crm\Lead::count())->toBeGreaterThanOrEqual(4);
    expect(Producto::count())->toBeGreaterThanOrEqual(5);
    expect(\App\Models\Core\ServicioExterno::count())->toBeGreaterThanOrEqual(3);

    putenv('SEED_MASSIVE_CLIENTES');
    putenv('SEED_MASSIVE_LEADS');
    putenv('SEED_MASSIVE_PRODUCTOS');
    putenv('SEED_MASSIVE_SERVICIOS');
    putenv('SEED_MASSIVE_EMPLEADOS');
    putenv('SEED_MASSIVE_ESPACIOS');
    putenv('SEED_MASSIVE_MEMBRESIAS');
    putenv('SEED_MASSIVE_CLASES');
});

it('seeds transactional scenarios and edge cases for cross-module validation', function () {
    $this->seed(ScenarioSeeder::class);
    $this->seed(EdgeCaseSeeder::class);

    expect(Caja::where('estado', 'abierta')->exists())->toBeTrue();
    expect(Caja::where('estado', 'cerrada')->exists())->toBeTrue();
    expect(ClienteMatricula::where('modalidad_pago', 'cuotas')->where('requiere_plan_cuotas', true)->exists())->toBeTrue();
    expect(ClienteMatricula::whereIn('estado', ['congelada', 'cancelada', 'vencida'])->count())->toBeGreaterThanOrEqual(3);
    expect(Producto::where('stock_actual', 0)->exists())->toBeTrue();
    expect(DiscountCoupon::where('codigo', 'EDGEEXP')->exists())->toBeTrue();
    expect(HealthRecord::query()->whereNotNull('observaciones')->exists())->toBeTrue();
});
