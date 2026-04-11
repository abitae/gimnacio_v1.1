<?php

use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;

function createSuperAdminModuleSucursal(string $codigo = 'principal-admin'): Sucursal
{
    $existingSucursal = Sucursal::query()->where('codigo', $codigo)->first();

    if ($existingSucursal) {
        return $existingSucursal;
    }

    $empresa = Empresa::query()->firstOrCreate(
        ['nombre' => 'Empresa Super Admin'],
        ['razon_social' => 'Empresa Super Admin SAC', 'estado' => 'activa']
    );

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal '.$codigo,
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

it('allows only super administrador to access the company branches module', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = createSuperAdminModuleSucursal();

    $superAdmin = User::factory()->withoutTwoFactor()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $superAdmin->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
    $superAdmin->sucursales()->syncWithoutDetaching([$sucursal->id]);

    $branchAdmin = User::factory()->withoutTwoFactor()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $branchAdmin->assignRole(PermissionCatalog::BRANCH_ADMIN_ROLE_NAME);
    $branchAdmin->sucursales()->syncWithoutDetaching([$sucursal->id]);

    $this->actingAs($superAdmin)
        ->withSession([
            SucursalContext::EMPRESA_ID_KEY => $sucursal->empresa_id,
            SucursalContext::SUCURSAL_ID_KEY => $sucursal->id,
            SucursalContext::SUCURSAL_NOMBRE_KEY => $sucursal->nombre,
        ])
        ->get(route('company-branches.index'))
        ->assertOk();

    $this->actingAs($branchAdmin)
        ->withSession([
            SucursalContext::EMPRESA_ID_KEY => $sucursal->empresa_id,
            SucursalContext::SUCURSAL_ID_KEY => $sucursal->id,
            SucursalContext::SUCURSAL_NOMBRE_KEY => $sucursal->nombre,
        ])
        ->get(route('company-branches.index'))
        ->assertForbidden();
});
