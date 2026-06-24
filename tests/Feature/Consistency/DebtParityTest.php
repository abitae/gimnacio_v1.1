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
use App\Services\Analytics\FinanceAnalyticsService;
use App\Services\DailyOperationsDebtService;

/**
 * Paridad de deuda entre capas operativa y analítica (debt-definitions.md).
 */
function operationalClientDebtTotal(int $clienteId): float
{
    return round((float) ClientDebt::query()
        ->where('cliente_id', $clienteId)
        ->pendientes()
        ->sum('saldo_pendiente'), 2);
}

function operationalTotalFromItems(array $summary): float
{
    return round((float) collect($summary['items'] ?? [])->sum('saldo_pendiente'), 2);
}

it('parity: overdue installment plus credit sale matches analytics client debt slice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create(['estado' => 'activa', 'precio_base' => 150]);

    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(25)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 150,
        'descuento_monto' => 0,
        'precio_final' => 150,
        'modalidad_pago' => 'cuotas',
        'requiere_plan_cuotas' => true,
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

    $operations = app(DailyOperationsDebtService::class)->summarizeCliente($cliente->id);
    $analytics = app(FinanceAnalyticsService::class)->accountsReceivableSummary([
        'search' => $cliente->numero_documento,
    ]);

    expect(operationalTotalFromItems($operations))->toBe($operations['total_pendiente']);
    expect($operations['total_pendiente'])->toBe(140.0);
    expect(operationalClientDebtTotal($cliente->id))->toBe(50.0);
    expect($analytics['total_saldo'])->toBe(50.0);
});

it('parity: legacy membership only is included in operational summary', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create(['estado' => 'activa', 'precio_base' => 100]);

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
        'monto' => 25,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 75,
        'registrado_por' => $user->id,
    ]);

    $operations = app(DailyOperationsDebtService::class)->summarizeCliente($cliente->id);

    expect($operations['total_pendiente'])->toBe(75.0);
    expect($operations['cantidad_items'])->toBe(1);
    expect(operationalTotalFromItems($operations))->toBe($operations['total_pendiente']);
    expect(operationalClientDebtTotal($cliente->id))->toBe(0.0);
});

it('parity: imported-style mixed debt totals are internally consistent', function () {
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
        'monto' => 50,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => now(),
        'es_pago_parcial' => true,
        'saldo_pendiente' => 150,
        'registrado_por' => $user->id,
    ]);

    ClientDebt::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => 'MEMBRESIA',
        'origen_id' => 99,
        'referencia' => 'Import legacy',
        'monto_total' => 40,
        'monto_pagado' => 0,
        'saldo_pendiente' => 40,
        'fecha_registro' => now()->toDateString(),
        'estado' => 'pendiente',
    ]);

    $operations = app(DailyOperationsDebtService::class)->summarizeCliente($cliente->id);
    $analytics = app(FinanceAnalyticsService::class)->accountsReceivableSummary([
        'search' => $cliente->numero_documento,
    ]);

    expect($operations['total_pendiente'])->toBe(190.0);
    expect(operationalTotalFromItems($operations))->toBe($operations['total_pendiente']);
    expect(operationalClientDebtTotal($cliente->id))->toBe(40.0);
    expect($analytics['total_saldo'])->toBe(40.0);
});
