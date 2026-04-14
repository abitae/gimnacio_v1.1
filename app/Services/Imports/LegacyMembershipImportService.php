<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class LegacyMembershipImportService
{
    public function __construct(
        private readonly ImportRelationResolverService $resolver,
        private readonly SellerUserResolver $sellerUserResolver,
    ) {}

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    public function process(
        array $rows,
        int $sucursalId,
        int $userId,
        bool $execute,
        bool $stopOnAnyError = false
    ): array {
        $rowResults = [];
        $summary = [
            'total' => count($rows),
            'validas' => 0,
            'errores' => 0,
            'importadas' => 0,
            'omitidas' => 0,
        ];

        $fallbackUser = $this->sellerUserResolver->fallbackUser();
        $cashMethod = PaymentMethod::withTrashed()->firstOrCreate(
            ['nombre' => 'Efectivo'],
            [
                'descripcion' => 'Pago en efectivo',
                'requiere_numero_operacion' => false,
                'requiere_entidad' => false,
                'estado' => 'activo',
            ]
        );

        foreach ($rows as $row) {
            if (! $row->soportaImportacionMembresia()) {
                $rowResults[] = [
                    'fila' => $row->rowNumber,
                    'estado' => 'skipped',
                    'errores' => ['TIPO DE VENTA no es membresía; omitido para este import.'],
                    'modelo_id' => null,
                ];

                continue;
            }

            $errors = $this->validateRow($row);
            if ($errors !== []) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => $errors, 'modelo_id' => null];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $cliente = $this->resolver->resolverClientePorCodigoODocumento($row->codigo, $row->dni, $sucursalId, 'DNI');
            if (! $cliente) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => ['No existe cliente con ese CODIGO/DNI en la sucursal. Importe clientes primero.'], 'modelo_id' => null];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $allowCreate = (bool) config('importacion.allow_create_membership_on_import', false);

            $membership = $this->resolver->resolverMembresiaPorNombre((string) $row->paquete, $sucursalId);

            if (! $membership && $allowCreate && $execute) {
                $duracion = $row->duracionDias() ?? 30;
                $membership = $this->resolver->crearMembresiaDesdeImportLegacy(
                    (string) $row->paquete,
                    $sucursalId,
                    $duracion,
                    (float) $row->costo
                );
            }

            if (! $membership && ! $allowCreate) {
                $summary['errores']++;
                $rowResults[] = [
                    'fila' => $row->rowNumber,
                    'estado' => 'error',
                    'errores' => ['No existe membresía en catálogo para el PAQUETE. Cree el paquete o active allow_create_membership_on_import en config/importacion.php.'],
                    'modelo_id' => null,
                ];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            if ($membership) {
                if ($this->findDuplicateMatricula($cliente, $membership->id, $row->fechaInicio, $row->fechaFin)) {
                    $summary['omitidas']++;
                    $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'skipped', 'errores' => ['Matrícula duplicada (mismo plan y fechas).'], 'modelo_id' => null];

                    continue;
                }
            } elseif ($this->findDuplicateMatriculaPorNombrePaquete($cliente, (string) $row->paquete, $row->fechaInicio, $row->fechaFin)) {
                $summary['omitidas']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'skipped', 'errores' => ['Matrícula duplicada (mismo plan y fechas).'], 'modelo_id' => null];

                continue;
            }

            $summary['validas']++;

            if (! $execute) {
                $summary['importadas']++;
                $rowResults[] = [
                    'fila' => $row->rowNumber,
                    'estado' => 'valid',
                    'errores' => [],
                    'modelo_id' => null,
                    'info' => $membership === null && $allowCreate
                        ? 'Se creará la membresía en catálogo al confirmar (paquete nuevo).'
                        : null,
                ];

                continue;
            }

            if (! $membership) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => ['No se pudo crear u obtener la membresía para el paquete.'], 'modelo_id' => null];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            try {
                DB::transaction(function () use ($row, $cliente, $membership, $sucursalId, $cashMethod, &$summary, &$rowResults): void {
                    $asesor = $this->sellerUserResolver->resolveOrFallback($row->vendedor);
                    $matricula = ClienteMatricula::create([
                        'cliente_id' => $cliente->id,
                        'tipo' => 'membresia',
                        'membresia_id' => $membership->id,
                        'fecha_matricula' => ($row->fechaInscripcion ?? $row->fechaInicio)?->toDateString(),
                        'fecha_inicio' => $row->fechaInicio?->toDateString(),
                        'fecha_fin' => $row->fechaFin?->toDateString(),
                        'estado' => $this->resolveMatriculaEstado($row->fechaFin),
                        'precio_lista' => $row->costo,
                        'descuento_monto' => 0,
                        'precio_final' => $row->costo,
                        'modalidad_pago' => 'contado',
                        'requiere_plan_cuotas' => false,
                        'cuota_inicial_monto' => 0,
                        'asesor_id' => $asesor->id,
                        'canal_venta' => $row->origen ? preg_replace('/\s+/u', ' ', trim((string) $row->origen)) : null,
                        'sucursal_id' => $sucursalId,
                    ]);

                    Pago::create([
                        'cliente_id' => $cliente->id,
                        'cliente_matricula_id' => $matricula->id,
                        'monto' => $row->costo,
                        'moneda' => 'PEN',
                        'metodo_pago' => 'efectivo',
                        'payment_method_id' => $cashMethod->id,
                        'fecha_pago' => ($row->fechaInscripcion ?? $row->fechaInicio)?->toDateTimeString(),
                        'es_pago_parcial' => false,
                        'saldo_pendiente' => 0,
                        'registrado_por' => $asesor->id,
                    ]);

                    $summary['importadas']++;
                    $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'imported', 'errores' => [], 'modelo_id' => $matricula->id];
                });
            } catch (\Throwable $e) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => [$e->getMessage()], 'modelo_id' => null];
                if ($stopOnAnyError) {
                    throw $e;
                }
            }
        }

        return ['summary' => $summary, 'row_results' => $rowResults];
    }

    /**
     * @return list<string>
     */
    private function validateRow(SocioActivoRowData $row): array
    {
        $errors = [];
        if (! $row->paquete) {
            $errors[] = 'PAQUETE obligatorio.';
        }
        if ($row->costo === null || $row->costo < 0) {
            $errors[] = 'COSTO inválido.';
        }
        if (! $row->fechaInicio || ! $row->fechaFin) {
            $errors[] = 'FECHA INICIO/FIN obligatorias.';
        }

        return $errors;
    }

    private function findDuplicateMatricula(Cliente $cliente, int $membresiaId, ?CarbonImmutable $fechaInicio, ?CarbonImmutable $fechaFin): ?ClienteMatricula
    {
        $query = ClienteMatricula::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'membresia')
            ->where('membresia_id', $membresiaId)
            ->whereDate('fecha_inicio', $fechaInicio?->toDateString());

        if ($fechaFin) {
            $query->whereDate('fecha_fin', $fechaFin->toDateString());
        } else {
            $query->whereNull('fecha_fin');
        }

        return $query->first();
    }

    /**
     * Vista previa (sin crear membresía en BD): detecta matrícula duplicada por nombre de paquete.
     */
    private function findDuplicateMatriculaPorNombrePaquete(Cliente $cliente, string $nombrePaquete, ?CarbonImmutable $fechaInicio, ?CarbonImmutable $fechaFin): bool
    {
        $nombrePaquete = trim($nombrePaquete);
        if ($nombrePaquete === '') {
            return false;
        }

        $normalized = mb_strtolower($nombrePaquete);

        $query = ClienteMatricula::query()
            ->where('cliente_id', $cliente->id)
            ->where('tipo', 'membresia')
            ->whereHas('membresia', function ($q) use ($normalized): void {
                $q->whereRaw('LOWER(TRIM(nombre)) = ?', [$normalized]);
            })
            ->whereDate('fecha_inicio', $fechaInicio?->toDateString());

        if ($fechaFin) {
            $query->whereDate('fecha_fin', $fechaFin->toDateString());
        } else {
            $query->whereNull('fecha_fin');
        }

        return $query->exists();
    }

    private function resolveMatriculaEstado(?CarbonImmutable $fechaFin): string
    {
        if ($fechaFin === null) {
            return 'activa';
        }

        return $fechaFin->startOfDay()->lt(now()->startOfDay()) ? 'vencida' : 'activa';
    }
}
