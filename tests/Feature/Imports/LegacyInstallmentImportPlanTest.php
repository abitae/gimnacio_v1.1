<?php

use App\DataTransferObjects\Imports\CuotaClienteRowData;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\Imports\LegacyInstallmentImportService;
use Carbon\CarbonImmutable;

function legacyCuotaRow(
    int $rowNumber,
    string $codigo,
    string $membresia,
    CarbonImmutable $fechaInicio,
    CarbonImmutable $fechaFin,
    CarbonImmutable $fechaCuota,
    float $montoCuota,
    ?float $pago = null
): CuotaClienteRowData {
    $precio = 300.0;
    $pagoVal = $pago ?? 0.0;

    return new CuotaClienteRowData(
        $rowNumber,
        $codigo,
        'Nombre',
        null,
        $membresia,
        $fechaInicio,
        $fechaFin,
        null,
        $precio,
        $pagoVal,
        $fechaCuota,
        max(0, round($precio - $pagoVal, 2)),
        $montoCuota,
    );
}

function seedClienteMatriculaCuotasContext(string $codigo = 'CXLEG1'): array
{
    $suffix = preg_replace('/\W/', '', $codigo) ?: 'X';
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa test cuotas '.$suffix,
        'estado' => 'activa',
    ]);
    $sucursal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'S-LEG-'.substr(md5($codigo.(string) microtime(true)), 0, 8),
        'nombre' => 'Sucursal test '.$suffix,
        'estado' => 'activa',
    ]);
    $user = User::factory()->create(['estado' => 'activo']);
    $dni = str_pad((string) (random_int(10000000, 89999999)), 8, '0', STR_PAD_LEFT);
    $cliente = Cliente::factory()->create([
        'codigo' => $codigo,
        'sucursal_id' => $sucursal->id,
        'tipo_documento' => 'DNI',
        'numero_documento' => $dni,
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $membresia = Membresia::factory()->create([
        'nombre' => 'PLAN CUOTAS LEGACY '.$suffix,
        'precio_base' => 300,
        'estado' => 'activa',
        'sucursal_id' => $sucursal->id,
    ]);
    $matricula = ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-03-01',
        'fecha_inicio' => '2026-03-01',
        'fecha_fin' => '2026-03-31',
        'estado' => 'activa',
        'precio_lista' => 300,
        'descuento_monto' => 0,
        'precio_final' => 300,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]);

    return compact('sucursal', 'user', 'cliente', 'membresia', 'matricula');
}

it('resolves installment plan by cliente_id when cliente_matricula_id is null', function () {
    $ctx = seedClienteMatriculaCuotasContext();
    $fi = CarbonImmutable::parse('2026-03-01');
    $ff = CarbonImmutable::parse('2026-03-31');
    $fc = CarbonImmutable::parse('2026-03-10');

    EnrollmentInstallmentPlan::query()->create([
        'cliente_id' => $ctx['cliente']->id,
        'cliente_matricula_id' => null,
        'monto_total' => 300,
        'numero_cuotas' => 3,
        'monto_cuota' => 100,
        'frecuencia' => 'mensual',
        'fecha_inicio' => '2026-03-01',
        'observaciones' => null,
    ]);

    $row = legacyCuotaRow(2, 'CXLEG1', $ctx['membresia']->nombre, $fi, $ff, $fc, 100.0);

    $out = app(LegacyInstallmentImportService::class)->process(
        [$row],
        (int) $ctx['sucursal']->id,
        (int) $ctx['user']->id,
        true,
        false
    );

    expect($out['summary']['errores'])->toBe(0)
        ->and($out['summary']['importadas'])->toBe(1)
        ->and(EnrollmentInstallment::query()->count())->toBe(1);
});

it('creates plan and installment when client has no enrollment plan', function () {
    $ctx = seedClienteMatriculaCuotasContext('CXLEG2');
    $fi = CarbonImmutable::parse('2026-03-01');
    $ff = CarbonImmutable::parse('2026-03-31');
    $fc = CarbonImmutable::parse('2026-03-15');

    expect(EnrollmentInstallmentPlan::query()->where('cliente_id', $ctx['cliente']->id)->count())->toBe(0);

    $row = legacyCuotaRow(2, 'CXLEG2', $ctx['membresia']->nombre, $fi, $ff, $fc, 75.0);

    $out = app(LegacyInstallmentImportService::class)->process(
        [$row],
        (int) $ctx['sucursal']->id,
        (int) $ctx['user']->id,
        true,
        false
    );

    expect($out['summary']['errores'])->toBe(0)
        ->and($out['summary']['importadas'])->toBe(1);

    $plan = EnrollmentInstallmentPlan::query()->where('cliente_id', $ctx['cliente']->id)->first();
    expect($plan)->not->toBeNull()
        ->and($plan->cliente_matricula_id)->toBeNull()
        ->and(EnrollmentInstallment::query()->where('enrollment_installment_plan_id', $plan->id)->count())->toBe(1);
});

it('preview includes info when plan will be created on commit', function () {
    $ctx = seedClienteMatriculaCuotasContext('CXLEG3');
    $fi = CarbonImmutable::parse('2026-03-01');
    $ff = CarbonImmutable::parse('2026-03-31');
    $fc = CarbonImmutable::parse('2026-03-20');

    $row = legacyCuotaRow(2, 'CXLEG3', $ctx['membresia']->nombre, $fi, $ff, $fc, 50.0);

    $out = app(LegacyInstallmentImportService::class)->process(
        [$row],
        (int) $ctx['sucursal']->id,
        (int) $ctx['user']->id,
        false,
        false
    );

    $first = $out['row_results'][0] ?? [];
    expect($first['estado'] ?? null)->toBe('valid')
        ->and($first['info'] ?? null)->toContain('plan de cuotas');
});
