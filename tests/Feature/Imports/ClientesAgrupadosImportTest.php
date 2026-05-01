<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\Import;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\Imports\ImportManagerService;
use App\Support\Imports\ImportType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

it('previews clientes agrupados without mutating business tables', function () {
    $ctx = seedClientesAgrupadosContext();
    Auth::login($ctx['user']);
    $path = createClientesAgrupadosExcel([
        clientesAgrupadosContractRow(codigo: '1', precio: 300, pagado: 100, deuda: 200, montoCuota: 100, cuotasPendientes: 2),
    ]);

    $file = new UploadedFile($path, 'Clientes_Agrupados.xlsx', null, null, true);
    $out = app(ImportManagerService::class)->preview($file, ImportType::CLIENTES_AGRUPADOS, $ctx['sucursal']->id);

    expect($out['result']['summary']['validas'])->toBe(1)
        ->and($out['result']['phase_summaries']['clientes_agrupados']['clientes_creados'])->toBe(1)
        ->and(Cliente::query()->count())->toBe(0)
        ->and(ClienteMatricula::query()->count())->toBe(0)
        ->and(Pago::query()->count())->toBe(0)
        ->and(EnrollmentInstallment::query()->count())->toBe(0);
});

it('commits clientes agrupados creating client membership enrollment payment and installments', function () {
    $ctx = seedClientesAgrupadosContext();
    Auth::login($ctx['user']);
    $path = createClientesAgrupadosExcel([
        clientesAgrupadosContractRow(
            codigo: '15',
            nombres: 'MARIA,LOPEZ RAMOS',
            celular: '51911112222',
            membresia: 'PLAN NUEVO',
            fechaInicio: '2026-01-01',
            fechaFin: '2026-06-30',
            precio: 600,
            pagado: 200,
            deuda: 400,
            montoCuota: 100,
            cuotasPendientes: 4
        ),
    ]);

    $preview = app(ImportManagerService::class)->preview(
        new UploadedFile($path, 'Clientes_Agrupados.xlsx', null, null, true),
        ImportType::CLIENTES_AGRUPADOS,
        $ctx['sucursal']->id
    );
    app(ImportManagerService::class)->commit($preview['import']);

    $cliente = Cliente::query()->where('codigo', '15')->firstOrFail();
    $matricula = ClienteMatricula::query()->where('cliente_id', $cliente->id)->firstOrFail();
    $pago = Pago::query()->where('cliente_matricula_id', $matricula->id)->firstOrFail();

    expect($cliente->tipo_documento)->toBe('CE')
        ->and($cliente->numero_documento)->toBe('15')
        ->and($cliente->nombres)->toBe('MARIA')
        ->and($cliente->apellidos)->toBe('LOPEZ RAMOS')
        ->and(Membresia::query()->where('nombre', 'PLAN NUEVO')->exists())->toBeTrue()
        ->and((float) $matricula->precio_final)->toBe(600.0)
        ->and((float) $matricula->cuota_inicial_monto)->toBe(200.0)
        ->and($matricula->requiere_plan_cuotas)->toBeTrue()
        ->and($pago->metodo_pago)->toBe('Importacion legacy')
        ->and((float) $pago->monto)->toBe(200.0)
        ->and((float) $pago->saldo_pendiente)->toBe(400.0)
        ->and(EnrollmentInstallment::query()->where('cliente_matricula_id', $matricula->id)->count())->toBe(4)
        ->and((float) EnrollmentInstallment::query()->where('cliente_matricula_id', $matricula->id)->sum('monto'))->toBe(400.0);
});

it('reconciles existing cliente and matricula without duplicating enrollment', function () {
    $ctx = seedClientesAgrupadosContext();
    Auth::login($ctx['user']);
    $membresia = Membresia::factory()->create([
        'nombre' => '3 meses',
        'precio_base' => 350,
        'estado' => 'activa',
        'sucursal_id' => $ctx['sucursal']->id,
    ]);
    $cliente = Cliente::factory()->create([
        'codigo' => '10',
        'tipo_documento' => 'CE',
        'numero_documento' => '10',
        'nombres' => 'Viejo',
        'apellidos' => 'Nombre',
        'sucursal_id' => $ctx['sucursal']->id,
        'created_by' => $ctx['user']->id,
    ]);
    $matricula = ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-03-31',
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $ctx['user']->id,
        'sucursal_id' => $ctx['sucursal']->id,
    ]);
    Pago::query()->create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 100,
        'moneda' => 'PEN',
        'metodo_pago' => 'Importacion legacy',
        'fecha_pago' => '2026-01-01 00:00:00',
        'es_pago_parcial' => false,
        'saldo_pendiente' => 0,
        'registrado_por' => $ctx['user']->id,
        'sucursal_id' => $ctx['sucursal']->id,
    ]);

    $path = createClientesAgrupadosExcel([
        clientesAgrupadosContractRow(codigo: '10', nombres: 'ARONE,SANES CANELA', membresia: '3 meses', fechaInicio: '2026-01-01', fechaFin: '2026-03-31', precio: 350, pagado: 300, deuda: 50, montoCuota: 50, cuotasPendientes: 1),
    ]);

    $preview = app(ImportManagerService::class)->preview(
        new UploadedFile($path, 'Clientes_Agrupados.xlsx', null, null, true),
        ImportType::CLIENTES_AGRUPADOS,
        $ctx['sucursal']->id
    );
    app(ImportManagerService::class)->commit($preview['import']);

    $cliente->refresh();
    $matricula->refresh();

    expect(Cliente::query()->where('codigo', '10')->count())->toBe(1)
        ->and(ClienteMatricula::query()->where('cliente_id', $cliente->id)->count())->toBe(1)
        ->and($cliente->nombres)->toBe('ARONE')
        ->and((float) $matricula->precio_final)->toBe(350.0)
        ->and((float) $matricula->cuota_inicial_monto)->toBe(300.0)
        ->and((float) Pago::query()->where('cliente_matricula_id', $matricula->id)->firstOrFail()->saldo_pendiente)->toBe(50.0);
});

it('reports summary mismatches as warnings', function () {
    $ctx = seedClientesAgrupadosContext();
    Auth::login($ctx['user']);
    $path = createClientesAgrupadosExcel([
        clientesAgrupadosContractRow(codigo: '20', precio: 300, pagado: 100, deuda: 200, montoCuota: 100, cuotasPendientes: 2),
    ], [
        ['CODIGO' => '20', 'PRECIO TOTAL' => 999, 'PAGADO' => 100, 'DEUDA TOTAL' => 200],
    ]);

    $out = app(ImportManagerService::class)->preview(
        new UploadedFile($path, 'Clientes_Agrupados.xlsx', null, null, true),
        ImportType::CLIENTES_AGRUPADOS,
        $ctx['sucursal']->id
    );

    expect($out['result']['summary']['advertencias'])->toBe(1)
        ->and($out['result']['row_results'][0]['estado'])->toBe('warning')
        ->and($out['result']['row_results'][0]['warnings'][0])->toContain('PRECIO');
});

it('reports invalid required fields and impossible debt', function () {
    $ctx = seedClientesAgrupadosContext();
    Auth::login($ctx['user']);
    $path = createClientesAgrupadosExcel([
        clientesAgrupadosContractRow(codigo: '', precio: 100, pagado: 0, deuda: 150, montoCuota: null, cuotasPendientes: 1),
    ], [
        ['CODIGO' => '', 'PRECIO TOTAL' => 100, 'PAGADO' => 0, 'DEUDA TOTAL' => 150],
    ]);

    $out = app(ImportManagerService::class)->preview(
        new UploadedFile($path, 'Clientes_Agrupados.xlsx', null, null, true),
        ImportType::CLIENTES_AGRUPADOS,
        $ctx['sucursal']->id
    );

    expect($out['result']['summary']['errores'])->toBe(1)
        ->and(implode(' ', $out['result']['row_results'][0]['errores']))->toContain('CODIGO')
        ->and(implode(' ', $out['result']['row_results'][0]['errores']))->toContain('DEUDA');
});

function seedClientesAgrupadosContext(): array
{
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa Clientes Agrupados',
        'estado' => 'activa',
    ]);
    $sucursal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'AGR-'.substr(md5((string) microtime(true)), 0, 8),
        'nombre' => 'Sucursal Agrupados',
        'estado' => 'activa',
    ]);
    $user = User::factory()->create(['estado' => 'activo', 'default_sucursal_id' => $sucursal->id]);

    return compact('empresa', 'sucursal', 'user');
}

function clientesAgrupadosContractRow(
    string $codigo = '1',
    string $nombres = 'ANDREA,PUENTE BRICENO',
    string $celular = '51999999999',
    string $membresia = 'low cost 2025',
    string $vendedor = 'SIN VENDEDOR',
    string $fechaInicio = '2026-01-01',
    string $fechaFin = '2026-12-31',
    float $precio = 300,
    float $pagado = 100,
    float $deuda = 200,
    ?float $montoCuota = 100,
    int $cuotasPendientes = 2,
    string $estado = 'EN DEUDA',
): array {
    return [
        'CODIGO' => $codigo,
        'NOMBRES' => $nombres,
        'CELULAR' => $celular,
        'MEMBRESIA' => $membresia,
        'VENDEDOR' => $vendedor,
        'F. INICIO' => $fechaInicio,
        'F. FIN' => $fechaFin,
        'PRECIO' => $precio,
        'PAGADO' => $pagado,
        'DEUDA' => $deuda,
        'MONTO CUOTA' => $montoCuota,
        'CUOTAS PEND.' => $cuotasPendientes,
        'ESTADO' => $estado,
    ];
}

function createClientesAgrupadosExcel(array $contracts, ?array $summaries = null): string
{
    $spreadsheet = new Spreadsheet();
    $summarySheet = $spreadsheet->getActiveSheet();
    $summarySheet->setTitle('Resumen por Cliente');
    $summarySheet->fromArray(['RESUMEN DE CLIENTES CON DEUDA'], null, 'A1');
    $summarySheet->fromArray(['Total clientes: '.count($contracts)], null, 'A2');
    $summaryHeaders = ['CODIGO', 'NOMBRES', 'CELULAR', 'MEMBRESIAS', 'VENDEDOR(ES)', 'N° CONTRATOS', 'PRECIO TOTAL', 'PAGADO', 'DEUDA TOTAL', '% PAGADO', 'ESTADO'];
    $summarySheet->fromArray($summaryHeaders, null, 'A3');

    $summaries ??= collect($contracts)
        ->groupBy(fn (array $row): string => (string) ($row['CODIGO'] ?? ''))
        ->map(function ($rows, string $codigo): array {
            return [
                'CODIGO' => $codigo,
                'PRECIO TOTAL' => collect($rows)->sum('PRECIO'),
                'PAGADO' => collect($rows)->sum('PAGADO'),
                'DEUDA TOTAL' => collect($rows)->sum('DEUDA'),
            ];
        })
        ->values()
        ->all();

    $r = 4;
    foreach ($summaries as $summary) {
        $summarySheet->fromArray([
            $summary['CODIGO'] ?? '',
            $summary['NOMBRES'] ?? 'Cliente',
            $summary['CELULAR'] ?? '',
            $summary['MEMBRESIAS'] ?? 'Plan',
            $summary['VENDEDOR(ES)'] ?? 'SIN VENDEDOR',
            $summary['N° CONTRATOS'] ?? 1,
            $summary['PRECIO TOTAL'] ?? 0,
            $summary['PAGADO'] ?? 0,
            $summary['DEUDA TOTAL'] ?? 0,
            $summary['% PAGADO'] ?? 0,
            $summary['ESTADO'] ?? 'EN DEUDA',
        ], null, 'A'.$r++);
    }

    $detailSheet = $spreadsheet->createSheet();
    $detailSheet->setTitle('Detalle por Contrato');
    $detailSheet->fromArray(['DETALLE DE CONTRATOS POR CLIENTE'], null, 'A1');
    $detailHeaders = ['CODIGO', 'NOMBRES', 'CELULAR', 'MEMBRESIA', 'VENDEDOR', 'F. INICIO', 'F. FIN', 'PRECIO', 'PAGADO', 'DEUDA', 'MONTO CUOTA', 'CUOTAS PEND.', 'ESTADO'];
    $detailSheet->fromArray($detailHeaders, null, 'A2');

    $r = 3;
    foreach ($contracts as $row) {
        $detailSheet->fromArray(array_map(fn (string $header): mixed => $row[$header] ?? null, $detailHeaders), null, 'A'.$r++);
    }

    $path = storage_path('framework/testing/'.uniqid('clientes-agrupados-', true).'.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }
    (new Xlsx($spreadsheet))->save($path);

    return $path;
}
