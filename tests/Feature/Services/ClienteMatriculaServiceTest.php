<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClientePlanTraspaso;
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
    expect($movimiento->categoria)->toBe('membresia');
    expect($movimiento->origen_modulo)->toBe('cliente_matriculas');
    expect((float) $movimiento->monto)->toBe(40.0);
});

it('records a traspaso when a matricula changes to another plan', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000003',
        'nombres' => 'Marco',
        'apellidos' => 'Ruiz',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $planInicial = Membresia::create([
        'nombre' => 'Mensual',
        'duracion_dias' => 30,
        'precio_base' => 90,
        'estado' => 'activa',
    ]);

    $planNuevo = Membresia::create([
        'nombre' => 'Premium',
        'duracion_dias' => 60,
        'precio_base' => 150,
        'estado' => 'activa',
    ]);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $planInicial->id,
        'fecha_matricula' => now()->subDays(5)->toDateString(),
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(25)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 90,
        'descuento_monto' => 0,
        'precio_final' => 90,
    ]);

    $updated = app(ClienteMatriculaService::class)->update($matricula->id, [
        'tipo' => 'membresia',
        'membresia_id' => $planNuevo->id,
        'fecha_matricula' => $matricula->fecha_matricula->toDateString(),
        'fecha_inicio' => $matricula->fecha_inicio->toDateString(),
        'fecha_fin' => now()->addDays(60)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 150,
        'descuento_monto' => 0,
    ]);

    expect($updated->membresia_id)->toBe($planNuevo->id);

    $traspaso = ClientePlanTraspaso::query()->where('cliente_id', $cliente->id)->first();

    expect($traspaso)->not->toBeNull();
    expect($traspaso->origen_tipo)->toBe(ClienteMatricula::class);
    expect($traspaso->plan_anterior_tipo)->toBe('membresia');
    expect((int) $traspaso->plan_anterior_id)->toBe($planInicial->id);
    expect($traspaso->plan_nuevo_tipo)->toBe('membresia');
    expect((int) $traspaso->plan_nuevo_id)->toBe($planNuevo->id);
});

it('creates installment plans automatically for financed memberships without duplicating debt', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000005',
        'nombres' => 'Rosa',
        'apellidos' => 'Salas',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Semestral Financiada',
        'duracion_dias' => 180,
        'precio_base' => 180,
        'permite_cuotas' => true,
        'numero_cuotas_default' => 3,
        'frecuencia_cuotas_default' => 'mensual',
        'cuota_inicial_monto' => 30,
        'estado' => 'activa',
    ]);

    $matricula = app(ClienteMatriculaService::class)->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 180,
        'descuento_monto' => 0,
        'modalidad_pago' => 'cuotas',
        'numero_cuotas' => 3,
        'frecuencia_cuotas' => 'mensual',
        'cuota_inicial_monto' => 30,
    ])->fresh(['pagos', 'installmentPlan.installments']);

    expect($matricula->modalidad_pago)->toBe('cuotas');
    expect($matricula->requiere_plan_cuotas)->toBeTrue();
    expect((float) $matricula->cuota_inicial_monto)->toBe(30.0);
    expect($matricula->pagos)->toHaveCount(1);
    expect((float) $matricula->pagos->first()->monto)->toBe(30.0);
    expect((float) $matricula->pagos->first()->saldo_pendiente)->toBe(150.0);
    expect($matricula->installmentPlan)->not->toBeNull();
    expect((int) $matricula->installmentPlan->cliente_id)->toBe((int) $cliente->id);
    expect($matricula->installmentPlan->numero_cuotas)->toBe(3);
    expect((float) $matricula->installmentPlan->monto_total)->toBe(150.0);
    expect($matricula->installmentPlan->installments)->toHaveCount(3);
    expect((float) $matricula->installmentPlan->installments->sum('monto'))->toBe(150.0);
    expect((int) $matricula->installmentPlan->installments->first()->cliente_matricula_id)->toBe((int) $matricula->id);
    expect($cliente->fresh()->deuda_total)->toBe(150.0);
});

it('rejects financed memberships when quota fields are missing', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000006',
        'nombres' => 'Pedro',
        'apellidos' => 'Nina',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual Contado',
        'duracion_dias' => 30,
        'precio_base' => 90,
        'estado' => 'activa',
    ]);

    app(ClienteMatriculaService::class)->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 90,
        'descuento_monto' => 0,
        'modalidad_pago' => 'cuotas',
        'frecuencia_cuotas' => 'mensual',
    ]);
})->throws(\Illuminate\Validation\ValidationException::class);

it('registra pago a cuenta en membresía al contado con saldo pendiente', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000007',
        'nombres' => 'Nora',
        'apellidos' => 'Díaz',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual demo contado',
        'duracion_dias' => 30,
        'precio_base' => 200,
        'estado' => 'activa',
    ]);

    $matricula = app(ClienteMatriculaService::class)->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 200,
        'descuento_monto' => 0,
        'modalidad_pago' => 'contado',
        'monto_pago_inicial' => 50,
    ])->fresh('pagos');

    expect($matricula->pagos)->toHaveCount(1);
    expect((float) $matricula->pagos->first()->monto)->toBe(50.0);
    expect((float) $matricula->pagos->first()->saldo_pendiente)->toBe(150.0);
    expect($matricula->pagos->first()->metodo_pago)->toBe('pago_a_cuenta');
});

it('sincroniza el único pago al contado cuando cambia precio_final en update', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000008',
        'nombres' => 'Omar',
        'apellidos' => 'León',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual sync',
        'duracion_dias' => 30,
        'precio_base' => 100,
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
        'monto_pago_inicial' => 25,
    ]);

    app(ClienteMatriculaService::class)->update($matricula->id, [
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => $matricula->fecha_matricula->toDateString(),
        'fecha_inicio' => $matricula->fecha_inicio->toDateString(),
        'fecha_fin' => $matricula->fecha_fin->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 120,
        'descuento_monto' => 0,
    ]);

    $pago = $matricula->fresh()->pagos()->orderBy('id')->first();
    expect((float) $pago->monto)->toBe(25.0);
    expect((float) $pago->saldo_pendiente)->toBe(95.0);
});
