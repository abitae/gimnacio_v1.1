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
        'biotime.bridge_exe_fallback_paths' => [],
        'biotime.bridge_config_path' => public_path('dist/dist/config.yaml.example'),
        'biotime.bridge_config_fallback_paths' => [],
    ]);

    if (! is_file(public_path('dist/dist/config.yaml.example'))) {
        $this->markTestSkipped('Falta public/dist/dist/config.yaml.example');
    }

    $this->actingAs(biotimeAdmin());

    $this->from(route('biotime.index', ['tab' => 'sedes']))
        ->get(route('biotime.bridge.download'))
        ->assertRedirect(route('biotime.index', ['tab' => 'sedes']));
});

it('usa public/dist/dist cuando existen los archivos', function () {
    $publicExe = public_path('dist/dist/BioTimeBridge.exe');
    $publicYaml = public_path('dist/dist/config.yaml.example');

    if (! is_file($publicExe) || ! is_file($publicYaml)) {
        $this->markTestSkipped('Faltan archivos en public/dist/dist');
    }

    config([
        'biotime.bridge_exe_path' => public_path('dist/dist/BioTimeBridge.exe'),
        'biotime.bridge_config_path' => public_path('dist/dist/config.yaml.example'),
    ]);

    $service = app(\App\Services\BioTime\BioTimeBridgePackageService::class);
    expect($service->isAvailable())->toBeTrue();
    expect(str_replace('\\', '/', $service->resolveExePath()))->toBe(str_replace('\\', '/', $publicExe));
});

it('exige autenticación para descargar el puente', function () {
    $this->get(route('biotime.bridge.download'))->assertRedirect(route('login'));
});
