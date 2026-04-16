<?php

use App\Models\Core\Cliente;
use App\Models\Core\RentableSpace;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\RentalService;
use App\Services\SucursalContext;

function crearSucursalRental(string $codigo, bool $principal = false): Sucursal
{
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal '.$codigo,
        'estado' => 'activa',
        'es_principal' => $principal,
    ]);
}

afterEach(function () {
    app(SucursalContext::class)->clearDelegateContext();
});

it('rejects rentals when the client belongs to another sucursal than the rentable space', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalRental('RENT-A', true);
    $sucursalB = crearSucursalRental('RENT-B');
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    $space = RentableSpace::factory()->create([
        'nombre' => 'Cancha central',
        'tipo' => 'cancha',
        'sucursal_id' => $sucursalA->id,
    ]);

    $clienteOtraSucursal = Cliente::factory()->create([
        'created_by' => $user->id,
        'sucursal_id' => $sucursalB->id,
    ]);

    app(RentalService::class)->create([
        'rentable_space_id' => $space->id,
        'cliente_id' => $clienteOtraSucursal->id,
        'fecha' => now()->addDay()->toDateString(),
        'hora_inicio' => '10:00',
        'hora_fin' => '11:00',
        'precio' => 50,
        'estado' => 'reservado',
    ]);
})->throws(\InvalidArgumentException::class, 'misma sucursal');
