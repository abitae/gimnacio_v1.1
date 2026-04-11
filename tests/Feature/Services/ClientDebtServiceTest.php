<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use App\Models\User;
use App\Services\ClientDebtService;

it('registers a payment for a client debt and reports it to caja', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $paymentMethod = PaymentMethod::factory()->create([
        'nombre' => 'Efectivo',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 50,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $debt = ClientDebt::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => 'Pos',
        'origen_id' => 10,
        'monto_total' => 120,
        'monto_pagado' => 20,
        'saldo_pendiente' => 100,
        'fecha_registro' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(5)->toDateString(),
        'estado' => 'parcial',
    ]);

    $pago = app(ClientDebtService::class)->procesarPago($debt->id, [
        'monto_pago' => 35,
        'payment_method_id' => $paymentMethod->id,
        'fecha_pago' => now(),
    ]);

    expect($pago)->toBeInstanceOf(Pago::class);
    expect($pago->client_debt_id)->toBe($debt->id);
    expect($pago->caja_id)->toBe($caja->id);
    expect((float) $pago->saldo_pendiente)->toBe(65.0);

    $debt->refresh();
    expect((float) $debt->monto_pagado)->toBe(55.0);
    expect((float) $debt->saldo_pendiente)->toBe(65.0);
    expect($debt->estado)->toBe('parcial');

    $movimiento = CajaMovimiento::query()
        ->where('referencia_tipo', Pago::class)
        ->where('referencia_id', $pago->id)
        ->first();

    expect($movimiento)->not->toBeNull();
    expect($movimiento->categoria)->toBe(CajaMovimiento::CATEGORIA_POS);
    expect((float) $movimiento->monto)->toBe(35.0);
});
