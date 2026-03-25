<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ClienteMatriculaService;
use App\Services\ClientWellnessService;

it('congela membresía activa por número de días y extiende fecha_fin', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000009',
        'nombres' => 'Pía',
        'apellidos' => 'Mora',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Plan con freeze',
        'duracion_dias' => 30,
        'precio_base' => 100,
        'permite_congelacion' => true,
        'max_dias_congelacion' => 10,
        'estado' => 'activa',
    ]);

    $matricula = app(ClienteMatriculaService::class)->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'modalidad_pago' => 'contado',
    ]);

    $finEsperada = $matricula->fecha_fin->copy()->addDays(3)->toDateString();

    app(ClientWellnessService::class)->freezePlanByDays(
        $cliente->id,
        'cliente_matricula',
        $matricula->id,
        3,
        'Prueba',
        $user->id
    );

    $m = ClienteMatricula::query()->findOrFail($matricula->id);
    expect($m->estado)->toBe('congelada');
    expect($m->fecha_fin->toDateString())->toBe($finEsperada);

    $periodos = $m->fechas_congelacion ?? [];
    expect($periodos)->not->toBeEmpty();
    expect($periodos[array_key_last($periodos)]['dias'] ?? null)->toBe(3);
});
