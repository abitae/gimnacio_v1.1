<?php

use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\Crm\LeadService;
use App\Services\SucursalContext;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['crm.ver', 'crm.crear', 'crm.editar', 'crm.eliminar', 'crm.ver_todos', 'crm.reasignar'] as $perm) {
        Permission::findOrCreate($perm, 'web');
    }
});

function crmStageParaScoping(): CrmStage
{
    return CrmStage::create([
        'nombre' => 'Nuevo',
        'orden' => 1,
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);
}

function vendedorConSucursal(\App\Models\System\Sucursal $sucursal): User
{
    $user = User::factory()->create();
    $user->givePermissionTo('crm.ver');
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();

    return $user;
}

it('restricts lead listing to the assigned vendedor by default', function () {
    $sucursal = biotimeSucursal();
    $vendedorA = vendedorConSucursal($sucursal);
    $vendedorB = vendedorConSucursal($sucursal);

    $this->actingAs($vendedorA);
    app(SucursalContext::class)->activate($sucursal);
    $stage = crmStageParaScoping();

    $leadA = Lead::create([
        'sucursal_id' => $sucursal->id,
        'nombres' => 'De A', 'apellidos' => '.', 'telefono' => '900000001',
        'estado' => 'nuevo', 'stage_id' => $stage->id, 'assigned_to' => $vendedorA->id, 'created_by' => $vendedorA->id,
    ]);
    Lead::create([
        'sucursal_id' => $sucursal->id,
        'nombres' => 'De B', 'apellidos' => '.', 'telefono' => '900000002',
        'estado' => 'nuevo', 'stage_id' => $stage->id, 'assigned_to' => $vendedorB->id, 'created_by' => $vendedorB->id,
    ]);

    $results = app(LeadService::class)->query()->get();

    expect($results->pluck('id')->all())->toBe([$leadA->id]);
});

it('lets a user with crm.ver_todos see every lead', function () {
    $sucursal = biotimeSucursal();
    $vendedorA = vendedorConSucursal($sucursal);
    $admin = vendedorConSucursal($sucursal);
    $admin->givePermissionTo('crm.ver_todos');

    $this->actingAs($admin);
    app(SucursalContext::class)->activate($sucursal);
    $stage = crmStageParaScoping();

    Lead::create([
        'sucursal_id' => $sucursal->id,
        'nombres' => 'De A', 'apellidos' => '.', 'telefono' => '900000003',
        'estado' => 'nuevo', 'stage_id' => $stage->id, 'assigned_to' => $vendedorA->id, 'created_by' => $vendedorA->id,
    ]);

    expect(app(LeadService::class)->query()->count())->toBe(1);
});

it('keeps unassigned leads visible to any vendedor', function () {
    $sucursal = biotimeSucursal();
    $vendedorA = vendedorConSucursal($sucursal);

    $this->actingAs($vendedorA);
    app(SucursalContext::class)->activate($sucursal);
    $stage = crmStageParaScoping();

    Lead::create([
        'sucursal_id' => $sucursal->id,
        'nombres' => 'Sin asignar', 'apellidos' => '.', 'telefono' => '900000004',
        'estado' => 'nuevo', 'stage_id' => $stage->id, 'assigned_to' => null, 'created_by' => $vendedorA->id,
    ]);

    expect(app(LeadService::class)->query()->count())->toBe(1);
});

it('denies viewing a lead assigned to another vendedor by direct url', function () {
    $sucursal = biotimeSucursal();
    $vendedorA = vendedorConSucursal($sucursal);
    $vendedorB = vendedorConSucursal($sucursal);

    $this->actingAs($vendedorB);
    app(SucursalContext::class)->activate($sucursal);
    $stage = crmStageParaScoping();

    $leadB = Lead::create([
        'sucursal_id' => $sucursal->id,
        'nombres' => 'De B', 'apellidos' => '.', 'telefono' => '900000005',
        'estado' => 'nuevo', 'stage_id' => $stage->id, 'assigned_to' => $vendedorB->id, 'created_by' => $vendedorB->id,
    ]);

    $this->actingAs($vendedorA);
    $this->get(route('crm.leads.show', $leadB))->assertForbidden();

    $this->actingAs($vendedorB);
    $this->get(route('crm.leads.show', $leadB))->assertOk();
});
