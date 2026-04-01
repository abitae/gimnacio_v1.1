<?php

use App\Livewire\Clientes\ClienteLive;
use App\Livewire\Clientes\ClientePerfilLive;
use App\Models\Core\Cliente;
use App\Models\Core\Clase;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    foreach (['clientes.view', 'clientes.create', 'clientes.update', 'cliente-matriculas.view', 'cliente-matriculas.create', 'cliente-matriculas.update'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
    }
});

it('redirige invitados del perfil de clientes al login', function () {
    $this->get(route('clientes.perfil.index'))->assertRedirect(route('login'));
});

it('responde 200 en perfil index con permiso clientes.view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $this->get(route('clientes.perfil.index'))->assertOk();
});

it('responde 200 en listado /clientes con permiso clientes.view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $this->get(route('clientes.index'))->assertOk();
});

it('lanza autorización al abrir nuevo cliente sin permiso clientes.create', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    Livewire::test(ClientePerfilLive::class)
        ->call('openClienteCreateModal')
        ->assertForbidden();
});

it('abre modal de nuevo cliente con permiso clientes.create', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'clientes.create']);
    $this->actingAs($user);

    Livewire::test(ClientePerfilLive::class)
        ->call('openClienteCreateModal')
        ->assertSet('clienteModalState.create', true);
});

it('renderiza el componente listado secundario', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    Livewire::test(ClienteLive::class)->assertOk();
});

it('selecciona cliente en perfil y fija la ficha', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '90000001',
        'nombres' => 'Ana',
        'apellidos' => 'Prueba',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSet('selectedClienteId', $cliente->id);
});

it('permite minimizar y expandir el card del perfil del cliente', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '90000002',
        'nombres' => 'Luis',
        'apellidos' => 'Compacto',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSet('perfilClienteMinimizado', false)
        ->call('togglePerfilClienteMinimizado')
        ->assertSet('perfilClienteMinimizado', true)
        ->call('togglePerfilClienteMinimizado')
        ->assertSet('perfilClienteMinimizado', false);
});

it('muestra pagos agrupados por matricula y permite cobrar solo si hay saldo', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'cliente-matriculas.view', 'cliente-matriculas.update']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create(['nombre' => 'Plan Oro', 'precio_base' => 180, 'estado' => 'activa']);
    $clase = Clase::factory()->create(['nombre' => 'Box Funcional', 'tipo' => 'paquete', 'precio_paquete' => 90, 'sesiones_paquete' => 8]);

    $matriculaPendiente = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 180,
        'descuento_monto' => 0,
        'precio_final' => 180,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matriculaPendiente->id,
        'monto' => 50,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 130,
        'registrado_por' => $user->id,
    ]);

    $matriculaPagada = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'clase',
        'clase_id' => $clase->id,
        'fecha_matricula' => now()->subDay()->toDateString(),
        'fecha_inicio' => now()->subDay()->toDateString(),
        'fecha_fin' => now()->addDays(15)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 90,
        'descuento_monto' => 0,
        'precio_final' => 90,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'sesiones_totales' => 8,
        'sesiones_usadas' => 0,
        'asesor_id' => $user->id,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matriculaPagada->id,
        'monto' => 90,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => false,
        'saldo_pendiente' => 0,
        'registrado_por' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSee('Plan Oro')
        ->assertSee('Box Funcional')
        ->assertSee('Cobrar')
        ->assertSee('Contado');
});

it('muestra matriculas en cuotas y solo permite pagar cuotas pendientes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'cliente-matriculas.view', 'cliente-matriculas.update', 'cliente-matriculas.create']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create(['nombre' => 'Plan Cuotas', 'precio_base' => 300, 'estado' => 'activa']);

    $matriculaCuotas = ClienteMatricula::create([
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
        'cliente_matricula_id' => $matriculaCuotas->id,
        'monto_total' => 250,
        'numero_cuotas' => 2,
        'monto_cuota' => 125,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);

    EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matriculaCuotas->id,
        'numero_cuota' => 1,
        'monto' => 125,
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'pendiente',
    ]);

    EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matriculaCuotas->id,
        'numero_cuota' => 2,
        'monto' => 125,
        'fecha_vencimiento' => now()->addDays(35)->toDateString(),
        'estado' => 'pagada',
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->set('perfilFinanzasTab', 'cuotas_pendientes')
        ->assertSee('Plan Cuotas')
        ->assertSee('Pagar');
});

it('muestra empty state cuando no existen matriculas en cuotas', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'cliente-matriculas.view']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->set('perfilFinanzasTab', 'cuotas_pendientes')
        ->assertSee('Este cliente no tiene membresías o clases matriculadas en cuotas.');
});

it('recalcula la cuota estimada al cambiar la frecuencia en una nueva matricula en cuotas', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'cliente-matriculas.view', 'cliente-matriculas.create']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create([
        'nombre' => 'Plan Reactivo',
        'duracion_dias' => 90,
        'precio_base' => 300,
        'cuota_inicial_monto' => 0,
        'frecuencia_cuotas_default' => 'mensual',
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->call('openMatriculaCreateModal')
        ->set('matriculaForm.tipo', 'membresia')
        ->set('matriculaForm.membresia_id', (string) $membresia->id)
        ->set('matriculaForm.modalidad_pago', 'cuotas')
        ->assertSet('matriculaForm.numero_cuotas', 3)
        ->assertSee('S/ 100.00')
        ->set('matriculaForm.frecuencia_cuotas', 'quincenal')
        ->assertSet('matriculaForm.numero_cuotas', 6)
        ->assertSee('S/ 50.00');
});

it('muestra en el resumen la suma de saldos de planes y membresias del cliente', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'cliente-matriculas.view', 'cliente-matriculas.update']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresiaContado = Membresia::factory()->create(['nombre' => 'Plan Saldo', 'precio_base' => 180, 'estado' => 'activa']);
    $membresiaCuotas = Membresia::factory()->conCuotas()->create(['nombre' => 'Plan Cuotas Saldo', 'precio_base' => 300, 'estado' => 'activa']);

    $matriculaContado = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresiaContado->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 180,
        'descuento_monto' => 0,
        'precio_final' => 180,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matriculaContado->id,
        'monto' => 50,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 130,
        'registrado_por' => $user->id,
    ]);

    $matriculaCuotas = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresiaCuotas->id,
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
        'cliente_matricula_id' => $matriculaCuotas->id,
        'monto_total' => 250,
        'numero_cuotas' => 2,
        'monto_cuota' => 125,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);

    EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matriculaCuotas->id,
        'numero_cuota' => 1,
        'monto' => 125,
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'pendiente',
    ]);

    EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matriculaCuotas->id,
        'numero_cuota' => 2,
        'monto' => 125,
        'fecha_vencimiento' => now()->addDays(35)->toDateString(),
        'estado' => 'pagada',
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSee('Debe S/ 255.00 en membresía');
});
