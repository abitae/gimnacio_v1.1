<?php

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\BaseCatalogSeeder;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
});

it('allows super admin to access database backups screen', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    $this->actingAs($user)
        ->get(route('administracion.backups.index'))
        ->assertOk()
        ->assertSee('Backups de base de datos');
});

it('forbids non super admin users from accessing database backups screen', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $user->assignRole('administrador');

    $this->actingAs($user)
        ->get(route('administracion.backups.index'))
        ->assertForbidden();
});
