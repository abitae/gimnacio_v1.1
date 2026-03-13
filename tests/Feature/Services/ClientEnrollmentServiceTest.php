<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use App\Services\ClientEnrollmentService;

it('prioritizes active matriculas and consolidates balance and history', function () {
    $user = User::factory()->create();

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000001',
        'nombres' => 'Ana',
        'apellidos' => 'Perez',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual',
        'duracion_dias' => 30,
        'precio_base' => 120,
        'estado' => 'activa',
    ]);

    ClienteMembresia::create([
        'cliente_id' => $cliente->id,
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->subDays(20)->toDateString(),
        'fecha_inicio' => now()->subDays(20)->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 120,
        'descuento_monto' => 0,
        'precio_final' => 120,
    ]);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->subDays(5)->toDateString(),
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(25)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 75,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 25,
        'registrado_por' => $user->id,
    ]);

    $service = app(ClientEnrollmentService::class);

    $activeEnrollment = $service->resolveActiveEnrollment($cliente->id);
    $history = $service->resolveCommercialHistory($cliente->id);

    expect($activeEnrollment)->not->toBeNull();
    expect($activeEnrollment['source_type'])->toBe('cliente_matricula');
    expect($activeEnrollment['source_id'])->toBe($matricula->id);
    expect($activeEnrollment['saldo_pendiente'])->toBe(25.0);
    expect($history['memberships'])->toHaveCount(2);
});
