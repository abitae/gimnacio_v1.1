<?php

use App\Livewire\Enrollments\Installments\PaymentForm;
use App\Models\Core\Caja;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\EnrollmentInstallmentService;
use App\Services\SucursalContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    app(SucursalContext::class)->activate(biotimeSucursal('cuota-descuento'));
    foreach (['matricula_cliente.ver', 'matricula_cliente.editar', 'matricula_cliente.aplicar_descuento'] as $perm) {
        Permission::findOrCreate($perm, 'web');
    }
});

function cuotaParaDescuentoTest(User $user): EnrollmentInstallment
{
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
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'pendiente',
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    return $cuota;
}

it('rejects a discount at service level when the user lacks the permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['matricula_cliente.ver', 'matricula_cliente.editar']);
    $this->actingAs($user);

    $cuota = cuotaParaDescuentoTest($user);

    expect(fn () => app(EnrollmentInstallmentService::class)->pagarCuota($cuota, [
        'monto' => 100,
        'descuento_monto' => 50,
        'fecha_pago' => now()->toDateString(),
    ]))->toThrow(\InvalidArgumentException::class, 'permiso para aplicar descuentos');
});

it('allows a discount at service level when the user has the permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['matricula_cliente.ver', 'matricula_cliente.editar', 'matricula_cliente.aplicar_descuento']);
    $this->actingAs($user);

    $cuota = cuotaParaDescuentoTest($user);

    $pago = app(EnrollmentInstallmentService::class)->pagarCuota($cuota, [
        'monto' => 100,
        'descuento_monto' => 50,
        'fecha_pago' => now()->toDateString(),
        'pagos' => [['payment_method_id' => \App\Models\Core\PaymentMethod::factory()->create([
            'requiere_numero_operacion' => false,
            'requiere_entidad' => false,
        ])->id, 'monto' => 100]],
    ]);

    expect((float) $pago->descuento_monto)->toBe(50.0);
    expect($cuota->fresh()->estado)->toBe('pagada');
});

it('ignores a tampered discount value on the standalone payment form when the user lacks the permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['matricula_cliente.ver', 'matricula_cliente.editar']);
    $this->actingAs($user);

    $cuota = cuotaParaDescuentoTest($user);

    Livewire::test(PaymentForm::class, ['installment' => $cuota])
        ->set('form.descuento_monto', 50)
        ->assertSet('form.descuento_monto', 0.0);
});

it('lets a user with the permission see the discount field enabled and apply it', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['matricula_cliente.ver', 'matricula_cliente.editar', 'matricula_cliente.aplicar_descuento']);
    $this->actingAs($user);

    $cuota = cuotaParaDescuentoTest($user);

    Livewire::test(PaymentForm::class, ['installment' => $cuota])
        ->set('form.descuento_monto', 50)
        ->assertSet('form.descuento_monto', 50.0)
        ->assertSet('form.monto', 100.0);
});
