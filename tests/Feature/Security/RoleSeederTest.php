<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds a permission catalog aligned with active modules', function () {
    $this->seed(RoleSeeder::class);

    $vendedor = Role::findByName('vendedor');
    $caja = Role::findByName('caja');
    $trainer = Role::findByName('trainer');

    expect(Permission::where('name', 'manage-users')->exists())->toBeFalse();
    expect(Permission::where('name', 'cliente-membresias.view')->exists())->toBeFalse();
    expect($vendedor->hasPermissionTo('crm.create'))->toBeTrue();
    expect($vendedor->hasPermissionTo('cliente-matriculas.create'))->toBeTrue();
    expect($caja->hasPermissionTo('reportes.view'))->toBeTrue();
    expect($caja->hasPermissionTo('crm.create'))->toBeFalse();
    expect($trainer->hasPermissionTo('gestion-nutricional.update'))->toBeTrue();
});

it('applies coherent route access for main roles', function (string $role, string $allowedRoute, string $forbiddenRoute) {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user)
        ->get(route($allowedRoute))
        ->assertOk();

    $this->actingAs($user)
        ->get(route($forbiddenRoute))
        ->assertForbidden();
})->with([
    ['vendedor', 'crm.pipeline', 'roles.index'],
    ['caja', 'reportes.index', 'crm.pipeline'],
    ['trainer', 'gestion-nutricional.index', 'cajas.index'],
]);
