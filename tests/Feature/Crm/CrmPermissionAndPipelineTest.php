<?php

use App\Livewire\Crm\CrmPipelineLive;
use App\Livewire\Crm\LeadDetailLive;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\Crm\ConvertLeadToClientService;
use App\Services\Crm\LeadService;
use App\Services\SucursalContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['crm.ver', 'crm.crear', 'crm.editar', 'crm.convertir', 'crm_mensaje.ver', 'crm_mensaje.enviar'] as $perm) {
        Permission::findOrCreate($perm, 'web');
    }
});

it('denies crm pipeline without permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('crm.pipeline'))->assertForbidden();
});

it('allows crm pipeline with crm.ver', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('crm.ver');
    $this->actingAs($user);

    $this->get(route('crm.pipeline'))->assertOk();
});

it('rejects invalid pipeline stage transition', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['crm.ver', 'crm.editar']);
    $this->actingAs($user);

    $stages = [
        CrmStage::create(['nombre' => 'Nuevo', 'orden' => 1, 'is_default' => true, 'is_won' => false, 'is_lost' => false]),
        CrmStage::create(['nombre' => 'Contactado', 'orden' => 2, 'is_default' => false, 'is_won' => false, 'is_lost' => false]),
        CrmStage::create(['nombre' => 'Negociación', 'orden' => 6, 'is_default' => false, 'is_won' => false, 'is_lost' => false]),
    ];

    $lead = Lead::create([
        'telefono' => '999000111',
        'estado' => 'negociacion',
        'stage_id' => $stages[2]->id,
        'created_by' => $user->id,
    ]);

    $service = app(LeadService::class);

    expect(fn () => $service->moveToStage($lead, $stages[0]->id))
        ->toThrow(InvalidArgumentException::class);
});

it('stores lead origen on cliente when converting', function () {
    config(['crm.conversion.require_qualified_stage' => false]);

    $user = User::factory()->create();
    $user->givePermissionTo('crm.convertir');
    $this->actingAs($user);

    $stage = CrmStage::create([
        'nombre' => 'Nuevo',
        'orden' => 1,
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);

    $lead = Lead::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000099',
        'nombres' => 'Ana',
        'apellidos' => 'Rios',
        'telefono' => '988776655',
        'estado' => 'nuevo',
        'stage_id' => $stage->id,
        'assigned_to' => $user->id,
        'created_by' => $user->id,
    ]);

    $result = app(ConvertLeadToClientService::class)->convert($lead, [
        'tipo_documento' => 'DNI',
        'numero_documento' => '70000099',
        'nombres' => 'Ana',
        'apellidos' => 'Rios',
        'telefono' => '988776655',
    ]);

    expect($result['cliente']->lead_origen_id)->toBe($lead->id);
    expect($result['cliente']->asesor_crm_id)->toBe($user->id);
    expect($result['lead']->converted_by)->toBe($user->id);
    expect($result['lead']->converted_at)->not->toBeNull();
});

it('detects duplicate lead phone on create', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['crm.crear', 'crm.editar']);
    $this->actingAs($user);

    $stage = CrmStage::create([
        'nombre' => 'Nuevo',
        'orden' => 1,
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);

    Lead::create([
        'telefono' => '911222333',
        'estado' => 'nuevo',
        'stage_id' => $stage->id,
        'created_by' => $user->id,
    ]);

    $duplicate = app(LeadService::class)->findDuplicateByTelefono('911222333');

    expect($duplicate)->not->toBeNull();
});

it('abre el detalle del lead cuando la ruta inyecta el modelo', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $user->givePermissionTo('crm.ver');
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $stage = CrmStage::create([
        'nombre' => 'Nuevo',
        'orden' => 1,
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);

    $lead = Lead::create([
        'sucursal_id' => $sucursal->id,
        'nombres' => 'Luis',
        'apellidos' => 'Perez',
        'telefono' => '911222334',
        'estado' => 'nuevo',
        'stage_id' => $stage->id,
        'created_by' => $user->id,
    ]);

    Livewire::test(LeadDetailLive::class, ['lead' => $lead])
        ->assertOk()
        ->assertSet('leadId', $lead->id)
        ->assertSee('Luis Perez');

    $this->get(route('crm.leads.show', $lead))
        ->assertOk()
        ->assertSee('Luis Perez');
});

it('colapsa y expande columnas de etapa en el pipeline', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $user->givePermissionTo('crm.ver');
    $user->sucursales()->attach($sucursal->id);
    $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $conLeads = CrmStage::create([
        'sucursal_id' => $sucursal->id,
        'nombre' => 'Nuevo',
        'orden' => 1,
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);
    $vacia = CrmStage::create([
        'sucursal_id' => $sucursal->id,
        'nombre' => 'Perdido',
        'orden' => 2,
        'is_default' => false,
        'is_won' => false,
        'is_lost' => true,
    ]);

    Lead::create([
        'sucursal_id' => $sucursal->id,
        'telefono' => '911222335',
        'estado' => 'nuevo',
        'stage_id' => $conLeads->id,
        'created_by' => $user->id,
    ]);

    Livewire::test(CrmPipelineLive::class)
        ->assertDontSee('Expandir todas')
        ->call('toggleStageCollapse', $conLeads->id)
        ->assertSet('collapsedStageIds', [$conLeads->id])
        ->assertSee('Expandir todas')
        ->call('collapseEmptyStages')
        ->assertSet('collapsedStageIds', [$conLeads->id, $vacia->id])
        ->call('expandAllStages')
        ->assertSet('collapsedStageIds', [])
        ->call('toggleStageCollapse', $vacia->id)
        ->call('toggleStageCollapse', $vacia->id)
        ->assertSet('collapsedStageIds', []);
});
