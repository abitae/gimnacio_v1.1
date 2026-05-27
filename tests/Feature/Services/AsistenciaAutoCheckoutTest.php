<?php

use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use App\Services\AsistenciaService;

it('closes open attendances through the end of the processed day', function () {
    $clienteHoy = Cliente::factory()->create();
    $clienteAnterior = Cliente::factory()->create();
    $fecha = now()->setDate(2026, 5, 27)->setTime(12, 0);

    $asistenciaHoy = Asistencia::create([
        'cliente_id' => $clienteHoy->id,
        'fecha_hora_ingreso' => $fecha->copy()->setTime(8, 30),
        'fecha_hora_salida' => null,
        'origen' => 'manual',
        'valido_por_membresia' => true,
    ]);
    $asistenciaAnterior = Asistencia::create([
        'cliente_id' => $clienteAnterior->id,
        'fecha_hora_ingreso' => $fecha->copy()->subDay()->setTime(18, 0),
        'fecha_hora_salida' => null,
        'origen' => 'manual',
        'valido_por_membresia' => true,
    ]);

    $result = app(AsistenciaService::class)->cerrarIngresosAbiertosHastaFinDelDia($fecha);

    expect($result['total'])->toBe(2)
        ->and($result['ids'])->toContain($asistenciaHoy->id, $asistenciaAnterior->id);

    expect($asistenciaHoy->fresh()->fecha_hora_salida->format('Y-m-d H:i:s'))->toBe('2026-05-27 23:59:59')
        ->and($asistenciaHoy->fresh()->checkout_origen)->toBe('automatico')
        ->and($asistenciaAnterior->fresh()->fecha_hora_salida->format('Y-m-d H:i:s'))->toBe('2026-05-27 23:59:59')
        ->and($asistenciaAnterior->fresh()->checkout_origen)->toBe('automatico');
});

it('does not modify closed or future attendances and is idempotent', function () {
    $clienteCerrado = Cliente::factory()->create();
    $clienteFuturo = Cliente::factory()->create();
    $fecha = now()->setDate(2026, 5, 27)->setTime(12, 0);

    $cerrada = Asistencia::create([
        'cliente_id' => $clienteCerrado->id,
        'fecha_hora_ingreso' => $fecha->copy()->setTime(9, 0),
        'fecha_hora_salida' => $fecha->copy()->setTime(10, 0),
        'checkout_origen' => 'manual',
        'origen' => 'manual',
        'valido_por_membresia' => true,
    ]);
    $futura = Asistencia::create([
        'cliente_id' => $clienteFuturo->id,
        'fecha_hora_ingreso' => $fecha->copy()->addDay()->setTime(9, 0),
        'fecha_hora_salida' => null,
        'origen' => 'manual',
        'valido_por_membresia' => true,
    ]);

    $firstRun = app(AsistenciaService::class)->cerrarIngresosAbiertosHastaFinDelDia($fecha);
    $secondRun = app(AsistenciaService::class)->cerrarIngresosAbiertosHastaFinDelDia($fecha);

    expect($firstRun['total'])->toBe(0)
        ->and($secondRun['total'])->toBe(0)
        ->and($cerrada->fresh()->fecha_hora_salida->format('Y-m-d H:i:s'))->toBe($fecha->copy()->setTime(10, 0)->format('Y-m-d H:i:s'))
        ->and($cerrada->fresh()->checkout_origen)->toBe('manual')
        ->and($futura->fresh()->fecha_hora_salida)->toBeNull()
        ->and($futura->fresh()->checkout_origen)->toBeNull();
});

it('marks manual checkout origin when registering an individual checkout', function () {
    $cliente = Cliente::factory()->create();
    $asistencia = Asistencia::create([
        'cliente_id' => $cliente->id,
        'fecha_hora_ingreso' => now()->subHour(),
        'fecha_hora_salida' => null,
        'origen' => 'manual',
        'valido_por_membresia' => true,
    ]);

    $cerrada = app(AsistenciaService::class)->registrarSalida($asistencia->id);

    expect($cerrada->fecha_hora_salida)->not->toBeNull()
        ->and($cerrada->checkout_origen)->toBe('manual');
});
