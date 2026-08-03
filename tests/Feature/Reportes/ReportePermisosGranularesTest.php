<?php

use App\Livewire\Reportes\ReporteCajasLive;
use App\Livewire\Reportes\ReporteClientesLive;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use Illuminate\Support\Collection;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function reporteTestUserWithSucursal(array $permissions = []): User
{
    $empresa = Empresa::create([
        'nombre' => 'Test Gym '.uniqid(),
        'razon_social' => 'Test Gym',
        'estado' => 'activa',
    ]);
    $sucursal = Sucursal::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Sede principal',
        'codigo' => 'sede-'.uniqid(),
        'estado' => 'activa',
    ]);

    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);

    foreach ($permissions as $permission) {
        Permission::findOrCreate($permission, 'web');
        $user->givePermissionTo($permission);
    }

    test()->actingAs($user);

    session([
        SucursalContext::EMPRESA_ID_KEY => $empresa->id,
        SucursalContext::SUCURSAL_ID_KEY => $sucursal->id,
    ]);

    return $user;
}

beforeEach(function () {
    Permission::findOrCreate('reporte.clientes', 'web');
    Permission::findOrCreate('reporte.cajas', 'web');
    Role::findOrCreate('trainer', 'web');
});

it('allows access to a single analytic report with granular permission', function () {
    reporteTestUserWithSucursal(['reporte.clientes']);

    $this->get(route('reportes.clientes'))->assertOk();
    Livewire::test(ReporteClientesLive::class)->assertOk();
});

it('denies analytic report without matching granular permission', function () {
    reporteTestUserWithSucursal(['reporte.clientes']);

    $this->get(route('reportes.cajas'))->assertForbidden();
    Livewire::test(ReporteCajasLive::class)->assertForbidden();
});

it('lists only trainers from active sucursal in clientes report filter', function () {
    $empresa = Empresa::create([
        'nombre' => 'Test Gym',
        'razon_social' => 'Test Gym',
        'estado' => 'activa',
    ]);
    $sucursalA = Sucursal::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Sede A',
        'codigo' => 'sede-a-test',
        'estado' => 'activa',
    ]);
    $sucursalB = Sucursal::create([
        'empresa_id' => $empresa->id,
        'nombre' => 'Sede B',
        'codigo' => 'sede-b-test',
        'estado' => 'activa',
    ]);

    $trainerA = User::factory()->create(['name' => 'Entrenador Sede A']);
    $trainerA->assignRole('trainer');
    $trainerA->sucursales()->attach($sucursalA->id);

    $trainerB = User::factory()->create(['name' => 'Entrenador Sede B']);
    $trainerB->assignRole('trainer');
    $trainerB->sucursales()->attach($sucursalB->id);

    $user = User::factory()->create(['default_sucursal_id' => $sucursalA->id]);
    $user->sucursales()->attach($sucursalA->id);
    $user->givePermissionTo('reporte.clientes');
    $this->actingAs($user);

    session([
        SucursalContext::EMPRESA_ID_KEY => $sucursalA->empresa_id,
        SucursalContext::SUCURSAL_ID_KEY => $sucursalA->id,
    ]);

    Livewire::test(ReporteClientesLive::class)
        ->assertViewHas('trainers', function (Collection $trainers): bool {
            $names = $trainers->pluck('name')->all();

            return in_array('Entrenador Sede A', $names, true)
                && ! in_array('Entrenador Sede B', $names, true);
        });
});
