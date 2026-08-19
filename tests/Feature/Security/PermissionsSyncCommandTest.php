<?php

use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('syncs permissions and roles from catalog', function () {
    Permission::query()->where('name', 'checking.ver')->delete();

    $this->artisan('permissions:sync')
        ->assertSuccessful()
        ->expectsOutputToContain('permisos');

    expect(Permission::query()->where('name', 'checking.ver')->exists())->toBeTrue();
});

it('assigns role permissions according to catalog', function () {
    $this->artisan('permissions:sync')->assertSuccessful();

    $role = Role::findByName('vendedor');
    $expected = collect(PermissionCatalog::permissionsForRole('vendedor'))->sort()->values();
    $assigned = $role->permissions()->pluck('name')->sort()->values();

    expect($assigned->all())->toBe($expected->all());
});

it('does not overwrite existing role permissions without --reset', function () {
    $this->artisan('permissions:sync')->assertSuccessful();

    $caja = Role::findByName('caja');
    $caja->givePermissionTo('crm.crear');
    expect($caja->hasPermissionTo('crm.crear'))->toBeTrue();

    $this->artisan('permissions:sync')->assertSuccessful();

    $caja->refresh();
    expect($caja->hasPermissionTo('crm.crear'))->toBeTrue();
});

it('overwrites existing role permissions only with --reset', function () {
    $this->artisan('permissions:sync')->assertSuccessful();

    $caja = Role::findByName('caja');
    $caja->givePermissionTo('crm.crear');

    $this->artisan('permissions:sync', ['--reset' => true])->assertSuccessful();

    $caja->refresh();
    expect($caja->hasPermissionTo('crm.crear'))->toBeFalse();
});

it('produces same result as RoleSeeder', function () {
    $this->seed(RoleSeeder::class);

    $this->artisan('permissions:audit --roles')
        ->assertSuccessful();
});
