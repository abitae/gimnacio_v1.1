<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\Cliente\ClienteCrmPortfolioService;
use App\Services\SucursalContext;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['crm.ver', 'crm.ver_todos', 'crm.reasignar'] as $perm) {
        Permission::findOrCreate($perm, 'web');
    }
});

function crmPortfolioCliente(array $overrides = []): Cliente
{
    static $seq = 0;
    $seq++;

    return Cliente::create(array_merge([
        'tipo_documento' => 'DNI',
        'numero_documento' => '8000'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        'nombres' => 'Cliente',
        'apellidos' => 'Cartera '.$seq,
        'estado_cliente' => 'activo',
        'created_by' => User::factory()->create()->id,
    ], $overrides));
}

it('backfills asesor_crm_id from the most recent membresia matricula', function () {
    $asesor = User::factory()->create();
    $cliente = crmPortfolioCliente();

    $membresia = Membresia::create([
        'nombre' => 'Mensual',
        'duracion_dias' => 30,
        'precio_base' => 100,
        'estado' => 'activa',
    ]);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->subDays(10)->toDateString(),
        'fecha_inicio' => now()->subDays(10)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'asesor_id' => $asesor->id,
    ]);

    $this->artisan('crm:backfill-asesor')->assertExitCode(0);

    expect($cliente->fresh()->asesor_crm_id)->toBe($asesor->id);
});

it('falls back to created_by when there is no matricula', function () {
    $creator = User::factory()->create();
    $cliente = crmPortfolioCliente(['created_by' => $creator->id]);

    $this->artisan('crm:backfill-asesor')->assertExitCode(0);

    expect($cliente->fresh()->asesor_crm_id)->toBe($creator->id);
});

it('dry-run does not persist changes', function () {
    $creator = User::factory()->create();
    $cliente = crmPortfolioCliente(['created_by' => $creator->id]);

    $this->artisan('crm:backfill-asesor --dry-run')->assertExitCode(0);

    expect($cliente->fresh()->asesor_crm_id)->toBeNull();
});

it('restricts the portfolio query to the own cartera unless crm.ver_todos', function () {
    $sucursal = biotimeSucursal();
    $vendedorA = User::factory()->create();
    $vendedorA->givePermissionTo('crm.ver');
    $vendedorA->sucursales()->attach($sucursal->id);
    $vendedorA->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    $vendedorB = User::factory()->create();
    $vendedorB->givePermissionTo('crm.ver');

    $this->actingAs($vendedorA);
    app(SucursalContext::class)->activate($sucursal);

    $clienteA = crmPortfolioCliente(['asesor_crm_id' => $vendedorA->id]);
    crmPortfolioCliente(['asesor_crm_id' => $vendedorB->id]);

    $result = app(ClienteCrmPortfolioService::class)->query()->get();

    expect($result->pluck('id')->all())->toBe([$clienteA->id]);
});

it('allows reassigning the asesor crm of a cliente', function () {
    $vendedorA = User::factory()->create();
    $vendedorB = User::factory()->create();
    $cliente = crmPortfolioCliente(['asesor_crm_id' => $vendedorA->id]);

    $updated = app(ClienteCrmPortfolioService::class)->reassign($cliente, $vendedorB->id);

    expect($updated->asesor_crm_id)->toBe($vendedorB->id);
});
