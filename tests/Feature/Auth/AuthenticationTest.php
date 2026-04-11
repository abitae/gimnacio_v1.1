<?php

use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Laravel\Fortify\Features;

function createEmpresaConSucursal(string $codigo = 'principal'): Sucursal
{
    $existingSucursal = Sucursal::query()->where('codigo', $codigo)->first();

    if ($existingSucursal) {
        return $existingSucursal;
    }

    $empresa = Empresa::create([
        'nombre' => 'Open9 Corp',
        'razon_social' => 'Open9 Corp',
        'estado' => 'activa',
    ]);

    return Sucursal::create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => ucfirst($codigo),
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

test('login screen can be rendered', function () {
    createEmpresaConSucursal();

    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('users can authenticate using the login screen with a valid sucursal', function () {
    $sucursal = createEmpresaConSucursal();
    $user = User::factory()->withoutTwoFactor()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->sucursales()->attach($sucursal->id);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'sucursal_id' => $sucursal->id,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $response->assertSessionHas('sucursal_activa_id', $sucursal->id);
});

test('users can not authenticate with a sucursal that is not assigned', function () {
    $sucursal = createEmpresaConSucursal('norte');
    $otraSucursal = createEmpresaConSucursal('sur');
    $user = User::factory()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->sucursales()->attach($sucursal->id);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'sucursal_id' => $otraSucursal->id,
    ]);

    $response->assertSessionHasErrorsIn('sucursal_id');
    $this->assertGuest();
});

test('super administrador can authenticate with any active sucursal', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = createEmpresaConSucursal('central');
    $user = User::factory()->withoutTwoFactor()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'sucursal_id' => $sucursal->id,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $response->assertSessionHas('sucursal_activa_id', $sucursal->id);
});

test('users can not authenticate with invalid password', function () {
    $sucursal = createEmpresaConSucursal();
    $user = User::factory()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->sucursales()->attach($sucursal->id);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
        'sucursal_id' => $sucursal->id,
    ]);

    $response->assertSessionHasErrorsIn('email');
    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $sucursal = createEmpresaConSucursal();
    $user = User::factory()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->sucursales()->attach($sucursal->id);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
        'sucursal_id' => $sucursal->id,
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $sucursal = createEmpresaConSucursal();
    $user = User::factory()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->sucursales()->attach($sucursal->id);

    $response = $this->actingAs($user)
        ->withSession(['sucursal_activa_id' => $sucursal->id])
        ->post(route('logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});
