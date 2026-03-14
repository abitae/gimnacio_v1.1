<?php

use App\Models\Core\Asistencia;
use App\Models\Core\Cita;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClientePlanTraspaso;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ReporteModuloService;

it('builds the detailed client report summary with attendance and transfer metrics', function () {
    $user = User::factory()->create();
    $trainer = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000005',
        'nombres' => 'Lucía',
        'apellidos' => 'Paredes',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
        'trainer_user_id' => $trainer->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual Plus',
        'duracion_dias' => 30,
        'precio_base' => 120,
        'estado' => 'activa',
    ]);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->subDays(10)->toDateString(),
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(5)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 120,
        'descuento_monto' => 0,
        'precio_final' => 120,
    ]);

    Asistencia::create([
        'cliente_id' => $cliente->id,
        'fecha_hora_ingreso' => now()->subDay(),
        'fecha_hora_salida' => now()->subDay()->addHour(),
        'origen' => 'manual',
        'valido_por_membresia' => true,
        'registrada_por' => $user->id,
    ]);

    Cita::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'seguimiento',
        'fecha_hora' => now()->subDays(2),
        'duracion_minutos' => 30,
        'estado' => 'no_asistio',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);

    ClientePlanTraspaso::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => ClienteMatricula::class,
        'origen_id' => 1,
        'plan_anterior_tipo' => 'membresia',
        'plan_anterior_id' => 10,
        'plan_nuevo_tipo' => 'membresia',
        'plan_nuevo_id' => 11,
        'registrado_por' => $user->id,
    ]);

    $data = app(ReporteModuloService::class)->datosReporteClientes(
        null,
        now()->subMonth()->toDateString(),
        now()->toDateString(),
        null,
        null,
        null,
        15
    );

    expect($data['resumen']['total'])->toBe(1);
    expect($data['resumen']['activos'])->toBe(1);
    expect($data['resumen']['clientes_por_vencer'])->toBe(1);
    expect($data['resumen']['traspasos'])->toBe(1);
    expect($data['resumen']['asistencias'])->toBe(1);
    expect($data['resumen']['inasistencias'])->toBe(1);

    $row = $data['clientes']->first();

    expect($row->plan_actual)->toBe('Mensual Plus');
    expect($row->por_vencer)->toBeTrue();
    expect($row->asistencias_count)->toBe(1);
    expect($row->inasistencias_count)->toBe(1);
    expect($row->traspasos_count)->toBe(1);
});
