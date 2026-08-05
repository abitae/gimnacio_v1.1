<?php

use App\Livewire\Clientes\ClientePerfilLive;
use App\Livewire\Enrollments\Installments\Schedule;
use App\Livewire\POS\POSLive;
use App\Livewire\Reportes\ReporteCuotasVencidasLive;
use App\Models\Core\Caja;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use App\Models\User;
use App\Services\EnrollmentInstallmentService;
use App\Services\SucursalContext;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Cache::flush();
    $guard = config('auth.defaults.guard');
    foreach ([
        'cliente.ver',
        'matricula_cliente.ver',
        'matricula_cliente.editar',
        'punto_venta.ver',
        'reporte.cuotas_vencidas',
    ] as $permission) {
        Permission::firstOrCreate(['name' => $permission, 'guard_name' => $guard]);
    }
});

function prepararInterfazPagoMixto(string $codigo, string $modalidad = 'cuotas'): array
{
    $sucursal = biotimeSucursal($codigo);
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo([
        'cliente.ver',
        'matricula_cliente.ver',
        'matricula_cliente.editar',
        'punto_venta.ver',
        'reporte.cuotas_vencidas',
    ]);
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
    $membresia = Membresia::factory()->create([
        'nombre' => 'Plan Livewire Mixto',
        'precio_base' => 190,
        'estado' => 'activa',
    ]);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
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
        'asesor_id' => $user->id,
    ]);
    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now()->subMinute(),
        'estado' => 'abierta',
    ]);

    return compact('sucursal', 'user', 'efectivo', 'yape', 'cliente', 'membresia', 'matricula', 'caja');
}

function agregarCuotasInterfazPagoMixto(array $ctx): array
{
    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $ctx['cliente']->id,
        'cliente_matricula_id' => $ctx['matricula']->id,
        'monto_total' => 190,
        'numero_cuotas' => 2,
        'monto_cuota' => 95,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);
    $primera = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $ctx['matricula']->id,
        'numero_cuota' => 1,
        'monto' => 95,
        'fecha_vencimiento' => now()->subDay()->toDateString(),
        'estado' => 'vencida',
    ]);
    $segunda = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $ctx['matricula']->id,
        'numero_cuota' => 2,
        'monto' => 95,
        'fecha_vencimiento' => now()->toDateString(),
        'estado' => 'pendiente',
    ]);

    return compact('plan', 'primera', 'segunda');
}

it('cobra una cuota con dos formas desde el cronograma y bloquea la siguiente', function () {
    $ctx = prepararInterfazPagoMixto('ui-cronograma');
    $cuotas = agregarCuotasInterfazPagoMixto($ctx);

    Livewire::test(Schedule::class, ['cliente' => $ctx['cliente']])
        ->assertSee('Primero paga la cuota pendiente más inmediata.')
        ->call('openRegistrarPagoCuota', $cuotas['segunda']->id)
        ->assertSet('pagoCuotaInstallmentId', null)
        ->call('openRegistrarPagoCuota', $cuotas['primera']->id)
        ->assertSet('pagoCuotaForm.monto', '95')
        ->assertSet('pagoCuotaForm.pagos.0.payment_method_id', $ctx['efectivo']->id)
        ->call('agregarFormaPagoCuota')
        ->set('pagoCuotaForm.pagos.0.monto', 50)
        ->set('pagoCuotaForm.pagos.1.payment_method_id', $ctx['yape']->id)
        ->set('pagoCuotaForm.pagos.1.monto', 45)
        ->set('pagoCuotaForm.pagos.1.numero_operacion', 'YP-LW-45')
        ->call('guardarPagoCuota')
        ->assertHasNoErrors()
        ->assertSet('cuotaPagoModalAbierto', false);

    $pago = Pago::query()->where('enrollment_installment_id', $cuotas['primera']->id)->firstOrFail();
    expect($pago->detalles)->toHaveCount(2)
        ->and($cuotas['primera']->refresh()->estado)->toBe('pagada')
        ->and(app(EnrollmentInstallmentService::class)->isFirstPayableInstallment($cuotas['segunda']->fresh()))->toBeTrue();
});

it('muestra la restricción de orden en perfil y cuotas vencidas', function () {
    $ctx = prepararInterfazPagoMixto('ui-prioridad');
    agregarCuotasInterfazPagoMixto($ctx);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $ctx['cliente']->id)
        ->set('perfilFinanzasTab', 'cuotas_pendientes')
        ->assertSee('Primero paga la cuota pendiente más inmediata.');

    Livewire::test(ReporteCuotasVencidasLive::class)
        ->assertSee('Primero paga la cuota pendiente más inmediata.');
});

it('cobra una matrícula con dos formas desde el POS y abre un único ticket', function () {
    $ctx = prepararInterfazPagoMixto('ui-pos', 'contado');

    Livewire::test(POSLive::class)
        ->call('openCobroModal', 'matricula', $ctx['matricula']->id)
        ->assertSet('cobroFormData.pagos.0.payment_method_id', $ctx['efectivo']->id)
        ->call('agregarFormaCobroPos')
        ->set('cobroFormData.pagos.0.monto', 100)
        ->set('cobroFormData.pagos.1.payment_method_id', $ctx['yape']->id)
        ->set('cobroFormData.pagos.1.monto', 90)
        ->set('cobroFormData.pagos.1.numero_operacion', 'YP-POS-90')
        ->call('procesarCobro')
        ->assertSet('mostrarModalTicketPagoCobro', true);

    $pago = Pago::query()->where('cliente_matricula_id', $ctx['matricula']->id)->firstOrFail();
    expect($pago->detalles)->toHaveCount(2)
        ->and($pago->metodo_pago)->toBe('Mixto');

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $ctx['cliente']->id)
        ->set('perfilFinanzasTab', 'pagos')
        ->assertSee('Efectivo S/ 100.00 + Yape S/ 90.00');
});
