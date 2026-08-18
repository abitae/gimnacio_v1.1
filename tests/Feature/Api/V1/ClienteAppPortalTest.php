<?php

use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;

function portalClienteConCuenta(array $clienteOverrides = []): array
{
    $sucursal = biotimeSucursal();
    $staff = User::factory()->create();
    $cliente = Cliente::factory()->create(array_merge([
        'sucursal_id' => $sucursal->id,
        'created_by' => $staff->id,
        'tipo_documento' => 'DNI',
        'nombres' => 'Luis',
        'apellidos' => 'Paredes',
        'datos_salud' => ['enfermedades' => 'secreto'],
    ], $clienteOverrides));
    $account = ClienteAppAccount::factory()->create(['cliente_id' => $cliente->id]);
    $token = clienteAppToken($account);

    return compact('sucursal', 'staff', 'cliente', 'account', 'token');
}

it('devuelve el perfil del socio autenticado sin datos de salud', function () {
    ['cliente' => $cliente, 'token' => $token, 'sucursal' => $sucursal] = portalClienteConCuenta();

    $this->getJson('/api/v1/me', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('data.id', $cliente->id)
        ->assertJsonPath('data.nombres', 'Luis')
        ->assertJsonPath('data.sucursal.id', $sucursal->id)
        ->assertJsonMissingPath('data.datos_salud')
        ->assertJsonMissing(['enfermedades' => 'secreto']);
});

it('lista membresías y clases del socio autenticado', function () {
    ['cliente' => $cliente, 'token' => $token, 'sucursal' => $sucursal, 'staff' => $staff] = portalClienteConCuenta();

    $plan = Membresia::factory()->create([
        'nombre' => 'Plan Mensual',
        'tipo_acceso' => 'ilimitado',
        'estado' => 'activa',
        'sucursal_id' => $sucursal->id,
    ]);
    $clase = Clase::factory()->create([
        'nombre' => 'Spinning',
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
        'instructor_id' => $staff->id,
    ]);

    $activa = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $plan->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(20)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'sucursal_id' => $sucursal->id,
    ]);
    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'clase',
        'clase_id' => $clase->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 50,
        'descuento_monto' => 0,
        'precio_final' => 50,
        'sucursal_id' => $sucursal->id,
        'sesiones_totales' => 8,
        'sesiones_usadas' => 2,
    ]);

    $response = $this->getJson('/api/v1/membresias', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonCount(2, 'data');

    expect($response->json('data.0.id'))->toBe($activa->id)
        ->and($response->json('data.0.nombre'))->toBe('Plan Mensual')
        ->and($response->json('data.0.tipo_acceso'))->toBe('ilimitado')
        ->and($response->json('data.0.dias_restantes'))->toBeGreaterThan(0);
});

it('no expone membresías de otro cliente', function () {
    ['token' => $tokenA, 'sucursal' => $sucursal, 'staff' => $staff] = portalClienteConCuenta();
    $otro = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $staff->id,
    ]);
    $plan = Membresia::factory()->create([
        'nombre' => 'Plan Ajeno',
        'estado' => 'activa',
        'sucursal_id' => $sucursal->id,
    ]);
    ClienteMatricula::create([
        'cliente_id' => $otro->id,
        'tipo' => 'membresia',
        'membresia_id' => $plan->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(15)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 80,
        'descuento_monto' => 0,
        'precio_final' => 80,
        'sucursal_id' => $sucursal->id,
    ]);

    $this->getJson('/api/v1/membresias', [
        'Authorization' => 'Bearer '.$tokenA,
    ])->assertOk()
        ->assertJsonCount(0, 'data');
});

it('lista pagos pendientes reutilizando el resumen operativo', function () {
    ['cliente' => $cliente, 'token' => $token, 'sucursal' => $sucursal, 'staff' => $staff] = portalClienteConCuenta();
    $plan = Membresia::factory()->create([
        'nombre' => 'Plan Deuda',
        'estado' => 'activa',
        'precio_base' => 200,
        'sucursal_id' => $sucursal->id,
    ]);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $plan->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(25)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 200,
        'descuento_monto' => 0,
        'precio_final' => 200,
        'modalidad_pago' => 'contado',
        'sucursal_id' => $sucursal->id,
    ]);
    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 50,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 150,
        'registrado_por' => $staff->id,
        'sucursal_id' => $sucursal->id,
    ]);

    $this->getJson('/api/v1/pagos/pendientes', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('total_pendiente', 150)
        ->assertJsonPath('tiene_deuda', true)
        ->assertJsonPath('data.0.tipo', 'matricula')
        ->assertJsonPath('data.0.saldo_pendiente', 150);
});

it('lista pagos realizados solo del socio autenticado', function () {
    ['cliente' => $cliente, 'token' => $token, 'sucursal' => $sucursal, 'staff' => $staff] = portalClienteConCuenta();
    $otro = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $staff->id,
    ]);
    $plan = Membresia::factory()->create([
        'nombre' => 'Plan Pagos',
        'estado' => 'activa',
        'sucursal_id' => $sucursal->id,
    ]);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $plan->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'sucursal_id' => $sucursal->id,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 100,
        'moneda' => 'PEN',
        'metodo_pago' => 'yape',
        'fecha_pago' => now(),
        'es_pago_parcial' => false,
        'saldo_pendiente' => 0,
        'registrado_por' => $staff->id,
        'sucursal_id' => $sucursal->id,
    ]);
    Pago::create([
        'cliente_id' => $otro->id,
        'monto' => 999,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => false,
        'saldo_pendiente' => 0,
        'registrado_por' => $staff->id,
        'sucursal_id' => $sucursal->id,
    ]);

    $this->getJson('/api/v1/pagos', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.monto', 100)
        ->assertJsonPath('data.0.concepto', 'Plan Pagos')
        ->assertJsonPath('data.0.moneda', 'PEN');
});
