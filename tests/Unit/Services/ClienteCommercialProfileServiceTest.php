<?php

use App\Data\Cliente\ClienteCommercialSummary;
use App\Data\Cliente\ClienteCrmSummary;
use App\Data\Cliente\ClienteFidelitySummary;
use App\Data\Cliente\ClienteOperationsSummary;
use App\Data\Cliente\ClienteWellnessSummary;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Services\Cliente\ClienteCommercialProfileService;
use App\Services\Cliente\ClienteProfileContextService;

uses(Tests\TestCase::class);

it('builds financial matricula row with saldo and cobro flag', function () {
    $matricula = new ClienteMatricula;
    $matricula->forceFill([
        'id' => 10,
        'estado' => 'activa',
        'nombre' => 'Plan Gold',
        'tipo' => 'membresia',
        'precio_final' => 500,
        'monto_pagado' => 200,
        'monto_financiado' => 500,
    ]);
    $matricula->setRelation('pagos', collect([]));

    $service = new ClienteCommercialProfileService(
        \Mockery::mock(\App\Services\EnrollmentInstallmentService::class),
        \Mockery::mock(\App\Services\ClientEnrollmentService::class),
        \Mockery::mock(\App\Services\Legacy\LegacyMembresiaReadService::class),
    );

    $row = $service->buildFinancialMatriculaRow($matricula);

    expect($row['tipo_label'])->toBe('Membresía');
    expect($row['is_legacy'])->toBeFalse();
    expect($row['modalidad_pago'])->toBe('Contado');
});

it('profile context service returns same cached instance per request', function () {
    $operations = \Mockery::mock(\App\Services\Cliente\ClienteOperationsProfileService::class);
    $commercial = \Mockery::mock(\App\Services\Cliente\ClienteCommercialProfileService::class);
    $wellness = \Mockery::mock(\App\Services\Cliente\ClienteWellnessProfileService::class);
    $crm = \Mockery::mock(\App\Services\Cliente\ClienteCrmProfileService::class);
    $fidelity = \Mockery::mock(\App\Services\Cliente\ClienteFidelityProfileService::class);
    $clienteService = \Mockery::mock(\App\Services\ClienteService::class);

    $cliente = \Mockery::mock(Cliente::class)->makePartial();
    $cliente->id = 1;
    $cliente->nombres = 'Test';
    $cliente->shouldReceive('loadMissing')->andReturnSelf();
    $clienteService->shouldReceive('find')->with(1)->andReturn($cliente);

    $operations->shouldReceive('getSummary')->once()->with(1)->andReturn(
        new ClienteOperationsSummary(
            membresiaActiva: null,
            operacionDiariaDebtSummary: [],
            saldoPendiente: 0.0,
            deudaProductoPendiente: 0.0,
            deudaMembresiaPendiente: 0.0,
            asistenciasRecientes: [],
            estadisticasAsistencia: [],
            validacionAcceso: [],
            ingresoEnCurso: null,
            pagosRecientes: [],
        )
    );

    $commercial->shouldReceive('getSummary')->once()->with(1, false)->andReturn(
        new ClienteCommercialSummary(
            historialMembresias: [],
            historialClases: [],
            usesLegacyMembresiasHistory: false,
            matriculaOpcionesCobro: collect([]),
            pendienteCuotaPorMatricula: [],
            cuotasCliente: collect([]),
            matriculasFinancieras: collect([]),
            matriculasConCuotas: collect([]),
            deudaPlanesPendiente: 0.0,
            matriculasSinCronogramaCuotas: collect([]),
        )
    );

    $wellness->shouldReceive('getSummary')->once()->with(1)->andReturn(
        new ClienteWellnessSummary([], 0, 0, 0)
    );
    $crm->shouldReceive('getSummary')->once()->with(1)->andReturn(
        new ClienteCrmSummary(0, 0, null, null)
    );
    $fidelity->shouldReceive('getSummary')->once()->with(1)->andReturn(
        new ClienteFidelitySummary([])
    );

    $contextService = new ClienteProfileContextService(
        $clienteService,
        $operations,
        $commercial,
        $wellness,
        $crm,
        $fidelity,
    );

    $first = $contextService->build(1, ['operations', 'wellness', 'crm', 'fidelity']);
    $second = $contextService->build(1, ['operations', 'wellness', 'crm', 'fidelity']);

    expect($first)->toBe($second);
});
