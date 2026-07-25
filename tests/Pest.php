<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function biotimeSucursal(?string $codigo = null, bool $esPrincipal = true): App\Models\System\Sucursal
{
    $codigo ??= 'bt-'.Illuminate\Support\Str::lower(Illuminate\Support\Str::random(8));

    $empresa = App\Models\System\Empresa::query()->create([
        'nombre' => 'Empresa Test '.$codigo,
        'estado' => 'activa',
    ]);

    return App\Models\System\Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal '.$codigo,
        'estado' => 'activa',
        'es_principal' => $esPrincipal,
    ]);
}

function biotimeAgentSetting(
    App\Models\System\Sucursal $sucursal,
    string $secret = 'valid-token',
    bool $enabled = true
): App\Models\BioTime\BioTimeSucursalSetting {
    $setting = App\Models\BioTime\BioTimeSucursalSetting::forSucursal($sucursal->id);
    $setting->forceFill([
        'webhook_secret' => $secret,
        'enabled' => $enabled,
    ])->save();

    return $setting->fresh();
}

function biotimeAdmin(?App\Models\System\Sucursal $sucursal = null): App\Models\User
{
    $user = App\Models\User::factory()->create();
    $role = Spatie\Permission\Models\Role::query()->firstOrCreate([
        'name' => App\Support\PermissionCatalog::SUPER_ADMIN_ROLE_NAME,
        'guard_name' => 'web',
    ]);
    $user->assignRole($role);

    if ($sucursal) {
        $user->sucursales()->attach($sucursal->id);
        $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    }

    return $user;
}
