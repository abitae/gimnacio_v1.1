<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClientDebt;
use App\Models\Core\DiscountCoupon;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\Exercise;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\ClienteCrossSucursalAlertService;
use App\Services\SucursalContext;

function crearSucursalAislamiento(string $codigo, bool $principal = false): Sucursal
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

it('returns zero rows when authenticated without active sucursal context', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    crearSucursalAislamiento('FC-A', true);

    expect(Cliente::query()->count())->toBe(0);
});

it('isolates clientes by active sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalAislamiento('CLI-A', true);
    $sucursalB = crearSucursalAislamiento('CLI-B');

    Cliente::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalA->id,
        'tipo_documento' => 'DNI',
        'numero_documento' => '11111111',
        'nombres' => 'Ana',
        'apellidos' => 'Sede A',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    Cliente::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'tipo_documento' => 'DNI',
        'numero_documento' => '22222222',
        'nombres' => 'Bruno',
        'apellidos' => 'Sede B',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    expect(Cliente::query()->count())->toBe(1)
        ->and(Cliente::query()->value('numero_documento'))->toBe('11111111');
});

it('detects cross-sucursal document matches with debt read-only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalAislamiento('XSA-A', true);
    $sucursalB = crearSucursalAislamiento('XSA-B');

    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    $clienteA = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '87654321',
        'nombres' => 'Carlos',
        'apellidos' => 'Local',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $clienteB = Cliente::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'tipo_documento' => 'DNI',
        'numero_documento' => '87654321',
        'nombres' => 'Carlos',
        'apellidos' => 'Remoto',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    ClientDebt::withoutGlobalScope('active_sucursal')->create([
        'cliente_id' => $clienteB->id,
        'sucursal_id' => $sucursalB->id,
        'referencia' => 'Deuda remota',
        'origen_tipo' => 'manual',
        'origen_id' => 0,
        'monto_total' => 120,
        'monto_pagado' => 0,
        'saldo_pendiente' => 120,
        'fecha_registro' => now()->toDateString(),
        'estado' => 'pendiente',
        'fecha_vencimiento' => now()->addDays(7)->toDateString(),
    ]);

    $matches = app(ClienteCrossSucursalAlertService::class)->findMatchesForCliente($clienteA);

    expect($matches)->toHaveCount(1)
        ->and($matches->first()['sucursal_nombre'])->toBe('Sucursal XSA-B')
        ->and($matches->first()['tiene_deuda'])->toBeTrue()
        ->and($matches->first()['saldo_deuda'])->toBe(120.0);

    expect(Cliente::query()->whereKey($clienteB->id)->exists())->toBeFalse();
});

it('isolates discount coupons by sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalAislamiento('CPN-A', true);
    $sucursalB = crearSucursalAislamiento('CPN-B');

    DiscountCoupon::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'codigo' => 'PROMO2026',
        'nombre' => 'Cupón B',
        'tipo_descuento' => 'monto_fijo',
        'valor_descuento' => 10,
        'fecha_inicio' => now()->toDateString(),
        'fecha_vencimiento' => now()->addMonth()->toDateString(),
        'estado' => 'activo',
    ]);

    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    expect(DiscountCoupon::query()->where('codigo', 'PROMO2026')->exists())->toBeFalse();
});

it('isolates CRM leads by sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalAislamiento('CRM-A', true);
    $sucursalB = crearSucursalAislamiento('CRM-B');

    $stage = CrmStage::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'nombre' => 'Nuevo B',
        'orden' => 1,
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);

    Lead::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'nombres' => 'Lead',
        'apellidos' => 'Remoto',
        'telefono' => '999888777',
        'estado' => 'nuevo',
        'canal_origen' => 'web',
        'stage_id' => $stage->id,
    ]);

    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    expect(Lead::query()->count())->toBe(0);
});

it('isolates exercises catalog by sucursal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalAislamiento('EX-A', true);
    $sucursalB = crearSucursalAislamiento('EX-B');

    Exercise::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'nombre' => 'Sentadilla remota',
        'tipo' => 'fuerza',
        'estado' => 'activo',
    ]);

    app(SucursalContext::class)->setDelegateContext($sucursalA->id, $sucursalA->empresa_id);

    expect(Exercise::query()->where('nombre', 'Sentadilla remota')->exists())->toBeFalse();
});

it('allows the same coupon code in different sucursales', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $sucursalA = crearSucursalAislamiento('CP2-A', true);
    $sucursalB = crearSucursalAislamiento('CP2-B');

    DiscountCoupon::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalA->id,
        'codigo' => 'MULTI',
        'nombre' => 'Cupón A',
        'tipo_descuento' => 'monto_fijo',
        'valor_descuento' => 5,
        'fecha_inicio' => now()->toDateString(),
        'fecha_vencimiento' => now()->addMonth()->toDateString(),
        'estado' => 'activo',
    ]);

    DiscountCoupon::withoutGlobalScope('active_sucursal')->create([
        'sucursal_id' => $sucursalB->id,
        'codigo' => 'MULTI',
        'nombre' => 'Cupón B',
        'tipo_descuento' => 'monto_fijo',
        'valor_descuento' => 5,
        'fecha_inicio' => now()->toDateString(),
        'fecha_vencimiento' => now()->addMonth()->toDateString(),
        'estado' => 'activo',
    ]);

    expect(DiscountCoupon::withoutGlobalScope('active_sucursal')->where('codigo', 'MULTI')->count())->toBe(2);
});
