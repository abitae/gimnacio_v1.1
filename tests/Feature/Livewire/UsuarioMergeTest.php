<?php

use App\Livewire\Clientes\ClienteLive;
use App\Livewire\Usuarios\UsuarioLive;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\SucursalContext;
use App\Services\UserMergeService;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    foreach (['usuario.ver', 'usuario.eliminar', 'cliente.ver'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
    }
});

it('unifica usuarios duplicados y reasigna el asesor en clientes y matrículas', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = biotimeSucursal('merge-users-a');
    $operator = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $operator->sucursales()->attach($sucursal->id);
    $operator->givePermissionTo(['usuario.ver', 'usuario.eliminar', 'cliente.ver']);
    $this->actingAs($operator);
    session([SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    $destino = User::factory()->create(['name' => 'Asesor Destino Unificado', 'estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $destino->assignRole('ventas');
    $destino->sucursales()->attach($sucursal->id);

    $origen = User::factory()->create(['name' => 'Asesor Duplicado Origen', 'estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $origen->assignRole('ventas');
    $origen->sucursales()->attach($sucursal->id);

    $cliente = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Unificado Asesor Test',
        'created_by' => $origen->id,
        'sucursal_id' => $sucursal->id,
    ]);

    $membresia = Membresia::factory()->create(['nombre' => 'Plan Merge Asesor', 'precio_base' => 100, 'estado' => 'activa']);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $origen->id,
    ]);

    Livewire::test(UsuarioLive::class)
        ->set('selectedUserIds', [(string) $destino->id, (string) $origen->id])
        ->call('openMergeModal')
        ->assertSet('modalState.merge', true)
        ->set('mergeKeepUserId', (string) $destino->id)
        ->call('confirmMerge')
        ->assertHasNoErrors()
        ->assertSet('modalState.merge', false);

    expect(User::query()->find($origen->id))->toBeNull()
        ->and(User::query()->find($destino->id))->not->toBeNull()
        ->and($matricula->refresh()->asesor_id)->toBe($destino->id)
        ->and($cliente->refresh()->created_by)->toBe($destino->id);

    Livewire::test(ClienteLive::class)
        ->assertSee('Asesor Destino Unificado')
        ->assertDontSee('Asesor Duplicado Origen')
        ->assertSee('Unificado Asesor Test');
});

it('no permite unificar menos de dos usuarios', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = biotimeSucursal('merge-users-min');
    $operator = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $operator->sucursales()->attach($sucursal->id);
    $operator->givePermissionTo(['usuario.ver', 'usuario.eliminar']);
    $this->actingAs($operator);
    session([SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    $unico = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $unico->sucursales()->attach($sucursal->id);

    Livewire::test(UsuarioLive::class)
        ->set('selectedUserIds', [(string) $unico->id])
        ->call('openMergeModal')
        ->assertSet('modalState.merge', false);
});

it('rechaza unificar un super administrador', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = biotimeSucursal('merge-users-admin');
    $operator = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $operator->sucursales()->attach($sucursal->id);
    $operator->givePermissionTo(['usuario.ver', 'usuario.eliminar']);
    $this->actingAs($operator);

    $destino = User::factory()->create(['estado' => 'activo']);
    $destino->assignRole('ventas');
    $super = User::factory()->create(['estado' => 'activo']);
    $super->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);

    expect(fn () => app(UserMergeService::class)->unificar($destino, [$super->id]))
        ->toThrow(InvalidArgumentException::class, 'administrativos especiales');
});
