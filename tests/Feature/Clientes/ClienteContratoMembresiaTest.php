<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ClienteContratoMembresiaService;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    Permission::firstOrCreate(['name' => 'cliente.ver', 'guard_name' => $guard]);
});

it('genera datos del contrato con información del cliente y matrícula', function () {
    $user = User::factory()->create(['name' => 'Asesor Contrato']);
    $cliente = Cliente::factory()->create([
        'nombres' => 'Juan',
        'apellidos' => 'Pérez Contrato',
        'numero_documento' => '12345678',
        'telefono' => '999888777',
        'codigo' => '10025',
        'direccion' => 'Av. Prueba 123',
        'fecha_nacimiento' => '1995-05-10',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::factory()->create([
        'nombre' => 'Plan Mensual',
        'duracion_dias' => 30,
        'precio_base' => 120,
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

    $datos = app(ClienteContratoMembresiaService::class)->datosContrato($cliente);

    expect($datos['afiliado_nombre'])->toBe('Juan Pérez Contrato')
        ->and($datos['afiliado_dni'])->toBe('12345678')
        ->and($datos['asesor_nombre'])->toBe('Asesor Contrato')
        ->and($datos['tipo_membresia']['mensual'])->toBeTrue();
});

it('responde pdf del contrato de membresía con permiso cliente.ver', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('cliente.ver');
    $this->actingAs($user);

    $cliente = Cliente::factory()->create();

    $response = $this->get(route('clientes.contrato-membresia.pdf', ['cliente' => $cliente->id]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});
