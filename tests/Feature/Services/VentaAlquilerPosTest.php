<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\Employee;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Rental;
use App\Models\Core\RentalPayment;
use App\Models\Core\VentaItem;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\PosAlquilerReservaService;
use App\Services\SucursalContext;
use App\Services\VentaService;
use Database\Factories\RentableSpaceFactory;

function crearSucursalAlquilerPos(string $codigo, bool $principal = false): Sucursal
{
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal '.$codigo,
        'estado' => 'activa',
        'es_principal' => $principal,
    ]);
}

afterEach(function () {
    app(SucursalContext::class)->clearDelegateContext();
});

it('processes a POS sale with alquiler item at reference price', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursal = crearSucursalAlquilerPos('VALQ-A', true);
    app(SucursalContext::class)->setDelegateContext($sucursal->id, $sucursal->empresa_id);

    $espacio = RentableSpaceFactory::new()->create([
        'nombre' => 'Cancha POS',
        'precio' => 35.00,
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);

    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id]);

    $paymentMethod = PaymentMethod::factory()->create([
        'nombre' => 'Efectivo Alquiler',
        'sucursal_id' => $sucursal->id,
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
        'sucursal_id' => $sucursal->id,
    ]);

    $venta = app(VentaService::class)->procesarVenta([
        'tipo_comprador' => 'cliente',
        'cliente_id' => $cliente->id,
        'payment_method_id' => $paymentMethod->id,
        'items' => [
            [
                'tipo' => 'alquiler',
                'id' => $espacio->id,
                'cantidad' => 1,
            ],
        ],
    ]);

    $item = VentaItem::query()->where('venta_id', $venta->id)->first();

    expect($item)->not->toBeNull()
        ->and($item->tipo_item)->toBe('alquiler')
        ->and((float) $item->precio_unitario)->toBe(35.0)
        ->and($item->nombre_item)->toContain('Cancha POS');
});

it('creates paid rentals from POS cart after sale', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursal = crearSucursalAlquilerPos('VALQ-B', true);
    app(SucursalContext::class)->setDelegateContext($sucursal->id, $sucursal->empresa_id);

    $espacio = RentableSpaceFactory::new()->create([
        'nombre' => 'Salón POS',
        'precio' => 45.00,
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);

    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id]);

    $paymentMethod = PaymentMethod::factory()->create([
        'nombre' => 'Efectivo Alquiler B',
        'sucursal_id' => $sucursal->id,
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
        'sucursal_id' => $sucursal->id,
    ]);

    $carrito = [
        'alquiler-'.$espacio->id => [
            'tipo' => 'alquiler',
            'id' => $espacio->id,
            'precio' => 45.0,
            'cantidad' => 1,
        ],
    ];

    $venta = app(VentaService::class)->procesarVenta([
        'tipo_comprador' => 'cliente',
        'cliente_id' => $cliente->id,
        'payment_method_id' => $paymentMethod->id,
        'items' => [
            ['tipo' => 'alquiler', 'id' => $espacio->id, 'cantidad' => 1],
        ],
    ]);

    app(PosAlquilerReservaService::class)->crearDesdeVenta($venta, $carrito, [
        'fecha' => now()->format('Y-m-d'),
        'hora_inicio' => '10:00',
        'hora_fin' => '11:00',
    ]);

    $rental = Rental::query()->where('rentable_space_id', $espacio->id)->first();

    expect($rental)->not->toBeNull()
        ->and($rental->cliente_id)->toBe($cliente->id)
        ->and($rental->estado)->toBe('pagado')
        ->and((float) $rental->precio)->toBe(45.0);

    $payment = RentalPayment::query()->where('rental_id', $rental->id)->first();
    expect($payment)->not->toBeNull()
        ->and((float) $payment->monto)->toBe(45.0);

    $movimientoAlquiler = CajaMovimiento::query()
        ->where('caja_id', $venta->caja_id)
        ->where('categoria', CajaMovimiento::CATEGORIA_ALQUILER)
        ->where('referencia_tipo', RentalPayment::class)
        ->where('referencia_id', $payment->id)
        ->first();

    expect($movimientoAlquiler)->not->toBeNull()
        ->and((float) $movimientoAlquiler->monto)->toBe(45.0);

    $movimientoPos = CajaMovimiento::query()
        ->where('caja_id', $venta->caja_id)
        ->where('categoria', CajaMovimiento::CATEGORIA_POS)
        ->where('referencia_id', $venta->id)
        ->first();

    expect($movimientoPos)->toBeNull();
});

it('creates rental with external name when sale is for employee', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursal = crearSucursalAlquilerPos('VALQ-C', true);
    app(SucursalContext::class)->setDelegateContext($sucursal->id, $sucursal->empresa_id);

    $espacio = RentableSpaceFactory::new()->create([
        'nombre' => 'Cancha Empleado',
        'precio' => 50.00,
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);

    $empleado = Employee::factory()->create([
        'nombres' => 'Ana',
        'apellidos' => 'Pérez',
        'documento' => '99887766',
        'sucursal_id' => $sucursal->id,
    ]);

    $paymentMethod = PaymentMethod::factory()->create([
        'sucursal_id' => $sucursal->id,
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
        'sucursal_id' => $sucursal->id,
    ]);

    $venta = app(VentaService::class)->procesarVenta([
        'tipo_comprador' => 'empleado',
        'employee_id' => $empleado->id,
        'payment_method_id' => $paymentMethod->id,
        'items' => [
            ['tipo' => 'alquiler', 'id' => $espacio->id, 'cantidad' => 1],
        ],
    ]);

    app(PosAlquilerReservaService::class)->crearDesdeVenta($venta, [
        'alquiler-'.$espacio->id => [
            'tipo' => 'alquiler',
            'id' => $espacio->id,
            'precio' => 50.0,
            'cantidad' => 1,
        ],
    ], [
        'fecha' => now()->format('Y-m-d'),
        'hora_inicio' => '14:00',
        'hora_fin' => '15:00',
    ]);

    $rental = Rental::query()->where('rentable_space_id', $espacio->id)->first();

    expect($rental)->not->toBeNull()
        ->and($rental->cliente_id)->toBeNull()
        ->and($rental->nombre_externo)->toContain('Ana')
        ->and($rental->nombre_externo)->toContain('Empleado')
        ->and($rental->documento_externo)->toBe('99887766');
});
