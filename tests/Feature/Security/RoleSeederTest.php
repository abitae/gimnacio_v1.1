<?php

use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Database\Seeders\CompanyBranchSeeder;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds a permission catalog aligned with active modules', function () {
    $this->seed(RoleSeeder::class);

    $administradorSucursal = Role::findByName(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME);
    $vendedor = Role::findByName('vendedor');
    $caja = Role::findByName('caja');
    $trainer = Role::findByName('trainer');
    $superAdmin = Role::findByName(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    expect(Permission::where('name', 'manage-users')->exists())->toBeFalse();
    expect(Permission::where('name', 'cliente-membresias.view')->exists())->toBeFalse();
    expect(Permission::where('name', 'cliente.ver')->value('descripcion'))->toBe('Ver clientes.');
    expect(Role::whereIn('name', PermissionCatalog::LEGACY_SUPER_ADMIN_ROLE_NAMES)->exists())->toBeFalse();
    expect(Role::whereIn('name', PermissionCatalog::LEGACY_BRANCH_ADMIN_ROLE_NAMES)->exists())->toBeFalse();
    expect($administradorSucursal->permissions->pluck('name')->sort()->values()->all())->toBe(
        collect(PermissionCatalog::permissionNames())->sort()->values()->all()
    );
    expect($superAdmin->permissions)->toHaveCount(0);
    expect($vendedor->hasPermissionTo('crm.crear'))->toBeTrue();
    expect($vendedor->hasPermissionTo('matricula_cliente.crear'))->toBeTrue();
    expect($caja->hasPermissionTo('reporte.ver'))->toBeTrue();
    expect($caja->hasPermissionTo('crm.crear'))->toBeFalse();
    expect($trainer->hasPermissionTo('gestion_nutricional.editar'))->toBeTrue();
});

it('grants super_administrador arbitrary abilities via Gate::before', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    expect($user->can('nonexistent.permission.for-test'))->toBeTrue();
});

it('applies coherent route access for main roles', function (string $role, string $allowedRoute, string $forbiddenRoute) {
    $this->seed([
        CompanyBranchSeeder::class,
        RoleSeeder::class,
    ]);

    $user = User::factory()->create();
    $user->assignRole($role);
    $sucursal = Sucursal::query()->firstOrFail();
    $user->sucursales()->syncWithoutDetaching([$sucursal->id]);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();

    $this->actingAs($user)
        ->withSession([
            SucursalContext::EMPRESA_ID_KEY => $sucursal->empresa_id,
            SucursalContext::SUCURSAL_ID_KEY => $sucursal->id,
            SucursalContext::SUCURSAL_NOMBRE_KEY => $sucursal->nombre,
        ])
        ->get(route($allowedRoute))
        ->assertOk();

    $this->actingAs($user)
        ->withSession([
            SucursalContext::EMPRESA_ID_KEY => $sucursal->empresa_id,
            SucursalContext::SUCURSAL_ID_KEY => $sucursal->id,
            SucursalContext::SUCURSAL_NOMBRE_KEY => $sucursal->nombre,
        ])
        ->get(route($forbiddenRoute))
        ->assertForbidden();
})->with([
    ['vendedor', 'crm.pipeline', 'roles.index'],
    ['caja', 'reportes.index', 'crm.pipeline'],
    ['trainer', 'gestion-nutricional.index', 'cajas.index'],
]);
