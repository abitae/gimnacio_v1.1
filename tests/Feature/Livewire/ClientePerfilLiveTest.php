<?php

use App\Livewire\Clientes\ClienteLive;
use App\Livewire\Clientes\ClientePerfilLive;
use App\Models\Core\Asistencia;
use App\Models\Core\Caja;
use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use App\Models\User;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
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

it('filtra el listado de clientes por asesor de matrícula', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $asesorUno = User::factory()->create(['name' => 'Asesor Uno Test', 'estado' => 'activo']);
    $asesorUno->assignRole('vendedor');
    $asesorDos = User::factory()->create(['name' => 'Asesor Dos Test', 'estado' => 'activo']);
    $asesorDos->assignRole('vendedor');

    $clienteAsesorUno = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Asesor Uno Filtro',
        'created_by' => $user->id,
    ]);
    $clienteAsesorDos = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Asesor Dos Filtro',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::factory()->create(['nombre' => 'Plan Filtro', 'precio_base' => 100, 'estado' => 'activa']);

    ClienteMatricula::create([
        'cliente_id' => $clienteAsesorUno->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $asesorUno->id,
    ]);

    ClienteMatricula::create([
        'cliente_id' => $clienteAsesorDos->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $asesorDos->id,
    ]);

    Livewire::test(ClienteLive::class)
        ->set('asesorFilter', (string) $asesorUno->id)
        ->assertSee('Asesor Uno Filtro')
        ->assertDontSee('Asesor Dos Filtro');
});

it('el filtro asesor solo muestra vendedores activos de la sucursal actual', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = biotimeSucursal('asesor-filtro-a');
    $sucursalOtra = biotimeSucursal('asesor-filtro-b', false);

    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);
    session([SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    $asesorActivo = User::factory()->create(['name' => 'Asesor Activo Filtro', 'estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $asesorActivo->assignRole('ventas');
    $asesorActivo->sucursales()->attach($sucursal->id);

    $asesorInactivo = User::factory()->create(['name' => 'Asesor Inactivo Filtro', 'estado' => 'inactivo', 'default_sucursal_id' => $sucursal->id]);
    $asesorInactivo->assignRole('ventas');
    $asesorInactivo->sucursales()->attach($sucursal->id);

    $asesorOtraSucursal = User::factory()->create(['name' => 'Asesor Otra Sucursal Filtro', 'estado' => 'activo', 'default_sucursal_id' => $sucursalOtra->id]);
    $asesorOtraSucursal->assignRole('ventas');
    $asesorOtraSucursal->sucursales()->attach($sucursalOtra->id);

    $usuarioCaja = User::factory()->create(['name' => 'Usuario Caja Filtro', 'estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $usuarioCaja->assignRole('caja');
    $usuarioCaja->sucursales()->attach($sucursal->id);

    Livewire::test(ClienteLive::class)
        ->assertSee('Asesor Activo Filtro')
        ->assertDontSee('Asesor Inactivo Filtro')
        ->assertDontSee('Asesor Otra Sucursal Filtro')
        ->assertDontSee('Usuario Caja Filtro');
});

it('cambia el asesor en todas las matrículas del cliente desde el listado', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = biotimeSucursal('asesor-cambio');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.editar']);
    $this->actingAs($user);
    session([SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    $asesorOriginal = User::factory()->create(['name' => 'Asesor Original Cambio', 'estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $asesorOriginal->assignRole('ventas');
    $asesorOriginal->sucursales()->attach($sucursal->id);

    $asesorNuevo = User::factory()->create(['name' => 'Asesor Nuevo Cambio', 'estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $asesorNuevo->assignRole('ventas');
    $asesorNuevo->sucursales()->attach($sucursal->id);

    $cliente = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Cambio Asesor Test',
        'created_by' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]);

    $membresia = Membresia::factory()->create(['nombre' => 'Plan Cambio Asesor', 'precio_base' => 100, 'estado' => 'activa']);
    $clase = Clase::factory()->create(['nombre' => 'Clase Cambio Asesor']);

    $matriculaMembresia = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $asesorOriginal->id,
    ]);

    $matriculaClase = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'clase',
        'clase_id' => $clase->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 80,
        'descuento_monto' => 0,
        'precio_final' => 80,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $asesorOriginal->id,
    ]);

    Livewire::test(ClienteLive::class)
        ->call('abrirModalAsesor', $cliente->id)
        ->assertSet('clienteIdAsesor', $cliente->id)
        ->assertSet('asesorActualNombre', 'Asesor Original Cambio')
        ->set('nuevoAsesorId', (string) $asesorNuevo->id)
        ->call('guardarCambioAsesor')
        ->assertHasNoErrors()
        ->assertSet('mostrarModalAsesor', false);

    expect($matriculaMembresia->refresh()->asesor_id)->toBe($asesorNuevo->id)
        ->and($matriculaClase->refresh()->asesor_id)->toBe($asesorNuevo->id);
});

it('rechaza cambiar asesor a un vendedor de otra sucursal', function () {
    $this->seed(RoleSeeder::class);

    $sucursal = biotimeSucursal('asesor-rechazo-a');
    $sucursalOtra = biotimeSucursal('asesor-rechazo-b', false);

    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.editar']);
    $this->actingAs($user);
    session([SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    $asesorOriginal = User::factory()->create(['estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);
    $asesorOriginal->assignRole('ventas');
    $asesorOriginal->sucursales()->attach($sucursal->id);

    $asesorOtraSucursal = User::factory()->create(['estado' => 'activo', 'default_sucursal_id' => $sucursalOtra->id]);
    $asesorOtraSucursal->assignRole('ventas');
    $asesorOtraSucursal->sucursales()->attach($sucursalOtra->id);

    $cliente = Cliente::factory()->create(['created_by' => $user->id, 'sucursal_id' => $sucursal->id]);
    $membresia = Membresia::factory()->create(['precio_base' => 100, 'estado' => 'activa']);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $asesorOriginal->id,
    ]);

    Livewire::test(ClienteLive::class)
        ->call('abrirModalAsesor', $cliente->id)
        ->set('nuevoAsesorId', (string) $asesorOtraSucursal->id)
        ->call('guardarCambioAsesor')
        ->assertHasErrors(['nuevoAsesorId']);
});

it('filtra el listado de clientes por vigencia comercial', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $membresia = Membresia::factory()->create(['nombre' => 'Plan Vigencia', 'precio_base' => 100, 'estado' => 'activa']);

    $clienteActivo = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Plan Activo Filtro',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);
    $clientePorVencer = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Por Vencer Filtro',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);
    $clientePorIniciar = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Por Iniciar Filtro',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);
    $clienteInactivo = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Inactivo Filtro',
        'estado_cliente' => 'inactivo',
        'created_by' => $user->id,
    ]);

    ClienteMatricula::create([
        'cliente_id' => $clienteActivo->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(60)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
    ]);

    ClienteMatricula::create([
        'cliente_id' => $clientePorVencer->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->subDays(20)->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
    ]);

    ClienteMatricula::create([
        'cliente_id' => $clientePorIniciar->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->addDays(15)->toDateString(),
        'fecha_fin' => now()->addDays(45)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
    ]);

    Livewire::test(ClienteLive::class)
        ->set('vigenciaFilter', 'activos')
        ->assertSee('Plan Activo Filtro')
        ->assertSee('Por Vencer Filtro')
        ->assertDontSee('Por Iniciar Filtro')
        ->assertDontSee('Inactivo Filtro');

    Livewire::test(ClienteLive::class)
        ->set('vigenciaFilter', 'por_vencer')
        ->set('ventanaDias', 15)
        ->assertSee('Por Vencer Filtro')
        ->assertDontSee('Plan Activo Filtro')
        ->assertDontSee('Por Iniciar Filtro');

    Livewire::test(ClienteLive::class)
        ->set('vigenciaFilter', 'por_iniciar')
        ->assertSee('Por Iniciar Filtro')
        ->assertDontSee('Plan Activo Filtro');

    Livewire::test(ClienteLive::class)
        ->set('vigenciaFilter', 'inactivos')
        ->assertSee('Inactivo Filtro')
        ->assertDontSee('Plan Activo Filtro');
});

it('filtra el listado de clientes por tipo de membresía', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $membresiaBasica = Membresia::factory()->create(['nombre' => 'Plan Básico Filtro', 'precio_base' => 80, 'estado' => 'activa']);
    $membresiaPremium = Membresia::factory()->create(['nombre' => 'Plan Premium Filtro', 'precio_base' => 150, 'estado' => 'activa']);

    $clienteBasico = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Membresia Basica Filtro',
        'created_by' => $user->id,
    ]);
    $clientePremium = Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Membresia Premium Filtro',
        'created_by' => $user->id,
    ]);

    foreach ([[$clienteBasico, $membresiaBasica], [$clientePremium, $membresiaPremium]] as [$cliente, $membresia]) {
        ClienteMatricula::create([
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresia->id,
            'fecha_matricula' => now()->toDateString(),
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDays(30)->toDateString(),
            'estado' => 'activa',
            'precio_lista' => $membresia->precio_base,
            'descuento_monto' => 0,
            'precio_final' => $membresia->precio_base,
            'modalidad_pago' => 'contado',
            'requiere_plan_cuotas' => false,
            'cuota_inicial_monto' => 0,
        ]);
    }

    Livewire::test(ClienteLive::class)
        ->set('membresiaFilter', (string) $membresiaBasica->id)
        ->assertSee('Membresia Basica Filtro')
        ->assertDontSee('Membresia Premium Filtro');

    Livewire::test(ClienteLive::class)
        ->set('membresiaFilter', (string) $membresiaPremium->id)
        ->assertSee('Membresia Premium Filtro')
        ->assertDontSee('Membresia Basica Filtro');
});

it('muestra tarjetas de resumen en listado de clientes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Resumen Cards Test',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    Livewire::test(ClienteLive::class)
        ->assertSee('Total clientes')
        ->assertSee('Clientes activos')
        ->assertSee('Clientes inactivos')
        ->assertSee('Por vencer')
        ->assertSee('Por iniciar')
        ->assertSee('Traspasos')
        ->assertSee('Asistencias')
        ->assertSee('Inasistencias');
});

it('muestra botón exportar excel en listado de clientes', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    Livewire::test(ClienteLive::class)
        ->assertSee('Exportar Excel');
});

it('exporta excel del listado de clientes con filtros', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Export Excel Test',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);
    Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Export Excel Oculto',
        'estado_cliente' => 'inactivo',
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('clientes.index.exportar.excel', [
        'estado' => 'activo',
    ]));

    $response->assertOk();
});

it('filtra el listado de clientes por estado del cliente', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Estado Activo Filtro',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);
    Cliente::factory()->create([
        'nombres' => 'Cliente',
        'apellidos' => 'Estado Inactivo Filtro',
        'estado_cliente' => 'inactivo',
        'created_by' => $user->id,
    ]);

    Livewire::test(ClienteLive::class)
        ->set('estadoFilter', 'activo')
        ->assertSee('Estado Activo Filtro')
        ->assertDontSee('Estado Inactivo Filtro');

    Livewire::test(ClienteLive::class)
        ->set('estadoFilter', 'inactivo')
        ->assertSee('Estado Inactivo Filtro')
        ->assertDontSee('Estado Activo Filtro');
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
        ->assertSee('Pagar')
        ->assertSee('Pendiente')
        ->assertDontSee('Pagada');
});

it('permite registrar un pago a cuenta de una cuota desde el perfil', function () {
    $sucursal = biotimeSucursal('perfil-pago-cuota');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo(['cliente.ver', 'matricula_cliente.ver', 'matricula_cliente.editar']);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    PaymentMethod::create([
        'nombre' => 'Efectivo',
        'estado' => 'activo',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

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

it('resetea el acceso de la app del cliente', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'cliente.editar']);
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create([
        'created_by' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]);
    $account = ClienteAppAccount::factory()->create(['cliente_id' => $cliente->id]);
    $account->createToken('mobile');

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->call('resetearAccesoApp');

    expect(ClienteAppAccount::query()->where('cliente_id', $cliente->id)->exists())->toBeFalse();
});

it('crea la cuenta de la app desde el listado de clientes', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'cliente.editar']);
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create([
        'nombres' => 'Lucia',
        'apellidos' => 'App Password',
        'created_by' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]);

    Livewire::test(ClienteLive::class)
        ->call('abrirModalPassword', $cliente->id)
        ->assertSet('mostrarModalPassword', true)
        ->assertSet('tieneCuentaApp', false)
        ->set('passwordApp', 'nuevaClave9')
        ->set('passwordAppConfirmation', 'nuevaClave9')
        ->call('guardarPasswordApp')
        ->assertHasNoErrors()
        ->assertSet('mostrarModalPassword', false);

    $account = ClienteAppAccount::query()->where('cliente_id', $cliente->id)->first();

    expect($account)->not->toBeNull()
        ->and(Hash::check('nuevaClave9', $account->password))->toBeTrue();
});

it('cambia la contraseña de la app desde el listado y cierra sesiones', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $user->givePermissionTo(['cliente.ver', 'cliente.editar']);
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create([
        'nombres' => 'Mario',
        'apellidos' => 'Clave App',
        'created_by' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]);
    $account = ClienteAppAccount::factory()->create([
        'cliente_id' => $cliente->id,
        'password' => 'secreto123',
    ]);
    $account->createToken('mobile');

    Livewire::test(ClienteLive::class)
        ->call('abrirModalPassword', $cliente->id)
        ->assertSet('tieneCuentaApp', true)
        ->set('passwordApp', 'otraClave88')
        ->set('passwordAppConfirmation', 'otraClave88')
        ->call('guardarPasswordApp')
        ->assertHasNoErrors();

    expect(Hash::check('otraClave88', $account->fresh()->password))->toBeTrue()
        ->and($account->tokens()->count())->toBe(0);
});

it('no permite cambiar la contraseña de la app sin permiso cliente.editar', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    Livewire::test(ClienteLive::class)
        ->call('abrirModalPassword', $cliente->id)
        ->assertForbidden();
});
