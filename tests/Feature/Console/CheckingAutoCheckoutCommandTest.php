<?php

use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use Illuminate\Support\Facades\Schedule;

it('runs the automatic checkout command for an explicit date', function () {
    $cliente = Cliente::factory()->create();
    $asistencia = Asistencia::create([
        'cliente_id' => $cliente->id,
        'fecha_hora_ingreso' => now()->setDate(2026, 5, 27)->setTime(7, 45),
        'fecha_hora_salida' => null,
        'origen' => 'manual',
        'valido_por_membresia' => true,
    ]);

    $this->artisan('checking:auto-checkout --date=2026-05-27')
        ->expectsOutput('Asistencias cerradas automaticamente: 1.')
        ->expectsOutput('Fecha/hora de salida aplicada: 2026-05-27 23:59:59.')
        ->assertExitCode(0);

    expect($asistencia->fresh()->fecha_hora_salida->format('Y-m-d H:i:s'))->toBe('2026-05-27 23:59:59')
        ->and($asistencia->fresh()->checkout_origen)->toBe('automatico');
});

it('rejects invalid automatic checkout date format', function () {
    $this->artisan('checking:auto-checkout --date=27-05-2026')
        ->expectsOutput('La opcion --date debe tener formato YYYY-MM-DD.')
        ->assertExitCode(1);
});

it('registers the automatic checkout in the scheduler', function () {
    $event = collect(Schedule::events())
        ->first(fn ($event) => str_contains($event->command ?? '', 'checking:auto-checkout'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('59 23 * * *')
        ->and($event->withoutOverlapping)->toBeTrue();
});
