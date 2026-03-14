<?php

use App\Models\Core\Caja;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Membresia;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\Crm\ConvertLeadToClientService;

it('converts a lead and creates a cliente_matricula instead of a legacy membership', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $stage = CrmStage::create([
        'nombre' => 'Nuevo',
        'orden' => 1,
        'is_won' => false,
        'is_lost' => false,
    ]);

    $lead = Lead::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000007',
        'nombres' => 'Lucia',
        'apellidos' => 'Campos',
        'telefono' => '999111222',
        'estado' => 'nuevo',
        'stage_id' => $stage->id,
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'CRM Premium',
        'duracion_dias' => 60,
        'precio_base' => 140,
        'estado' => 'activa',
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $result = app(ConvertLeadToClientService::class)->convert($lead, [
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000007',
        'nombres' => 'Lucia',
        'apellidos' => 'Campos',
        'telefono' => '999111222',
        'activar_membresia' => true,
        'membresia_id' => $membresia->id,
        'pago' => [
            'descuento' => 10,
            'monto' => 50,
            'metodo_pago' => 'efectivo',
        ],
    ]);

    $cliente = $result['cliente']->fresh();
    $matricula = ClienteMatricula::query()->where('cliente_id', $cliente->id)->first();

    expect($matricula)->not->toBeNull();
    expect($matricula->tipo)->toBe('membresia');
    expect($matricula->membresia_id)->toBe($membresia->id);
    expect(ClienteMembresia::query()->where('cliente_id', $cliente->id)->count())->toBe(0);
    expect($matricula->pagos()->count())->toBe(2);
});
