<?php

use App\Livewire\Settings\AppPublicidad\Index;
use App\Models\Core\AppPublicidad;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use App\Models\User;
use App\Services\SucursalContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    foreach (['publicidad_app.ver', 'publicidad_app.crear', 'publicidad_app.editar', 'publicidad_app.eliminar'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
    }
});

function publicidadStaff(): array
{
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $user->givePermissionTo(['publicidad_app.ver', 'publicidad_app.crear', 'publicidad_app.editar', 'publicidad_app.eliminar']);
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();

    return compact('sucursal', 'user');
}

it('responde 200 en configuracion de publicidad app', function () {
    ['user' => $user, 'sucursal' => $sucursal] = publicidadStaff();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $this->get(route('app-publicidad.index'))->assertOk();
});

it('crea una publicidad con imagen para la sucursal activa', function () {
    Storage::fake('public');
    ['user' => $user, 'sucursal' => $sucursal] = publicidadStaff();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    Livewire::test(Index::class)
        ->call('openCreateModal')
        ->set('formData.titulo', 'Promo verano')
        ->set('formData.orden', 1)
        ->set('formData.estado', 'activo')
        ->set('imagen', UploadedFile::fake()->image('banner.jpg', 800, 400))
        ->call('save')
        ->assertHasNoErrors();

    $item = AppPublicidad::query()->first();

    expect($item)->not->toBeNull()
        ->and($item->titulo)->toBe('Promo verano')
        ->and($item->sucursal_id)->toBe($sucursal->id)
        ->and(Storage::disk('public')->exists($item->imagen))->toBeTrue();
});

it('lista publicidades activas de la sucursal del socio', function () {
    Storage::fake('public');
    $sucursal = biotimeSucursal();
    $otra = biotimeSucursal();
    $staff = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $staff->id,
    ]);
    $account = ClienteAppAccount::factory()->create(['cliente_id' => $cliente->id]);
    $token = clienteAppToken($account);

    AppPublicidad::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursal->id,
        'titulo' => 'Banner propio',
        'imagen' => 'app-publicidad/propia.jpg',
        'orden' => 1,
        'estado' => 'activo',
    ]);
    AppPublicidad::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursal->id,
        'titulo' => 'Banner inactivo',
        'imagen' => 'app-publicidad/inactiva.jpg',
        'orden' => 2,
        'estado' => 'inactivo',
    ]);
    AppPublicidad::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $otra->id,
        'titulo' => 'Otra sede',
        'imagen' => 'app-publicidad/otra.jpg',
        'orden' => 1,
        'estado' => 'activo',
    ]);

    $response = $this->getJson('/api/v1/publicidades', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.titulo', 'Banner propio');

    expect($response->json('data.0.imagen_url'))->toContain('app-publicidad/propia.jpg');
});
