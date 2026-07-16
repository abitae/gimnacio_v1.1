<?php

declare(strict_types=1);

use App\Models\BioTime\BioTimeSetting;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

it('stores defaults via forSucursal and keeps secret only where assigned', function () {
    expect(Schema::hasTable('bio_time_sucursal_settings'))->toBeTrue();

    $empresa = Empresa::query()->create(['nombre' => 'Empresa Settings', 'estado' => 'activa']);

    $principal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'prin-bt',
        'nombre' => 'Principal BT',
        'estado' => 'activa',
        'es_principal' => true,
    ]);

    $secundaria = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'sec-bt',
        'nombre' => 'Secundaria BT',
        'estado' => 'activa',
        'es_principal' => false,
    ]);

    BioTimeSetting::current()->forceFill(['webhook_secret' => 'legacy-global-secret'])->save();

    // Simula lo que hace la migracion de datos para la sede principal.
    BioTimeSucursalSetting::forSucursal($principal->id)->forceFill([
        'webhook_secret' => BioTimeSetting::activeSecret(),
    ])->save();
    BioTimeSucursalSetting::forSucursal($secundaria->id);

    $principalSetting = BioTimeSucursalSetting::query()->where('sucursal_id', $principal->id)->first();
    $secSetting = BioTimeSucursalSetting::query()->where('sucursal_id', $secundaria->id)->first();

    expect($principalSetting)->not->toBeNull()
        ->and($principalSetting->webhook_secret)->toBe('legacy-global-secret')
        ->and($secSetting)->not->toBeNull()
        ->and($secSetting->webhook_secret)->toBeNull()
        ->and($secSetting->poll_interval_seconds)->toBe(3600)
        ->and($secSetting->enabled)->toBeTrue();
});

it('regenerates a bt_ prefixed secret of expected length', function () {
    $empresa = Empresa::query()->create(['nombre' => 'Empresa Regen', 'estado' => 'activa']);
    $sucursal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'regen-bt',
        'nombre' => 'Regen BT',
        'estado' => 'activa',
        'es_principal' => true,
    ]);

    $setting = BioTimeSucursalSetting::forSucursal($sucursal->id);
    $secret = $setting->regenerateSecret();

    expect($secret)->toStartWith('bt_')
        ->and(strlen($secret))->toBe(3 + 64)
        ->and($setting->fresh()->webhook_secret)->toBe($secret)
        ->and(BioTimeSucursalSetting::findBySecret($secret)?->id)->toBe($setting->id)
        ->and(BioTimeSucursalSetting::findBySecret('wrong-secret'))->toBeNull();
});

it('enforces unique sucursal_id', function () {
    $empresa = Empresa::query()->create(['nombre' => 'Empresa Unique', 'estado' => 'activa']);
    $sucursal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'uniq-bt',
        'nombre' => 'Unique BT',
        'estado' => 'activa',
        'es_principal' => true,
    ]);

    BioTimeSucursalSetting::forSucursal($sucursal->id);

    expect(fn () => BioTimeSucursalSetting::query()->create([
        'sucursal_id' => $sucursal->id,
        'poll_interval_seconds' => 3600,
        'enabled' => true,
    ]))->toThrow(QueryException::class);
});

it('forSucursal returns the same row on repeated calls', function () {
    $empresa = Empresa::query()->create(['nombre' => 'Empresa Idem', 'estado' => 'activa']);
    $sucursal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'idem-bt',
        'nombre' => 'Idem BT',
        'estado' => 'activa',
        'es_principal' => false,
    ]);

    $a = BioTimeSucursalSetting::forSucursal($sucursal->id);
    $b = BioTimeSucursalSetting::forSucursal($sucursal->id);

    expect($a->id)->toBe($b->id)
        ->and(BioTimeSucursalSetting::query()->where('sucursal_id', $sucursal->id)->count())->toBe(1);
});
