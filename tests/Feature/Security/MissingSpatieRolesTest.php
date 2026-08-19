<?php

use App\Models\User;
use App\Services\ClienteService;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

it('no lanza RoleDoesNotExist al listar asesores si falta el rol vendedor', function () {
    User::factory()->create(['name' => 'Sin rol de venta', 'estado' => 'activo']);

    expect(fn () => User::query()->asesoresActivos()->get())
        ->not->toThrow(RoleDoesNotExist::class);

    expect(app(ClienteService::class)->asesoresActivosParaFiltro())->toHaveCount(0);
});

it('no lanza RoleDoesNotExist al listar trainers si falta el rol trainer', function () {
    expect(fn () => User::query()->trainersForSucursal()->get())
        ->not->toThrow(RoleDoesNotExist::class)
        ->and(User::query()->trainersForSucursal()->count())->toBe(0);
});
