<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Models\Core\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LegacyClientImportService
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
        string $duplicateMode = 'crear_o_actualizar',
        bool $stopOnAnyError = false
    ): array {
        $rowResults = [];
        $summary = [
            'total' => count($rows),
            'validas' => 0,
            'errores' => 0,
            'omitidas' => 0,
            'importadas' => 0,
            'actualizadas' => 0,
        ];

        $fallbackUser = $this->sellerUserResolver->fallbackUser();

        foreach ($rows as $row) {
            $errors = $this->validateClientRow($row);
            if ($errors !== []) {
                $summary['errores']++;
                $rowResults[] = $this->rowResult($row, 'error', $errors, null);
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $existing = $this->resolver->resolverClientePorCodigoODocumento(
                $row->codigo,
                $row->dni,
                $sucursalId,
                'DNI'
            );

            if ($existing && $duplicateMode === 'omitir') {
                $summary['omitidas']++;
                $rowResults[] = $this->rowResult($row, 'skipped', ['Cliente ya existe (modo omitir).'], $existing->id);

                continue;
            }

            if ($duplicateMode === 'actualizar' && ! $existing) {
                $summary['errores']++;
                $rowResults[] = $this->rowResult($row, 'error', ['No existe cliente para actualizar.'], null);
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $summary['validas']++;

            if (! $execute) {
                if ($existing) {
                    $summary['actualizadas']++;
                } else {
                    $summary['importadas']++;
                }
                $rowResults[] = $this->rowResult($row, 'valid', [], $existing?->id);

                continue;
            }

            try {
                DB::transaction(function () use ($row, $sucursalId, $userId, $existing, $duplicateMode, $fallbackUser, &$summary, &$rowResults): void {
                    $observaciones = $this->buildObservacionesLegacy($row);

                    if ($existing && in_array($duplicateMode, ['actualizar', 'crear_o_actualizar'], true)) {
                        $updates = [
                            'nombres' => $row->nombres,
                            'apellidos' => $row->apellidos,
                            'telefono' => $row->celular,
                            'direccion' => $row->direccion,
                            'origen' => $row->origen !== null && trim((string) $row->origen) !== '' ? trim((string) $row->origen) : $existing->origen,
                            'observaciones' => $this->mergeObservaciones($existing->observaciones, $observaciones),
                            'updated_by' => $userId,
                            'sucursal_id' => $sucursalId,
                        ];
                        if ($row->codigo) {
                            $updates['codigo'] = trim((string) $row->codigo);
                        }
                        if ($email = $this->resolveEmail($row->correo, $existing->id)) {
                            $updates['email'] = $email;
                        }
                        if ($row->fechaNacimiento) {
                            $updates['fecha_nacimiento'] = $row->fechaNacimiento->toDateString();
                        }
                        $existing->update($updates);
                        $summary['actualizadas']++;
                        $rowResults[] = $this->rowResult($row, 'imported', [], $existing->id);

                        return;
                    }

                    $dniDigits = preg_replace('/\D+/', '', (string) $row->dni) ?? '';

                    $cliente = Cliente::create([
                        'codigo' => trim((string) $row->codigo),
                        'tipo_documento' => 'DNI',
                        'numero_documento' => $dniDigits,
                        'nombres' => $row->nombres,
                        'apellidos' => $row->apellidos,
                        'telefono' => $row->celular,
                        'email' => $this->resolveEmail($row->correo, null),
                        'direccion' => $row->direccion,
                        'origen' => $row->origen !== null && trim((string) $row->origen) !== '' ? trim((string) $row->origen) : null,
                        'fecha_nacimiento' => $row->fechaNacimiento?->toDateString(),
                        'estado_cliente' => 'activo',
                        'observaciones' => $observaciones,
                        'created_by' => $fallbackUser->id,
                        'updated_by' => $userId,
                        'sucursal_id' => $sucursalId,
                    ]);
                    $summary['importadas']++;
                    $rowResults[] = $this->rowResult($row, 'imported', [], $cliente->id);
                });
            } catch (\Throwable $e) {
                $summary['errores']++;
                $rowResults[] = $this->rowResult($row, 'error', [$e->getMessage()], null);
                if ($stopOnAnyError) {
                    throw $e;
                }
            }
        }

        return [
            'summary' => $summary,
            'row_results' => $rowResults,
        ];
    }

    /**
     * @return list<string>
     */
    private function validateClientRow(SocioActivoRowData $row): array
    {
        $errors = [];
        if (! $row->codigo || trim((string) $row->codigo) === '') {
            $errors[] = 'CODIGO es obligatorio.';
        }
        if (! $row->nombres || trim((string) $row->nombres) === '') {
            $errors[] = 'NOMBRES es obligatorio.';
        }
        if (! $row->apellidos || trim((string) $row->apellidos) === '') {
            $errors[] = 'APELLIDOS es obligatorio.';
        }
        if (! $row->dni || trim((string) $row->dni) === '') {
            $errors[] = 'DNI es obligatorio.';
        }

        return $errors;
    }

    private function buildObservacionesLegacy(SocioActivoRowData $row): ?string
    {
        $parts = array_filter([
            $row->origen ? 'Origen: '.$row->origen : null,
            $row->vendedor ? 'Vendedor: '.$row->vendedor : null,
            $row->repartido ? 'Repartido: '.$row->repartido : null,
            $row->tipoVenta ? 'Tipo venta: '.$row->tipoVenta : null,
            $row->fechaInscripcion ? 'F. inscripción: '.$row->fechaInscripcion->toDateString() : null,
        ]);

        return $parts !== [] ? implode(' | ', $parts) : null;
    }

    private function mergeObservaciones(?string $existing, ?string $incoming): ?string
    {
        if ($incoming === null || $incoming === '') {
            return $existing;
        }
        if ($existing === null || $existing === '') {
            return $incoming;
        }
        if (str_contains($existing, $incoming)) {
            return $existing;
        }

        return $existing.' | '.$incoming;
    }

    private function resolveEmail(?string $email, ?int $ignoreClienteId): ?string
    {
        if ($email === null || trim($email) === '') {
            return null;
        }
        $email = Str::lower(trim($email));
        $q = Cliente::query()->where('email', $email);
        if ($ignoreClienteId) {
            $q->where('id', '!=', $ignoreClienteId);
        }

        return $q->exists() ? null : $email;
    }

    /**
     * @param  list<string>  $errors
     */
    private function rowResult(SocioActivoRowData $row, string $estado, array $errors, ?int $modeloId): array
    {
        return [
            'fila' => $row->rowNumber,
            'phase' => 'clientes',
            'estado' => $estado,
            'errores' => $errors,
            'modelo_id' => $modeloId,
            'codigo' => $row->codigo,
            'dni' => $row->dni,
            'nombres' => $row->nombres,
            'paquete' => $row->paquete,
            'vendedor' => $row->vendedor,
        ];
    }
}
