<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\PagoDetalle;
use App\Models\Core\PaymentMethod;
use App\Models\User;
use App\Services\ClienteMatriculaService;
use App\Services\EnrollmentInstallmentService;
use App\Services\SucursalContext;

function prepararPagoMixtoMatricula(string $codigo): array
{
    $sucursal = biotimeSucursal($codigo);
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    test()->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $efectivo = PaymentMethod::create([
        'nombre' => 'Efectivo',
        'estado' => 'activo',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);
    $yape = PaymentMethod::create([
        'nombre' => 'Yape',
        'estado' => 'activo',
        'requiere_numero_operacion' => true,
        'requiere_entidad' => false,
    ]);
    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create(['precio_base' => 190, 'estado' => 'activa']);
    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now()->subMinute(),
        'estado' => 'abierta',
    ]);

    return compact('sucursal', 'user', 'efectivo', 'yape', 'cliente', 'membresia', 'caja');
}

function crearMatriculaPagoMixto(array $ctx, string $modalidad = 'contado'): ClienteMatricula
{
    return ClienteMatricula::create([
        'cliente_id' => $ctx['cliente']->id,
        'tipo' => 'membresia',
        'membresia_id' => $ctx['membresia']->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addMonth()->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 190,
        'descuento_monto' => 0,
        'precio_final' => 190,
        'modalidad_pago' => $modalidad,
        'requiere_plan_cuotas' => $modalidad === 'cuotas',
        'cuota_inicial_monto' => 0,
        'asesor_id' => $ctx['user']->id,
    ]);
}

it('registra un pago mixto de matrícula con un comprobante y dos entradas de caja', function () {
    $ctx = prepararPagoMixtoMatricula('pago-mixto-contado');
    $matricula = crearMatriculaPagoMixto($ctx);

    $pago = app(ClienteMatriculaService::class)->procesarPago($matricula->id, [
        'monto_pago' => 190,
        'fecha_pago' => now(),
        'pagos' => [
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 100],
            ['payment_method_id' => $ctx['yape']->id, 'monto' => 90, 'numero_operacion' => 'YP-9001'],
        ],
    ]);

    expect($pago->metodo_pago)->toBe('Mixto')
        ->and($pago->payment_method_id)->toBeNull()
        ->and((float) $pago->monto)->toBe(190.0)
        ->and($pago->comprobante_numero)->not->toBeNull()
        ->and($pago->detalles)->toHaveCount(2)
        ->and((float) $pago->detalles->sum('monto'))->toBe(190.0)
        ->and($pago->metodosPagoResumen())->toContain('Efectivo S/ 100.00', 'Yape S/ 90.00');

    $movimientos = CajaMovimiento::query()
        ->where('caja_id', $ctx['caja']->id)
        ->where('referencia_tipo', PagoDetalle::class)
        ->orderBy('id')
        ->get();

    $ticketIds = collect($ctx['caja']->fresh()->movimientosNormalizados())->pluck('ticket_pago_id')->unique()->values();
    expect($movimientos)->toHaveCount(2)
        ->and((float) $movimientos->sum('monto'))->toBe(190.0)
        ->and($ticketIds->all())->toBe([$pago->id]);
});

it('mantiene la cuota parcial como próxima y habilita la siguiente solo al completarla', function () {
    $ctx = prepararPagoMixtoMatricula('pago-mixto-cuotas');
    $matricula = crearMatriculaPagoMixto($ctx, 'cuotas');
    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $ctx['cliente']->id,
        'cliente_matricula_id' => $matricula->id,
        'monto_total' => 380,
        'numero_cuotas' => 2,
        'monto_cuota' => 190,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);
    $primera = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 1,
        'monto' => 190,
        'fecha_vencimiento' => now()->addDay()->toDateString(),
        'estado' => 'pendiente',
    ]);
    $segunda = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 2,
        'monto' => 190,
        'fecha_vencimiento' => now()->addMonth()->toDateString(),
        'estado' => 'pendiente',
    ]);
    $service = app(EnrollmentInstallmentService::class);

    expect(fn () => $service->pagarCuota($segunda, [
        'monto' => 10,
        'pagos' => [['payment_method_id' => $ctx['efectivo']->id, 'monto' => 10]],
    ]))->toThrow(InvalidArgumentException::class, 'cuota pendiente más inmediata');

    $pagoParcial = $service->pagarCuota($primera, [
        'monto' => 100,
        'pagos' => [
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 60],
            ['payment_method_id' => $ctx['yape']->id, 'monto' => 40, 'numero_operacion' => 'YP-4001'],
        ],
    ]);

    expect($pagoParcial->detalles)->toHaveCount(2)
        ->and($primera->refresh()->estado)->toBe('parcial')
        ->and($service->isFirstPayableInstallment($primera->fresh()))->toBeTrue()
        ->and($service->isFirstPayableInstallment($segunda->fresh()))->toBeFalse();

    $service->pagarCuota($primera->fresh(), [
        'monto' => 90,
        'pagos' => [['payment_method_id' => $ctx['efectivo']->id, 'monto' => 90]],
    ]);

    expect($primera->refresh()->estado)->toBe('pagada')
        ->and($service->isFirstPayableInstallment($segunda->fresh()))->toBeTrue();
});

it('valida la distribución, métodos duplicados y datos requeridos', function () {
    $ctx = prepararPagoMixtoMatricula('pago-mixto-validacion');
    $matricula = crearMatriculaPagoMixto($ctx);
    $service = app(ClienteMatriculaService::class);

    expect(fn () => $service->procesarPago($matricula->id, [
        'monto_pago' => 190,
        'pagos' => [
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 100],
            ['payment_method_id' => $ctx['yape']->id, 'monto' => 80, 'numero_operacion' => 'YP-80'],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'suma de las formas');

    expect(fn () => $service->procesarPago($matricula->id, [
        'monto_pago' => 190,
        'pagos' => [
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 100],
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 90],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'repetir el mismo método');

    expect(fn () => $service->procesarPago($matricula->id, [
        'monto_pago' => 190,
        'pagos' => [
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 100],
            ['payment_method_id' => $ctx['yape']->id, 'monto' => 90],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'requiere número de operación');

    $transferencia = PaymentMethod::create([
        'nombre' => 'Transferencia',
        'estado' => 'activo',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => true,
    ]);
    expect(fn () => $service->procesarPago($matricula->id, [
        'monto_pago' => 190,
        'pagos' => [['payment_method_id' => $transferencia->id, 'monto' => 190]],
    ]))->toThrow(InvalidArgumentException::class, 'requiere entidad financiera');

    expect(fn () => $service->procesarPago($matricula->id, [
        'monto_pago' => 190,
        'pagos' => [
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 50],
            ['payment_method_id' => $ctx['yape']->id, 'monto' => 70, 'numero_operacion' => 'YP-70'],
            ['payment_method_id' => $ctx['efectivo']->id, 'monto' => 70],
        ],
    ]))->toThrow(InvalidArgumentException::class, 'una o dos formas');

    expect(fn () => $service->procesarPago($matricula->id, [
        'monto_pago' => 191,
        'pagos' => [['payment_method_id' => $ctx['efectivo']->id, 'monto' => 191]],
    ]))->toThrow(Exception::class, 'mayor al saldo pendiente');

    expect(PagoDetalle::query()->count())->toBe(0);
});

it('mantiene compatibilidad con pago singular y pagos históricos sin detalles', function () {
    $ctx = prepararPagoMixtoMatricula('pago-singular-compatible');
    $matricula = crearMatriculaPagoMixto($ctx);

    $pago = app(ClienteMatriculaService::class)->procesarPago($matricula->id, [
        'monto_pago' => 40,
        'payment_method_id' => $ctx['efectivo']->id,
        'metodo_pago' => 'Efectivo',
    ]);

    expect($pago->detalles)->toHaveCount(1)
        ->and($pago->metodo_pago)->toBe('Efectivo')
        ->and($pago->payment_method_id)->toBe($ctx['efectivo']->id)
        ->and(CajaMovimiento::query()->where('referencia_tipo', PagoDetalle::class)->count())->toBe(1);

    $historico = \App\Models\Core\Pago::create([
        'cliente_id' => $ctx['cliente']->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 10,
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'registrado_por' => $ctx['user']->id,
        'sucursal_id' => $ctx['sucursal']->id,
    ]);

    expect($historico->detalles)->toHaveCount(0)
        ->and($historico->metodosPagoResumen())->toBe('efectivo');
});

it('habilita una próxima cuota independiente para cada matrícula del cliente', function () {
    $ctx = prepararPagoMixtoMatricula('cuotas-por-matricula');
    $service = app(EnrollmentInstallmentService::class);
    $primeras = collect();
    $segundas = collect();
    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $ctx['cliente']->id,
        'cliente_matricula_id' => null,
        'monto_total' => 380,
        'numero_cuotas' => 4,
        'monto_cuota' => 95,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);

    foreach (range(1, 2) as $indice) {
        $matricula = crearMatriculaPagoMixto($ctx, 'cuotas');
        $primeras->push(EnrollmentInstallment::create([
            'enrollment_installment_plan_id' => $plan->id,
            'cliente_matricula_id' => $matricula->id,
            'numero_cuota' => 1,
            'monto' => 95,
            'fecha_vencimiento' => now()->addDays($indice)->toDateString(),
            'estado' => 'pendiente',
        ]));
        $segundas->push(EnrollmentInstallment::create([
            'enrollment_installment_plan_id' => $plan->id,
            'cliente_matricula_id' => $matricula->id,
            'numero_cuota' => 2,
            'monto' => 95,
            'fecha_vencimiento' => now()->addMonth()->addDays($indice)->toDateString(),
            'estado' => 'pendiente',
        ]));
    }

    expect($primeras->every(fn ($cuota) => $service->isFirstPayableInstallment($cuota)))->toBeTrue()
        ->and($segundas->every(fn ($cuota) => ! $service->isFirstPayableInstallment($cuota)))->toBeTrue();
});
