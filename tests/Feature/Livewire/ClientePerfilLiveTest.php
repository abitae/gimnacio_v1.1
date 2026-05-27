<?php

use App\Livewire\Clientes\ClienteLive;
use App\Livewire\Clientes\ClientePerfilLive;
use App\Models\Core\Asistencia;
use App\Models\Core\Caja;
use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    foreach (['cliente.ver', 'cliente.crear', 'cliente.editar', 'matricula_cliente.ver', 'matricula_cliente.crear', 'matricula_cliente.editar', 'checking.crear', 'checking.editar'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
    }
});

it('redirige invitados del perfil de clientes al login', function () {
    $this->get(route('clientes.perfil.index'))->assertRedirect(route('login'));
});

it('responde 200 en perfil index con permiso cliente.ver', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $this->get(route('clientes.perfil.index'))->assertOk();
});

it('responde 200 en listado /clientes con permiso cliente.ver', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $this->get(route('clientes.index'))->assertOk();
});

it('lanza autorización al abrir nuevo cliente sin permiso cliente.crear', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    Livewire::test(ClientePerfilLive::class)
        ->call('openClienteCreateModal')
        ->assertForbidden();
});

it('abre modal de nuevo cliente con permiso cliente.crear', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'cliente.crear']);
    $this->actingAs($user);

    Livewire::test(ClientePerfilLive::class)
        ->call('openClienteCreateModal')
        ->assertSet('clienteModalState.create', true);
});

it('super administrador puede abrir el modal de nueva matrícula sin permisos explícitos de matrícula', function () {
    $this->seed(RoleSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '90000099',
        'nombres' => 'Super',
        'apellidos' => 'Admin',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->call('openMatriculaCreateModal')
        ->assertSet('matriculaModalState.create', true);
});

it('renderiza el componente listado secundario', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    Livewire::test(ClienteLive::class)->assertOk();
});

it('selecciona cliente en perfil y fija la ficha', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
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
    $user->givePermissionTo('cliente.ver');
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
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.editar']);
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
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.editar', 'matricula_cliente.crear']);
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

it('permite registrar un pago a cuenta de una cuota desde el perfil', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.editar']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create(['nombre' => 'Plan Abono', 'precio_base' => 300, 'estado' => 'activa']);

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

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->call('openRegistrarPagoCuota', $cuota->id)
        ->assertSet('pagoCuotaForm.monto', '150')
        ->set('pagoCuotaForm.monto', '60')
        ->call('guardarPagoCuota')
        ->assertHasNoErrors();

    $cuota->refresh();
    expect($cuota->estado)->toBe('parcial');
    expect((float) $cuota->monto_pagado)->toBe(60.0);
    expect((float) $cuota->saldo_pendiente)->toBe(90.0);
    expect(Pago::query()->where('enrollment_installment_id', $cuota->id)->count())->toBe(1);
});

it('muestra empty state cuando no existen matriculas en cuotas', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->set('perfilFinanzasTab', 'cuotas_pendientes')
        ->assertSee('Este cliente no tiene membresías o clases matriculadas en cuotas.');
});

it('recalcula la cuota estimada al cambiar la frecuencia en una nueva matricula en cuotas', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.crear']);
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

it('muestra vista previa del cronograma al cambiar cuota inicial y fecha inicio en nueva matricula', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.crear']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->conCuotas()->create([
        'nombre' => 'Plan Calendario',
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
        ->set('matriculaForm.cuota_inicial_monto', 100)
        ->set('matriculaForm.fecha_inicio_plan_cuotas', '2026-04-16')
        ->assertSet('matriculaForm.frecuencia_cuotas', 'mensual')
        ->assertSet('matriculaSchedulePreview.0.fecha_vencimiento', '2026-04-16')
        ->assertSet('matriculaSchedulePreview.1.fecha_vencimiento', '2026-05-16')
        ->assertSet('matriculaSchedulePreview.2.fecha_vencimiento', '2026-06-16')
        ->assertSet('matriculaSchedulePreview.0.monto', 100.0)
        ->assertSee('16/05/2026')
        ->assertSee('16/06/2026')
        ->assertSee('S/ 200.00');
});

it('muestra en el resumen la suma de saldos de planes y membresias del cliente', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.editar']);
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
it('registra ingreso desde el perfil del cliente y refresca el estado de asistencia', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'checking.crear', 'checking.editar']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create([
        'nombre' => 'Plan Check In',
        'precio_base' => 120,
        'estado' => 'activa',
    ]);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 120,
        'descuento_monto' => 0,
        'precio_final' => 120,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->call('registrarIngresoPerfil')
        ->assertSee('Registrar salida')
        ->assertSee('Ingreso en curso desde');

    $asistencia = Asistencia::query()->where('cliente_id', $cliente->id)->latest('id')->first();

    expect($asistencia)->not->toBeNull()
        ->and($asistencia->fecha_hora_salida)->toBeNull();
});

it('registra salida desde el perfil del cliente cuando existe un ingreso en curso', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'checking.crear', 'checking.editar']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create([
        'nombre' => 'Plan Check Out',
        'precio_base' => 120,
        'estado' => 'activa',
    ]);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 120,
        'descuento_monto' => 0,
        'precio_final' => 120,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);

    $asistencia = Asistencia::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'fecha_hora_ingreso' => now()->subHour(),
        'fecha_hora_salida' => null,
        'origen' => 'manual',
        'valido_por_membresia' => true,
        'registrada_por' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSee('Registrar salida')
        ->call('registrarSalidaPerfil')
        ->assertSee('Registrar ingreso');

    expect($asistencia->fresh()->fecha_hora_salida)->not->toBeNull();
});
