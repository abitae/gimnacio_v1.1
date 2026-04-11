<?php

use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use App\Services\DailyOperationsDebtService;

it('builds a unified operational debt summary for the cliente', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create(['estado' => 'activa', 'precio_base' => 200]);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->subDays(10)->toDateString(),
        'fecha_fin' => now()->addDays(20)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 200,
        'descuento_monto' => 0,
        'precio_final' => 200,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 80,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 120,
        'registrado_por' => $user->id,
    ]);

    $legacy = ClienteMembresia::create([
        'cliente_id' => $cliente->id,
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->subDays(20)->toDateString(),
        'fecha_fin' => now()->addDays(10)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'asesor_id' => $user->id,
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_membresia_id' => $legacy->id,
        'monto' => 20,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 80,
        'registrado_por' => $user->id,
    ]);

    $plan = EnrollmentInstallmentPlan::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto_total' => 90,
        'numero_cuotas' => 1,
        'monto_cuota' => 90,
        'frecuencia' => 'mensual',
        'fecha_inicio' => now()->toDateString(),
    ]);

    EnrollmentInstallment::create([
        'enrollment_installment_plan_id' => $plan->id,
        'cliente_matricula_id' => $matricula->id,
        'numero_cuota' => 1,
        'monto' => 90,
        'fecha_vencimiento' => now()->subDay()->toDateString(),
        'estado' => 'vencida',
    ]);

    ClientDebt::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => 'Pos',
        'origen_id' => 1,
        'monto_total' => 60,
        'monto_pagado' => 10,
        'saldo_pendiente' => 50,
        'fecha_registro' => now()->toDateString(),
        'fecha_vencimiento' => now()->subDays(2)->toDateString(),
        'estado' => 'vencido',
    ]);

    $summary = app(DailyOperationsDebtService::class)->summarizeCliente($cliente->id);

    expect($summary['cantidad_items'])->toBe(4);
    expect($summary['total_pendiente'])->toBe(340.0);
    expect($summary['tiene_deuda'])->toBeTrue();
    expect($summary['tiene_deuda_vencida'])->toBeTrue();
    expect(collect($summary['items'])->pluck('tipo')->all())
        ->toContain('matricula', 'membresia', 'cuota', 'client_debt');
});
