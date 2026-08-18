<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

function clienteParaApp(array $overrides = []): Cliente
{
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();

    return Cliente::factory()->create(array_merge([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'tipo_documento' => 'DNI',
        'numero_documento' => '87654321',
        'nombres' => 'Ana',
        'apellidos' => 'Quispe',
    ], $overrides));
}

it('activa la cuenta de un cliente existente y devuelve token', function () {
    $cliente = clienteParaApp();

    $this->postJson('/api/v1/auth/activar', [
        'tipo_documento' => 'DNI',
        'numero_documento' => $cliente->numero_documento,
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ])->assertCreated()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('cliente.numero_documento', $cliente->numero_documento)
        ->assertJsonPath('cliente.nombres', 'Ana')
        ->assertJsonMissingPath('cliente.datos_salud')
        ->assertJsonStructure(['token', 'cliente' => ['id', 'nombre_completo']]);

    expect(ClienteAppAccount::query()->where('cliente_id', $cliente->id)->exists())->toBeTrue();
});

it('no activa si el documento no existe en recepción', function () {
    $this->postJson('/api/v1/auth/activar', [
        'tipo_documento' => 'DNI',
        'numero_documento' => '11111111',
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.numero_documento.0', 'No encontramos tu documento. Acércate a recepción.');
});

it('no activa si el cliente ya tiene cuenta', function () {
    $cliente = clienteParaApp();
    ClienteAppAccount::factory()->create(['cliente_id' => $cliente->id]);

    $this->postJson('/api/v1/auth/activar', [
        'tipo_documento' => 'DNI',
        'numero_documento' => $cliente->numero_documento,
        'password' => 'secreto123',
        'password_confirmation' => 'secreto123',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.numero_documento.0', 'Ya tienes cuenta. Inicia sesión.');
});

it('inicia sesión con dni y contraseña', function () {
    $cliente = clienteParaApp();
    ClienteAppAccount::factory()->create([
        'cliente_id' => $cliente->id,
        'password' => 'secreto123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'tipo_documento' => 'DNI',
        'numero_documento' => $cliente->numero_documento,
        'password' => 'secreto123',
    ])->assertOk()
        ->assertJsonPath('cliente.id', $cliente->id)
        ->assertJsonStructure(['token']);
});

it('pide crear contraseña si el cliente existe pero no tiene cuenta', function () {
    $cliente = clienteParaApp();

    $this->postJson('/api/v1/auth/login', [
        'tipo_documento' => 'DNI',
        'numero_documento' => $cliente->numero_documento,
        'password' => 'secreto123',
    ])->assertUnprocessable()
        ->assertJsonPath('errors.numero_documento.0', 'Primero crea tu contraseña.');
});

it('rechaza login con contraseña incorrecta', function () {
    $cliente = clienteParaApp();
    ClienteAppAccount::factory()->create([
        'cliente_id' => $cliente->id,
        'password' => 'secreto123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'tipo_documento' => 'DNI',
        'numero_documento' => $cliente->numero_documento,
        'password' => 'otra-clave',
    ])->assertUnprocessable();
});

it('cierra sesión y revoca el token actual', function () {
    $cliente = clienteParaApp();
    $account = ClienteAppAccount::factory()->create(['cliente_id' => $cliente->id]);
    $token = clienteAppToken($account);

    $this->postJson('/api/v1/auth/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('message', 'Sesión cerrada.');

    expect($account->tokens()->count())->toBe(0);

    Auth::forgetGuards();

    $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();
});

it('cambia la contraseña y exige un nuevo login', function () {
    $cliente = clienteParaApp();
    $account = ClienteAppAccount::factory()->create([
        'cliente_id' => $cliente->id,
        'password' => 'secreto123',
    ]);
    $token = clienteAppToken($account);

    $this->postJson('/api/v1/auth/cambiar-password', [
        'password_actual' => 'secreto123',
        'password' => 'nuevaClave9',
        'password_confirmation' => 'nuevaClave9',
    ], [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk();

    Auth::forgetGuards();

    $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertUnauthorized();

    $this->postJson('/api/v1/auth/login', [
        'tipo_documento' => 'DNI',
        'numero_documento' => $cliente->numero_documento,
        'password' => 'nuevaClave9',
    ])->assertOk();
});

it('rechaza rutas protegidas sin token', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});
