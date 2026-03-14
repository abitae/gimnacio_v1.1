<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\HealthRecord;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\ClienteService;

it('persists the full cliente contract including personal and emergency fields', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = app(ClienteService::class)->create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000009',
        'nombres' => 'Elena',
        'apellidos' => 'Gomez',
        'telefono' => '999888777',
        'email' => 'elena@example.com',
        'direccion' => 'Av. Siempre Viva 123',
        'ocupacion' => 'Arquitecta',
        'fecha_nacimiento' => '1994-02-10',
        'lugar_nacimiento' => 'Lima',
        'estado_civil' => 'soltero',
        'numero_hijos' => 1,
        'placa_carro' => 'ABC-123',
        'sexo' => 'femenino',
        'estado_cliente' => 'inactivo',
        'datos_emergencia' => [
            'nombre_contacto' => 'Mario Gomez',
            'telefono_contacto' => '988777666',
            'relacion' => 'Hermano',
        ],
        'consentimientos' => [
            'uso_imagen' => true,
            'tratamiento_datos' => true,
            'fecha_consentimiento' => now()->toDateString(),
        ],
    ]);

    expect($cliente->ocupacion)->toBe('Arquitecta');
    expect($cliente->lugar_nacimiento)->toBe('Lima');
    expect($cliente->estado_civil)->toBe('soltero');
    expect($cliente->numero_hijos)->toBe(1);
    expect($cliente->placa_carro)->toBe('ABC-123');
    expect($cliente->datos_emergencia['nombre_contacto'])->toBe('Mario Gomez');
    expect($cliente->consentimientos['uso_imagen'])->toBeTrue();
});

it('blocks cliente deletion when there are matriculas or health records', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000010',
        'nombres' => 'Julio',
        'apellidos' => 'Suarez',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    $membresia = Membresia::create([
        'nombre' => 'Mensual Cliente',
        'duracion_dias' => 30,
        'precio_base' => 100,
        'estado' => 'activa',
    ]);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addDays(30)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
    ]);

    HealthRecord::create([
        'cliente_id' => $cliente->id,
        'alergias' => 'Polen',
        'actualizado_por' => $user->id,
    ]);

    app(ClienteService::class)->delete($cliente->id);
})->throws(Exception::class);

it('prioritizes health_records over legacy datos_salud in cliente summaries', function () {
    $user = User::factory()->create();

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000011',
        'nombres' => 'Raul',
        'apellidos' => 'Diaz',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
        'datos_salud' => [
            'alergias' => 'Polvo',
            'medicamentos' => 'Legacy meds',
            'lesiones' => 'Legacy lesion',
        ],
    ]);

    HealthRecord::create([
        'cliente_id' => $cliente->id,
        'alergias' => 'Mariscos',
        'medicacion' => 'Antialergicos',
        'lesiones' => 'Rodilla',
        'actualizado_por' => $user->id,
    ]);

    $cliente = $cliente->fresh('healthRecord');

    expect($cliente->health_summary['alergias'])->toBe('Mariscos');
    expect($cliente->health_summary['medicacion'])->toBe('Antialergicos');
    expect($cliente->health_summary['lesiones'])->toBe('Rodilla');
});
