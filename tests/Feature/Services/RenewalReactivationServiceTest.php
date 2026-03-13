<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Membresia;
use App\Models\Crm\CrmTask;
use App\Models\User;
use App\Services\Crm\RenewalReactivationService;

it('creates crm renewal tasks with a configured automation user', function () {
    $user = User::factory()->create();
    config()->set('crm.automation_user_id', $user->id);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000003',
        'nombres' => 'Carla',
        'apellidos' => 'Diaz',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual',
        'duracion_dias' => 30,
        'precio_base' => 90,
        'estado' => 'activa',
    ]);

    ClienteMembresia::create([
        'cliente_id' => $cliente->id,
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->subDays(20)->toDateString(),
        'fecha_inicio' => now()->subDays(20)->toDateString(),
        'fecha_fin' => now()->addDays(3)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 90,
        'descuento_monto' => 0,
        'precio_final' => 90,
    ]);

    $count = app(RenewalReactivationService::class)->generateRenewalTasks(7);

    expect($count)->toBe(1);

    $task = CrmTask::query()->where('cliente_id', $cliente->id)->first();

    expect($task)->not->toBeNull();
    expect($task->assigned_to)->toBe($user->id);
    expect($task->created_by)->toBe($user->id);
    expect($task->estado)->toBe('pending');
});
