<?php

use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Spatie\Permission\Models\Permission;

it('reports success when catalog permissions exist in database', function () {
    $this->seed(RoleSeeder::class);

    $this->artisan('permissions:audit')
        ->assertSuccessful();
});

it('reports missing permissions and can sync them', function () {
    Permission::query()->where('name', 'checking.ver')->delete();

    $this->artisan('permissions:audit')
        ->assertFailed();

    $this->artisan('permissions:audit --sync')
        ->assertSuccessful();

    expect(Permission::query()->where('name', 'checking.ver')->exists())->toBeTrue();
});

it('audits role assignments with --roles', function () {
    $this->seed(RoleSeeder::class);

    $this->artisan('permissions:audit --roles')
        ->assertSuccessful();
});

it('outputs json report', function () {
    $this->seed(RoleSeeder::class);

    $this->artisan('permissions:audit --json')
        ->assertSuccessful()
        ->expectsOutputToContain('"ok": true');
});
