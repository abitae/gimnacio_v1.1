<?php

use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\Hash;

it('creates the base administrator with the minimum required fields', function () {
    config()->set('app.key', 'base64:test-app-key');
    putenv('SEED_ADMIN_PASSWORD=seed-admin-secret');

    $this->seed(BaseCatalogSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $user = User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->first();

    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Administrador');
    expect($user->estado)->toBe('activo');
    expect($user->email_verified_at)->not->toBeNull();
    expect(Hash::check('seed-admin-secret', $user->password))->toBeTrue();
    expect($user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME))->toBeTrue();

    putenv('SEED_ADMIN_PASSWORD');
});

it('is idempotent when seeding the base administrator repeatedly', function () {
    config()->set('app.key', 'base64:test-app-key');
    putenv('SEED_ADMIN_PASSWORD=seed-admin-secret');

    $this->seed(BaseCatalogSeeder::class);
    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    expect(User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->count())->toBe(1);

    putenv('SEED_ADMIN_PASSWORD');
});

it('falls back to a deterministic password when no env password is configured', function () {
    config()->set('app.key', 'base64:test-app-key');
    putenv('SEED_ADMIN_PASSWORD');

    $this->seed(BaseCatalogSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $user = User::query()->where('email', AdminUserSeeder::ADMIN_EMAIL)->firstOrFail();
    $expectedFallback = hash('sha256', config('app.key') . '|' . AdminUserSeeder::ADMIN_EMAIL);

    expect(Hash::check($expectedFallback, $user->password))->toBeTrue();
});
