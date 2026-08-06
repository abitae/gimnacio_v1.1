<?php

use App\Livewire\Crm\CrmPipelineLive;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use App\Services\Crm\CrmStageService;
use App\Services\SucursalContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['crm.ver', 'crm.crear', 'crm.editar', 'crm.eliminar'] as $perm) {
        Permission::findOrCreate($perm, 'web');
    }

    $this->sucursal = biotimeSucursal('crm-stages');
    app(SucursalContext::class)->activate($this->sucursal);
});

function makeStageUser(array $permissions): User
{
    $user = User::factory()->create();
    $user->givePermissionTo($permissions);

    return $user;
}

it('creates a stage and assigns orden and default when none exists', function () {
    $service = app(CrmStageService::class);

    $stage = $service->create([
        'nombre' => 'Prospecto',
        'is_default' => false,
        'is_won' => false,
        'is_lost' => false,
    ]);

    expect($stage->nombre)->toBe('Prospecto')
        ->and($stage->orden)->toBe(1)
        ->and($stage->is_default)->toBeTrue();
});

it('keeps a single default stage when creating another default', function () {
    $service = app(CrmStageService::class);

    $first = $service->create(['nombre' => 'Nuevo', 'is_default' => true]);
    $second = $service->create(['nombre' => 'Contactado', 'is_default' => true]);

    expect($first->fresh()->is_default)->toBeFalse()
        ->and($second->fresh()->is_default)->toBeTrue()
        ->and(CrmStage::query()->where('is_default', true)->count())->toBe(1);
});

it('updates stage name and flags', function () {
    $service = app(CrmStageService::class);
    $stage = $service->create(['nombre' => 'Nuevo', 'is_default' => true]);

    $updated = $service->update($stage, [
        'nombre' => 'Lead nuevo',
        'is_default' => true,
        'is_won' => false,
        'is_lost' => false,
    ]);

    expect($updated->nombre)->toBe('Lead nuevo');
});

it('rejects won and lost at the same time', function () {
    $service = app(CrmStageService::class);

    expect(fn () => $service->create([
        'nombre' => 'Ambiguo',
        'is_won' => true,
        'is_lost' => true,
    ]))->toThrow(InvalidArgumentException::class);
});

it('rejects clearing the only default stage', function () {
    $service = app(CrmStageService::class);
    $stage = $service->create(['nombre' => 'Nuevo', 'is_default' => true]);

    expect(fn () => $service->update($stage, [
        'nombre' => 'Nuevo',
        'is_default' => false,
        'is_won' => false,
        'is_lost' => false,
    ]))->toThrow(InvalidArgumentException::class, 'Debe existir al menos una etapa por defecto.');
});

it('reorders stages with moveUp and moveDown', function () {
    $service = app(CrmStageService::class);
    $a = $service->create(['nombre' => 'A', 'is_default' => true]);
    $b = $service->create(['nombre' => 'B']);
    $c = $service->create(['nombre' => 'C']);

    $service->moveUp($c->fresh());
    expect($c->fresh()->orden)->toBe(2)
        ->and($b->fresh()->orden)->toBe(3);

    $service->moveDown($a->fresh());
    expect($a->fresh()->orden)->toBe(2)
        ->and($c->fresh()->orden)->toBe(1);
});

it('deletes an empty stage and reassigns default', function () {
    $service = app(CrmStageService::class);
    $default = $service->create(['nombre' => 'Nuevo', 'is_default' => true]);
    $other = $service->create(['nombre' => 'Contactado']);

    $service->delete($default);

    expect(CrmStage::find($default->id))->toBeNull()
        ->and($other->fresh()->is_default)->toBeTrue();
});

it('does not delete a stage that has leads', function () {
    $user = makeStageUser(['crm.ver']);
    $this->actingAs($user);

    $service = app(CrmStageService::class);
    $stage = $service->create(['nombre' => 'Nuevo', 'is_default' => true]);

    Lead::create([
        'telefono' => '999111222',
        'estado' => 'nuevo',
        'stage_id' => $stage->id,
        'created_by' => $user->id,
    ]);

    expect(fn () => $service->delete($stage->fresh()))
        ->toThrow(InvalidArgumentException::class, 'No se puede eliminar una etapa con leads');
});

it('denies managing stages without crm.editar', function () {
    $user = makeStageUser(['crm.ver']);
    $this->actingAs($user);

    Livewire::test(CrmPipelineLive::class)
        ->call('openManageStages')
        ->assertForbidden();
});

it('allows creating a stage from the pipeline with crm.crear', function () {
    $user = makeStageUser(['crm.ver', 'crm.crear', 'crm.editar']);
    $this->actingAs($user);

    Livewire::test(CrmPipelineLive::class)
        ->call('openCreateStage')
        ->set('stageNombre', 'Calificado')
        ->set('stageIsDefault', true)
        ->call('saveStage')
        ->assertHasNoErrors();

    expect(CrmStage::query()->where('nombre', 'Calificado')->exists())->toBeTrue();
});

it('allows editing and reordering stages with crm.editar', function () {
    $user = makeStageUser(['crm.ver', 'crm.editar']);
    $this->actingAs($user);

    $service = app(CrmStageService::class);
    $a = $service->create(['nombre' => 'Nuevo', 'is_default' => true]);
    $b = $service->create(['nombre' => 'Contactado']);

    Livewire::test(CrmPipelineLive::class)
        ->call('openEditStage', $b->id)
        ->set('stageNombre', 'Contactado hoy')
        ->call('saveStage')
        ->assertHasNoErrors()
        ->call('moveStageUp', $b->id);

    expect($b->fresh()->nombre)->toBe('Contactado hoy')
        ->and($b->fresh()->orden)->toBe(1)
        ->and($a->fresh()->orden)->toBe(2);
});

it('deletes a stage from the pipeline with crm.eliminar', function () {
    $user = makeStageUser(['crm.ver', 'crm.editar', 'crm.eliminar']);
    $this->actingAs($user);

    $service = app(CrmStageService::class);
    $service->create(['nombre' => 'Nuevo', 'is_default' => true]);
    $empty = $service->create(['nombre' => 'Temporal']);

    Livewire::test(CrmPipelineLive::class)
        ->call('deleteStage', $empty->id);

    expect(CrmStage::find($empty->id))->toBeNull();
});
