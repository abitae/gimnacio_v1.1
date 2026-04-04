<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use App\Imports\RawExcelArrayImport;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelDeudasReader
{
    public const EXPECTED_HEADERS = [
        'CODIGO',
        'NOMBRES',
        'CORREO',
        'DNI',
        'CELULAR',
        'TIPO PLAN',
        'PLAN',
        'FECHA INICIO',
        'FECHA FIN',
        'COSTO',
        'DEBE',
        'VENDEDOR',
    ];

    public function __construct(
        private readonly DeudaRowNormalizer $normalizer,
    ) {}

    /**
     * @return list<DeudaClienteRowData>
     */
    public function read(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("No se encontro el archivo Excel: {$filePath}");
        }

        $sheet = $this->readSheet($filePath);
        if (! is_array($sheet) || $sheet === []) {
            throw new RuntimeException('El archivo Excel no contiene filas legibles.');
        }

        [$headerIndex, $headers] = $this->resolveHeaders($sheet);

        $rows = [];
        foreach (array_slice($sheet, $headerIndex + 1) as $offset => $row) {
            if (! is_array($row) || $this->isBlankRow($row)) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = $row[$index] ?? null;
            }

            $rows[] = $this->normalizer->normalize($headerIndex + $offset + 2, $assoc);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @return array{0:int,1:array<int,string>}
     */
    private function resolveHeaders(array $sheet): array
    {
        $expectedMap = [];
        foreach (self::EXPECTED_HEADERS as $header) {
            $expectedMap[$this->normalizeHeader($header)] = $header;
        }

        foreach ($sheet as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = [];
            foreach ($row as $cell) {
                $normalized[] = $this->normalizeHeader((string) $cell);
            }

            if (in_array('dni', $normalized, true) && in_array('plan', $normalized, true) && in_array('debe', $normalized, true)) {
                $missing = [];
                foreach (self::EXPECTED_HEADERS as $header) {
                    if (! in_array($this->normalizeHeader($header), $normalized, true)) {
                        $missing[] = $header;
                    }
                }

                if ($missing !== []) {
                    throw new RuntimeException('Faltan encabezados requeridos: '.implode(', ', $missing));
                }

                $canonical = [];
                foreach ($normalized as $position => $normalizedHeader) {
                    $canonical[$position] = $expectedMap[$normalizedHeader] ?? trim((string) ($row[$position] ?? ''));
                }

                return [$index, $canonical];
            }
        }

        throw new RuntimeException('No se encontro la fila de encabezados esperada en el Excel.');
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readSheet(string $filePath): array
    {
        $rawContents = File::get($filePath);
        if ($this->looksLikeHtml($rawContents)) {
            return $this->parseHtmlTable($rawContents);
        }

        $sheets = Excel::toArray(new RawExcelArrayImport, $filePath);

        return $sheets[0] ?? [];
    }

    private function looksLikeHtml(string $contents): bool
    {
        $start = Str::lower(trim(substr($contents, 0, 256)));

        return str_starts_with($start, '<!doctype html') || str_starts_with($start, '<html');
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function parseHtmlTable(string $html): array
    {
        $internalErrors = libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new DOMXPath($dom);
        $rows = [];

        foreach ($xpath->query('//table//tr') as $tr) {
            $row = [];
            foreach ($xpath->query('./th|./td', $tr) as $cell) {
                $row[] = trim(html_entity_decode($cell->textContent ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }
            if ($row !== []) {
                $rows[] = $row;
            }
        }

        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $rows;
    }

    private function normalizeHeader(string $value): string
    {
        $value = $this->repairEncoding(trim($value));
        $value = Str::ascii($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace(['.', '"'], '', $value);

        return Str::lower(trim($value));
    }

    private function repairEncoding(string $value): string
    {
        if (! preg_match('/Ã|Â|â/u', $value)) {
            return $value;
        }

        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);

        return is_string($converted) && $converted !== '' ? $converted : $value;
    }
}
