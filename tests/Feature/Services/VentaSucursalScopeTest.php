<?php

use App\Models\Core\Caja;
use App\Models\Core\CategoriaProducto;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Producto;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use App\Services\VentaService;

function crearSucursalVenta(string $codigo, bool $principal = false): Sucursal
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

it('rejects a POS sale when the payment method belongs to another sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalVenta('VPAY-A', true);
    $sucursalB = crearSucursalVenta('VPAY-B');
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    $categoria = CategoriaProducto::factory()->create([
        'sucursal_id' => $sucursalA->id,
    ]);
    $producto = Producto::factory()->create([
        'categoria_id' => $categoria->id,
        'stock_actual' => 10,
        'estado' => 'activo',
        'sucursal_id' => $sucursalA->id,
    ]);
    PaymentMethod::factory()->create([
        'id' => 1001,
        'nombre' => 'Transferencia A',
        'sucursal_id' => $sucursalB->id,
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
        'sucursal_id' => $sucursalA->id,
    ]);

    app(VentaService::class)->procesarVenta([
        'payment_method_id' => 1001,
        'items' => [
            [
                'tipo' => 'producto',
                'id' => $producto->id,
                'cantidad' => 1,
            ],
        ],
    ]);
})->throws(\Exception::class, 'metodo de pago');

it('rejects a POS sale when a product belongs to another sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalVenta('VPRO-A', true);
    $sucursalB = crearSucursalVenta('VPRO-B');
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    $categoriaA = CategoriaProducto::factory()->create([
        'sucursal_id' => $sucursalA->id,
    ]);
    $categoriaB = CategoriaProducto::factory()->create([
        'sucursal_id' => $sucursalB->id,
    ]);
    $productoOtraSucursal = Producto::factory()->create([
        'categoria_id' => $categoriaB->id,
        'stock_actual' => 10,
        'estado' => 'activo',
        'sucursal_id' => $sucursalB->id,
    ]);
    $paymentMethod = PaymentMethod::factory()->create([
        'nombre' => 'Efectivo A',
        'sucursal_id' => $sucursalA->id,
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
        'sucursal_id' => $sucursalA->id,
    ]);

    app(VentaService::class)->procesarVenta([
        'payment_method_id' => $paymentMethod->id,
        'items' => [
            [
                'tipo' => 'producto',
                'id' => $productoOtraSucursal->id,
                'cantidad' => 1,
            ],
        ],
    ]);
})->throws(\Exception::class, 'producto');
