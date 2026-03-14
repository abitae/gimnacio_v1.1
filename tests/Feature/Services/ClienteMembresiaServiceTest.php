<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\ClientePlanTraspaso;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ClienteMembresiaService;

it('records a traspaso when a legacy membership changes to another plan', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000004',
        'nombres' => 'Ana',
        'apellidos' => 'León',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $planInicial = Membresia::create([
        'nombre' => 'Básico',
        'duracion_dias' => 30,
        'precio_base' => 80,
        'estado' => 'activa',
    ]);

    $planNuevo = Membresia::create([
        'nombre' => 'Full',
        'duracion_dias' => 90,
        'precio_base' => 220,
        'estado' => 'activa',
    ]);

    $membresia = ClienteMembresia::create([
        'cliente_id' => $cliente->id,
        'membresia_id' => $planInicial->id,
        'fecha_matricula' => now()->subDays(2)->toDateString(),
        'fecha_inicio' => now()->subDays(2)->toDateString(),
        'fecha_fin' => now()->addDays(28)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 80,
        'descuento_monto' => 0,
        'precio_final' => 80,
    ]);

    $updated = app(ClienteMembresiaService::class)->update($membresia->id, [
        'membresia_id' => $planNuevo->id,
        'fecha_matricula' => $membresia->fecha_matricula->toDateString(),
        'fecha_inicio' => $membresia->fecha_inicio->toDateString(),
        'fecha_fin' => now()->addDays(90)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 220,
        'descuento_monto' => 0,
    ]);

    expect($updated->membresia_id)->toBe($planNuevo->id);

    $traspaso = ClientePlanTraspaso::query()->where('cliente_id', $cliente->id)->first();

    expect($traspaso)->not->toBeNull();
    expect($traspaso->origen_tipo)->toBe(ClienteMembresia::class);
    expect((int) $traspaso->plan_anterior_id)->toBe($planInicial->id);
    expect((int) $traspaso->plan_nuevo_id)->toBe($planNuevo->id);
});
