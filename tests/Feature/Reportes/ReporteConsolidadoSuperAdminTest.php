<?php

use App\Data\Reporte\ReporteSucursalFilter;
use App\Models\Core\Caja;
use App\Models\Core\Venta;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\ReporteModuloService;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Role;

function crearSucursalReporte(string $codigo, bool $principal = false): Sucursal
{
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa reporte '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal reporte '.$codigo,
        'estado' => 'activa',
        'es_principal' => $principal,
    ]);
}

function crearVentaReporte(Sucursal $sucursal, User $usuario, string $numero, float $total): Venta
{
    $caja = Caja::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursal->id,
        'usuario_id' => $usuario->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    return Venta::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursal->id,
        'caja_id' => $caja->id,
        'numero_venta' => $numero,
        'usuario_id' => $usuario->id,
        'tipo_comprobante' => 'ticket',
        'subtotal' => $total,
        'descuento' => 0,
        'igv' => 0,
        'total' => $total,
        'estado' => 'completada',
        'fecha_venta' => now(),
        'es_credito' => false,
        'metodo_pago' => 'Efectivo',
    ]);
}

afterEach(function () {
    app(SucursalContext::class)->clearDelegateContext();
});

it('consolidates ventas report across assigned sucursales for super admin', function () {
    (new RoleSeeder)->run();

    $sucursalA = crearSucursalReporte('REP-A', true);
    $sucursalB = crearSucursalReporte('REP-B');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
    $superAdmin->sucursales()->attach([$sucursalA->id, $sucursalB->id]);
    $superAdmin->update(['default_sucursal_id' => $sucursalA->id]);

    $this->actingAs($superAdmin);
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    crearVentaReporte($sucursalA, $superAdmin, 'V-A-001', 100);
    crearVentaReporte($sucursalB, $superAdmin, 'V-B-001', 50);

    $activeOnly = app(ReporteModuloService::class)->datosReporteVentas(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
        ReporteSucursalFilter::active(),
    );

    $consolidated = app(ReporteModuloService::class)->datosReporteVentas(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
        new ReporteSucursalFilter(ReporteSucursalFilter::MODE_CONSOLIDATED),
    );

    expect($activeOnly['resumen']['cantidad'])->toBe(1)
        ->and($activeOnly['resumen']['total'])->toBe(100.0)
        ->and($consolidated['resumen']['cantidad'])->toBe(2)
        ->and($consolidated['resumen']['total'])->toBe(150.0);
});

it('filters ventas report to a specific sucursal for super admin', function () {
    (new RoleSeeder)->run();

    $sucursalA = crearSucursalReporte('RSP-A', true);
    $sucursalB = crearSucursalReporte('RSP-B');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
    $superAdmin->sucursales()->attach([$sucursalA->id, $sucursalB->id]);
    $superAdmin->update(['default_sucursal_id' => $sucursalA->id]);

    $this->actingAs($superAdmin);
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    crearVentaReporte($sucursalB, $superAdmin, 'V-B-ONLY', 80);

    $specific = app(ReporteModuloService::class)->datosReporteVentas(
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
        new ReporteSucursalFilter(ReporteSucursalFilter::MODE_SPECIFIC, $sucursalB->id),
    );

    expect($specific['resumen']['cantidad'])->toBe(1)
        ->and($specific['resumen']['total'])->toBe(80.0);
});
