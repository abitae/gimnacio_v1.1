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
            'detectados' => count($entries),
            'validas' => 0,
            'errores' => 0,
            'importadas' => 0,
            'omitidas' => 0,
            'existentes' => 0,
            'a_crear' => 0,
        ];

        foreach ($entries as $entry) {
            $fila = (int) ($entry['fila'] ?? 0);
            $nombre = trim((string) ($entry['nombre'] ?? ''));
            if ($nombre === '') {
                $summary['omitidas']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'phase' => 'usuarios',
                    'estado' => 'skipped',
                    'errores' => ['Nombre vacio.'],
                    'modelo_id' => null,
                    'nombre' => null,
                ];

                continue;
            }

            $out = $this->sellerUserResolver->syncSingleVendedorEntry($nombre, $sucursalId, $execute);

            if ($out['existing']) {
                $summary['validas']++;
                $summary['importadas']++;
                $summary['existentes']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'phase' => 'usuarios',
                    'estado' => $execute ? 'imported' : 'valid',
                    'errores' => [],
                    'modelo_id' => $execute ? $out['user_id'] : null,
                    'info' => $execute ? null : 'Usuario ya existente; al confirmar se asegura rol y sucursal.',
                    'nombre' => $nombre,
                ];

                continue;
            }

            if ($execute && $out['created']) {
                $summary['validas']++;
                $summary['importadas']++;
                $summary['a_crear']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'phase' => 'usuarios',
                    'estado' => 'imported',
                    'errores' => [],
                    'modelo_id' => $out['user_id'],
                    'nombre' => $nombre,
                ];

                continue;
            }

            if (! $execute) {
                $summary['validas']++;
                $summary['importadas']++;
                $summary['a_crear']++;
                $rowResults[] = [
                    'fila' => $fila,
                    'phase' => 'usuarios',
                    'estado' => 'valid',
                    'errores' => [],
                    'modelo_id' => null,
                    'info' => 'Se creara el usuario con contrasena inicial de importacion al confirmar.',
                    'nombre' => $nombre,
                ];

                continue;
            }

            $summary['errores']++;
            $rowResults[] = [
                'fila' => $fila,
                'phase' => 'usuarios',
                'estado' => 'error',
                'errores' => ['No se pudo crear el usuario.'],
                'modelo_id' => null,
                'nombre' => $nombre,
            ];
            if ($stopOnAnyError) {
                break;
            }
        }

        return ['summary' => $summary, 'row_results' => $rowResults];
    }
}
