<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'biotime.ver', 'guard_name' => config('auth.defaults.guard')]);
});

it('descarga un zip con el exe y config.yaml', function () {
    $tmp = storage_path('app/testing-bridge-'.uniqid());
    File::makeDirectory($tmp, 0755, true, true);
    $exe = $tmp.DIRECTORY_SEPARATOR.'BioTimeBridge.exe';
    $yaml = $tmp.DIRECTORY_SEPARATOR.'config.yaml.example';
    file_put_contents($exe, 'MZ-fake-exe');
    file_put_contents($yaml, "laravel_base_url: \"https://tudominio.com\"\nlaravel_token: \"bt_REEMPLAZAR\"\n");

    config([
        'app.url' => 'https://fitness.example.test',
        'biotime.bridge_exe_path' => $exe,
        'biotime.bridge_config_path' => $yaml,
    ]);

    $this->actingAs(biotimeAdmin());

    $response = $this->get(route('biotime.bridge.download'));
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('BioTimeBridge.zip');

    File::deleteDirectory($tmp);
});

it('redirige si falta el ejecutable del puente', function () {
    config([
        'biotime.bridge_exe_path' => storage_path('app/missing-BioTimeBridge.exe'),
        'biotime.bridge_config_path' => base_path('tools/biotime-bridge/config.yaml.example'),
    ]);

    $this->actingAs(biotimeAdmin());

    $this->from(route('biotime.index', ['tab' => 'sedes']))
        ->get(route('biotime.bridge.download'))
        ->assertRedirect(route('biotime.index', ['tab' => 'sedes']));
});

it('exige autenticación para descargar el puente', function () {
    $this->get(route('biotime.bridge.download'))->assertRedirect(route('login'));
});
