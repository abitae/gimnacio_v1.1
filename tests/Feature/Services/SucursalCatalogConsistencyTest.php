<?php

use App\Models\Core\CategoriaProducto;
use App\Models\Core\CategoriaServicio;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Services\ProductoService;
use App\Services\ServicioExternoService;
use App\Services\SucursalContext;
use App\Models\User;

function crearSucursalCatalogo(string $codigo, bool $principal = false): Sucursal
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

it('rejects a product category from another sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalCatalogo('SCAT-A', true);
    $sucursalB = crearSucursalCatalogo('SCAT-B');
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    $categoriaOtraSucursal = CategoriaProducto::factory()->create([
        'nombre' => 'Categoria remota',
        'sucursal_id' => $sucursalB->id,
    ]);

    app(ProductoService::class)->create([
        'codigo' => 'P-001',
        'nombre' => 'Producto cruzado',
        'categoria_id' => $categoriaOtraSucursal->id,
        'precio_venta' => 15,
        'estado' => 'activo',
    ]);
})->throws(\Illuminate\Validation\ValidationException::class);

it('rejects a service category from another sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalCatalogo('SSRV-A', true);
    $sucursalB = crearSucursalCatalogo('SSRV-B');
    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    $categoriaOtraSucursal = CategoriaServicio::factory()->create([
        'nombre' => 'Categoria servicio remota',
        'sucursal_id' => $sucursalB->id,
    ]);

    app(ServicioExternoService::class)->create([
        'codigo' => 'S-001',
        'nombre' => 'Servicio cruzado',
        'categoria_id' => $categoriaOtraSucursal->id,
        'precio' => 20,
        'duracion_minutos' => 60,
        'estado' => 'activo',
    ]);
})->throws(\Illuminate\Validation\ValidationException::class);

it('allows reusing the same product code in different sucursales', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalCatalogo('SCOD-A', true);
    $sucursalB = crearSucursalCatalogo('SCOD-B');
    $categoriaA = CategoriaProducto::factory()->create([
        'nombre' => 'Categoria A',
        'sucursal_id' => $sucursalA->id,
    ]);
    $categoriaB = CategoriaProducto::factory()->create([
        'nombre' => 'Categoria B',
        'sucursal_id' => $sucursalB->id,
    ]);

    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);
    $productoA = app(ProductoService::class)->create([
        'codigo' => 'REPETIDO-001',
        'nombre' => 'Producto A',
        'categoria_id' => $categoriaA->id,
        'precio_venta' => 25,
        'estado' => 'activo',
    ]);

    app(SucursalContext::class)->setDelegateContext($sucursalB->id, $sucursalB->empresa_id);
    $productoB = app(ProductoService::class)->create([
        'codigo' => 'REPETIDO-001',
        'nombre' => 'Producto B',
        'categoria_id' => $categoriaB->id,
        'precio_venta' => 30,
        'estado' => 'activo',
    ]);

    expect($productoA->codigo)->toBe('REPETIDO-001')
        ->and($productoB->codigo)->toBe('REPETIDO-001')
        ->and($productoA->sucursal_id)->toBe($sucursalA->id)
        ->and($productoB->sucursal_id)->toBe($sucursalB->id);
});

it('auto generates sequential product codes when codigo is omitted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursal = crearSucursalCatalogo('SPROD-AUTO', true);
    app(SucursalContext::class)->setDelegateContext($sucursal->id, $sucursal->empresa_id);

    $categoria = CategoriaProducto::factory()->create([
        'nombre' => 'Categoria auto',
        'sucursal_id' => $sucursal->id,
    ]);

    \App\Models\Core\Producto::withoutGlobalScopes()->create([
        'codigo' => 'PROD-0001',
        'nombre' => 'Producto base',
        'categoria_id' => $categoria->id,
        'precio_venta' => 10,
        'precio_compra' => 5,
        'stock_actual' => 4,
        'stock_minimo' => 1,
        'unidad_medida' => 'unidad',
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);

    $producto2 = app(ProductoService::class)->create([
        'nombre' => 'Producto 2',
        'categoria_id' => $categoria->id,
        'precio_venta' => 15,
        'estado' => 'activo',
    ]);

    $producto3 = app(ProductoService::class)->create([
        'nombre' => 'Producto 3',
        'categoria_id' => $categoria->id,
        'precio_venta' => 18,
        'estado' => 'activo',
    ]);

    expect($producto2->codigo)->toBe('PROD-0002')
        ->and($producto3->codigo)->toBe('PROD-0003');
});

it('keeps the existing product code when updating without codigo', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursal = crearSucursalCatalogo('SPROD-UPD', true);
    app(SucursalContext::class)->setDelegateContext($sucursal->id, $sucursal->empresa_id);

    $categoria = CategoriaProducto::factory()->create([
        'nombre' => 'Categoria update',
        'sucursal_id' => $sucursal->id,
    ]);

    $producto = \App\Models\Core\Producto::withoutGlobalScopes()->create([
        'codigo' => 'PROD-0042',
        'nombre' => 'Producto legacy',
        'categoria_id' => $categoria->id,
        'precio_venta' => 22,
        'precio_compra' => 11,
        'stock_actual' => 7,
        'stock_minimo' => 2,
        'unidad_medida' => 'unidad',
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);

    $actualizado = app(ProductoService::class)->update($producto->id, [
        'nombre' => 'Producto actualizado',
    ]);

    expect($actualizado->codigo)->toBe('PROD-0042')
        ->and($actualizado->nombre)->toBe('Producto actualizado');
});
