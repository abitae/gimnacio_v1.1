<?php

use App\Livewire\POS\POSLive;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    Permission::firstOrCreate(['name' => 'punto_venta.ver', 'guard_name' => $guard]);
});

function crearClienteConCuotaPendiente(User $user): array
{
    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create([
        'nombre' => 'Plan POS Cuotas',
        'precio_base' => 300,
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
        'precio_lista' => 300,
        'descuento_monto' => 0,
        'precio_final' => 300,
        'modalidad_pago' => 'cuotas',
        'requiere_plan_cuotas' => true,
        'cuota_inicial_monto' => 50,
        'asesor_id' => $user->id,
    ]);

    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto_total' => 250,
        'numero_cuotas' => 2,
        'monto_cuota' => 125,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);

    $cuota = EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 1,
        'monto' => 125,
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'pendiente',
    ]);

    return [$cliente, $cuota];
}

it('get pos con cobrar_cliente muestra el cliente en modo cobro', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('punto_venta.ver');
    [$cliente] = crearClienteConCuotaPendiente($user);

    $this->actingAs($user)
        ->get(route('pos.index', ['cobrar_cliente' => $cliente->id]))
        ->assertOk()
        ->assertSee($cliente->nombres);
});

it('abre modo cobro y selecciona cliente con irACobrarCliente', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('punto_venta.ver');
    [$cliente] = crearClienteConCuotaPendiente($user);

    Livewire::actingAs($user)
        ->test(POSLive::class)
        ->call('irACobrarCliente', $cliente->id)
        ->assertSet('modoCobroMembresiaClase', true)
        ->tap(function ($component) use ($cliente) {
            expect($component->get('selectedClienteCobro'))->not->toBeNull();
            expect($component->get('selectedClienteCobro')->id)->toBe($cliente->id);
        });
});

it('lista cuota pendiente al seleccionar cliente en POS cobro', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('punto_venta.ver');
    [$cliente, $cuota] = crearClienteConCuotaPendiente($user);

    Livewire::actingAs($user)
        ->test(POSLive::class)
        ->call('activarModoCobroMembresiaClase')
        ->call('selectClienteCobro', $cliente->id)
        ->assertSet('selectedClienteCobro.id', $cliente->id)
        ->tap(function ($component) use ($cuota) {
            $items = $component->get('itemsConSaldo');
            expect($items)->toBeArray();
            $cuotas = collect($items)->where('tipo', 'cuota');
            expect($cuotas)->not->toBeEmpty();
            expect($cuotas->first()['id'])->toBe($cuota->id);
        })
        ->assertSee('/cuotas/'.$cuota->id.'/pagar');
});
