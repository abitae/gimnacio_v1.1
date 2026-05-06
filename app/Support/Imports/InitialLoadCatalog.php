<?php

namespace App\Support\Imports;

use App\Services\Imports\ExcelClientesMaestroReader;
use App\Services\Imports\ExcelContratosDeudaReader;
use App\Services\Imports\ExcelCuotasLegacyReader;
use App\Services\Imports\ExcelDeudasReader;
use App\Services\Imports\ExcelSociosReader;
use App\Services\Imports\ExcelVendedorColumnReader;

final class InitialLoadCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            ImportType::USUARIOS => [
                'label' => 'Usuarios',
                'description' => 'Alta inicial de usuarios o vendedores desde una hoja con columna VENDEDOR.',
                'sheet_title' => ExcelVendedorColumnReader::SHEET_NAME,
                'title_row' => 'Carga inicial de usuarios - usar la hoja Usuarios Vendedores del consolidado.',
                'headers' => ExcelVendedorColumnReader::EXPECTED_HEADERS,
                'preferred_sheet' => ExcelVendedorColumnReader::SHEET_NAME,
                'markers' => ['VENDEDOR'],
                'filename' => 'plantilla-carga-inicial-usuarios.xlsx',
                'accepted_mimes' => 'xlsx,xls,csv',
            ],
            ImportType::CLIENTES => [
                'label' => 'Clientes',
                'description' => 'Carga inicial de clientes desde la hoja Clientes Maestro del consolidado.',
                'sheet_title' => 'Clientes Maestro',
                'title_row' => 'Carga inicial de clientes - usar la estructura de la hoja Clientes Maestro.',
                'headers' => ExcelClientesMaestroReader::EXPECTED_HEADERS,
                'preferred_sheet' => ExcelClientesMaestroReader::SHEET_NAME,
                'markers' => ['CODIGO', 'CLIENTE', 'DNI', 'ULTIMA_MEMBRESIA'],
                'filename' => 'plantilla-carga-inicial-clientes.xlsx',
                'accepted_mimes' => 'xlsx,xls,csv',
            ],
            ImportType::MEMBRESIAS_MATRICULAS => [
                'label' => 'Membresias y matriculas',
                'description' => 'Carga inicial de catalogo de membresias y matriculas usando la hoja Contratos Deuda.',
                'sheet_title' => ExcelContratosDeudaReader::SHEET_NAME,
                'title_row' => 'Carga inicial de membresias y matriculas - usar la hoja Contratos Deuda.',
                'headers' => ExcelContratosDeudaReader::EXPECTED_HEADERS,
                'preferred_sheet' => ExcelContratosDeudaReader::SHEET_NAME,
                'markers' => ['CODIGO', 'MEMBRESIA', 'F. INICIO', 'F. FIN'],
                'filename' => 'plantilla-carga-inicial-membresias-matriculas.xlsx',
                'accepted_mimes' => 'xlsx,xls,csv',
            ],
            ImportType::CUOTAS => [
                'label' => 'Cuotas',
                'description' => 'Carga inicial de cronogramas de cuotas enlazados a matriculas existentes.',
                'sheet_title' => ExcelCuotasLegacyReader::SHEET_NAME,
                'title_row' => 'Carga inicial de cuotas - usar la hoja Detalle Cuotas.',
                'headers' => ExcelCuotasLegacyReader::EXPECTED_HEADERS,
                'preferred_sheet' => ExcelCuotasLegacyReader::SHEET_NAME,
                'markers' => ['CODIGO', 'FECHA CUOTA', 'M. CUOTA'],
                'filename' => 'plantilla-carga-inicial-cuotas.xlsx',
                'accepted_mimes' => 'xlsx,xls,csv',
            ],
            ImportType::DEUDAS => [
                'label' => 'Cuotas resumidas / deudas',
                'description' => 'Carga inicial de deudas resumen por cliente.',
                'sheet_title' => ExcelDeudasReader::SHEET_NAME,
                'title_row' => 'Carga inicial de deudas - usar la hoja Deudas Clientes.',
                'headers' => ExcelDeudasReader::EXPECTED_HEADERS,
                'preferred_sheet' => ExcelDeudasReader::SHEET_NAME,
                'markers' => ['DNI', 'PLAN', 'DEBE'],
                'filename' => 'plantilla-carga-inicial-deudas.xlsx',
                'accepted_mimes' => 'xlsx,xls,csv',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function implemented(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, mixed>
     */
    public static function for(string $type): array
    {
        $config = self::all()[$type] ?? null;

        if ($config === null) {
            throw new \InvalidArgumentException('Tipo de carga inicial no soportado: '.$type);
        }

        return $config;
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        $labels = [];

        foreach (self::all() as $type => $config) {
            $labels[$type] = (string) $config['label'];
        }

        return $labels;
    }
}
