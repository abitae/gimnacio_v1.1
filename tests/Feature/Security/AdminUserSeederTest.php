<?php

use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

function seedBranchStructure(): void
{
    $empresa = Empresa::query()->firstOrCreate(
        ['nombre' => 'Empresa Demo'],
        [
            'razon_social' => 'Empresa Demo SAC',
            'estado' => 'activa',
        ]
    );

    Sucursal::query()->firstOrCreate(
        ['codigo' => 'principal'],
        [
            'empresa_id' => $empresa->id,
            'nombre' => 'Sucursal Principal',
            'estado' => 'activa',
            'es_principal' => true,
        ]
    );

    Sucursal::query()->firstOrCreate(
        ['codigo' => 'secundaria'],
        [
            'empresa_id' => $empresa->id,
            'nombre' => 'Sucursal Secundaria',
            'estado' => 'activa',
            'es_principal' => false,
        ]
    );
}

it('creates the base administrator with the minimum required fields', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    seedBranchStructure();
    $this->seed(AdminUserSeeder::class);

    $user = User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Administrador');
    expect($user->estado)->toBe('activo');
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check(AdminUserSeeder::ADMIN_PASSWORD, $user->password))->toBeTrue();
    expect($user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME))->toBeTrue();
    expect($user->sucursales()->count())->toBe(2);
    expect($user->default_sucursal_id)->toBe(
        Sucursal::query()->where('codigo', 'principal')->value('id')
    );
});

it('is idempotent when seeding the base administrator repeatedly', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    seedBranchStructure();
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->count())->toBe(1);
    expect(
        User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->firstOrFail()->sucursales()->count()
    )->toBe(2);
});

it('always uses the fixed administrator password', function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
    seedBranchStructure();
    $this->seed(AdminUserSeeder::class);

    $user = User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->firstOrFail();

    expect(Hash::check(AdminUserSeeder::ADMIN_PASSWORD, $user->password))->toBeTrue();
});
