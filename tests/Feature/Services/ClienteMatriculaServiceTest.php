<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ClienteMatriculaService;

it('registers a caja movement when a matricula payment is processed', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000002',
        'nombres' => 'Luis',
        'apellidos' => 'Torres',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Trimestral',
        'duracion_dias' => 90,
        'precio_base' => 180,
        'estado' => 'activa',
    ]);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(90)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 180,
        'descuento_monto' => 0,
        'precio_final' => 180,
    ]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $pago = app(ClienteMatriculaService::class)->procesarPago($matricula->id, [
        'monto_pago' => 40,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
    ]);

    expect($pago->caja_id)->toBe($caja->id);
    expect((float) $pago->saldo_pendiente)->toBe(140.0);

    $movimiento = CajaMovimiento::query()
        ->where('caja_id', $caja->id)
        ->where('referencia_tipo', ClienteMatricula::class)
        ->where('referencia_id', $matricula->id)
        ->latest('id')
        ->first();

    expect($movimiento)->not->toBeNull();
    expect($movimiento->tipo)->toBe('entrada');
    expect((float) $movimiento->monto)->toBe(40.0);
});
