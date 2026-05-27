<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClientePlanTraspaso;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use App\Services\ClienteMatriculaService;
use App\Services\EnrollmentInstallmentService;

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
    expect($pago->comprobante_tipo)->toBe('ticket');
    expect($pago->comprobante_numero)->toStartWith('C');

    $movimiento = CajaMovimiento::query()
        ->where('caja_id', $caja->id)
        ->where('referencia_tipo', Pago::class)
        ->where('referencia_id', $pago->id)
        ->latest('id')
        ->first();

    expect($movimiento)->not->toBeNull();
    expect($movimiento->tipo)->toBe('entrada');
    expect($movimiento->categoria)->toBe('membresia');
    expect($movimiento->origen_modulo)->toBe('cliente_matriculas');
    expect((float) $movimiento->monto)->toBe(40.0);
});

it('allows partial payments on enrollment installments and completes the same installment later', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create(['precio_base' => 300, 'estado' => 'activa']);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(90)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 300,
        'descuento_monto' => 0,
        'precio_final' => 300,
        'modalidad_pago' => 'cuotas',
        'requiere_plan_cuotas' => true,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);

    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto_total' => 300,
        'numero_cuotas' => 2,
        'monto_cuota' => 150,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);

    $cuota = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 1,
        'monto' => 150,
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'pendiente',
    ]);

    EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 2,
        'monto' => 150,
        'fecha_vencimiento' => now()->addDays(35)->toDateString(),
        'estado' => 'pendiente',
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $service = app(EnrollmentInstallmentService::class);

    $primerPago = $service->pagarCuota($cuota, [
        'monto' => 60,
        'fecha_pago' => now()->toDateString(),
    ]);

    $cuota->refresh();
    expect((float) $primerPago->monto)->toBe(60.0);
    expect($primerPago->enrollment_installment_id)->toBe($cuota->id);
    expect($cuota->estado)->toBe('parcial');
    expect((float) $cuota->monto_pagado)->toBe(60.0);
    expect((float) $cuota->saldo_pendiente)->toBe(90.0);
    expect(app(ClienteMatriculaService::class)->obtenerSaldoPendiente($matricula->id))->toBe(240.0);

    $segundoPago = $service->pagarCuota($cuota, [
        'monto' => 90,
        'fecha_pago' => now()->toDateString(),
    ]);

    $cuota->refresh();
    expect((float) $segundoPago->monto)->toBe(90.0);
    expect($cuota->estado)->toBe('pagada');
    expect((float) $cuota->monto_pagado)->toBe(150.0);
    expect((float) $cuota->saldo_pendiente)->toBe(0.0);
    expect($cuota->pagos()->count())->toBe(2);
    expect(app(ClienteMatriculaService::class)->obtenerSaldoPendiente($matricula->id))->toBe(150.0);
});

it('rejects installment payments greater than the pending balance', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create(['precio_base' => 150, 'estado' => 'activa']);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 150,
        'descuento_monto' => 0,
        'precio_final' => 150,
        'modalidad_pago' => 'cuotas',
        'requiere_plan_cuotas' => true,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);
    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto_total' => 150,
        'numero_cuotas' => 1,
        'monto_cuota' => 150,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);
    $cuota = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 1,
        'monto' => 150,
        'monto_pagado' => 100,
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'parcial',
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    expect(fn () => app(EnrollmentInstallmentService::class)->pagarCuota($cuota, [
        'monto' => 60,
        'fecha_pago' => now()->toDateString(),
    ]))->toThrow(\InvalidArgumentException::class, 'saldo pendiente de la cuota');
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
    expect($matricula->pagos)->toHaveCount(0);
    expect($matricula->installmentPlan)->not->toBeNull();
    expect((int) $matricula->installmentPlan->cliente_id)->toBe((int) $cliente->id);
    expect($matricula->installmentPlan->numero_cuotas)->toBe(3);
    expect((float) $matricula->installmentPlan->monto_total)->toBe(180.0);
    expect($matricula->installmentPlan->installments)->toHaveCount(3);
    expect((float) $matricula->installmentPlan->installments->sum('monto'))->toBe(180.0);
    expect((float) $matricula->installmentPlan->installments->first()->monto)->toBe(30.0);
    expect((int) $matricula->installmentPlan->installments->first()->cliente_matricula_id)->toBe((int) $matricula->id);
    expect($cliente->fresh()->deuda_total)->toBe(180.0);
});

it('generates monthly installment dates on the same day of the next month or end of month', function () {
    $schedule = app(EnrollmentInstallmentService::class)->previewSchedule([
        'monto_total' => 300,
        'cuota_inicial_monto' => 0,
        'numero_cuotas' => 3,
        'frecuencia' => 'mensual',
        'fecha_inicio' => '2026-04-16',
    ]);

    expect($schedule[0]['fecha_vencimiento'])->toBe('2026-04-16');
    expect($schedule[1]['fecha_vencimiento'])->toBe('2026-05-16');
    expect($schedule[2]['fecha_vencimiento'])->toBe('2026-06-16');

    $endOfMonthSchedule = app(EnrollmentInstallmentService::class)->previewSchedule([
        'monto_total' => 200,
        'cuota_inicial_monto' => 0,
        'numero_cuotas' => 2,
        'frecuencia' => 'mensual',
        'fecha_inicio' => '2026-01-31',
    ]);

    expect($endOfMonthSchedule[0]['fecha_vencimiento'])->toBe('2026-01-31');
    expect($endOfMonthSchedule[1]['fecha_vencimiento'])->toBe('2026-02-28');
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
