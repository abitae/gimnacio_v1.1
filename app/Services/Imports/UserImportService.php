<?php

namespace App\Services\Imports;

class UserImportService
{
    public function __construct(
        private readonly SellerUserResolver $sellerUserResolver,
    ) {}

    /**
     * @param  list<array{fila: int, nombre: string}>  $entries
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    public function process(
        array $entries,
        int $sucursalId,
        int $userId,
        bool $execute,
        bool $stopOnAnyError = false
    ): array {
        $rowResults = [];
        $summary = [
            'total' => count($entries),
            'validas' => 0,
            'errores' => 0,
            'importadas' => 0,
            'omitidas' => 0,
        ];

        foreach ($entries as $entry) {
            $fila = (int) ($entry['fila'] ?? 0);
            $nombre = trim((string) ($entry['nombre'] ?? ''));
            if ($nombre === '') {
                $summary['omitidas']++;
                $rowResults[] = ['fila' => $fila, 'estado' => 'skipped', 'errores' => ['Nombre vacío.'], 'modelo_id' => null];

                continue;
            }

            $out = $this->sellerUserResolver->syncSingleVendedorEntry($nombre, $sucursalId, $execute);

            if ($out['existing']) {
                $summary['validas']++;
                $summary['importadas']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'estado' => $execute ? 'imported' : 'valid',
                    'errores' => [],
                    'modelo_id' => $execute ? $out['user_id'] : null,
                    'info' => $execute ? null : 'Usuario ya existente; al confirmar se asegura rol y sucursal.',
                ];

                continue;
            }

            if ($execute && $out['created']) {
                $summary['validas']++;
                $summary['importadas']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'estado' => 'imported',
                    'errores' => [],
                    'modelo_id' => $out['user_id'],
                ];

                continue;
            }

            if (! $execute) {
                $summary['validas']++;
                $summary['importadas']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'estado' => 'valid',
                    'errores' => [],
                    'modelo_id' => null,
                    'info' => 'Se creará el usuario con contraseña inicial de importación al confirmar.',
                ];

                continue;
            }

            $summary['errores']++;
            $rowResults[] = ['fila' => $fila, 'estado' => 'error', 'errores' => ['No se pudo crear el usuario.'], 'modelo_id' => null];
            if ($stopOnAnyError) {
                break;
            }
        }

        return ['summary' => $summary, 'row_results' => $rowResults];
    }
}
