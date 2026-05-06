<?php

namespace App\Services\Imports;

use App\Imports\RawExcelArrayImport;
use App\Support\Imports\InitialLoadCatalog;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelColumnAnalyzerService
{
    /**
     * @return array<string, mixed>
     */
    public function analyze(string $filePath, string $type): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("No se encontro el archivo Excel: {$filePath}");
        }

        $config = InitialLoadCatalog::for($type);
        $workbook = $this->readWorkbook($filePath);
        $sheet = $this->selectSheet($workbook, $config);

        if ($sheet === []) {
            throw new RuntimeException('El archivo Excel no contiene filas legibles.');
        }

        $detectedIndex = $this->detectHeaderRowIndex($sheet, $config['markers'] ?? []);
        $detectedHeaders = $detectedIndex === null ? [] : $this->extractHeaders($sheet[$detectedIndex] ?? []);
        $expectedHeaders = array_values($config['headers'] ?? []);

        $normalizedDetected = [];
        foreach ($detectedHeaders as $header) {
            $normalizedDetected[$this->normalizeHeader($header)] = $header;
        }

        $missing = [];
        foreach ($expectedHeaders as $header) {
            if (! array_key_exists($this->normalizeHeader($header), $normalizedDetected)) {
                $missing[] = $header;
            }
        }

        $expectedLookup = [];
        foreach ($expectedHeaders as $header) {
            $expectedLookup[$this->normalizeHeader($header)] = true;
        }

        $extras = [];
        foreach ($detectedHeaders as $header) {
            if (! isset($expectedLookup[$this->normalizeHeader($header)])) {
                $extras[] = $header;
            }
        }

        return [
            'header_row' => $detectedIndex === null ? null : $detectedIndex + 1,
            'total_rows' => count($sheet),
            'detected_headers' => $detectedHeaders,
            'expected_headers' => $expectedHeaders,
            'missing_headers' => $missing,
            'extra_headers' => $extras,
            'is_ready' => $detectedIndex !== null && $missing === [],
            'sample_rows' => $this->sampleRows($sheet, $detectedIndex),
        ];
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readWorkbook(string $filePath): array
    {
        $rawContents = File::get($filePath);
        if ($this->looksLikeHtml($rawContents)) {
            return [
                ['name' => 'HTML', 'rows' => $this->parseHtmlTable($rawContents)],
            ];
        }

        $sheets = Excel::toArray(new RawExcelArrayImport, $filePath);
        try {
            $names = Excel::sheetNames($filePath);
        } catch (\Throwable) {
            $names = [];
        }
        $out = [];

        foreach ($sheets as $index => $rows) {
            $out[] = [
                'name' => $names[$index] ?? 'Sheet '.($index + 1),
                'rows' => is_array($rows) ? $rows : [],
            ];
        }

        return $out;
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

    /**
     * @param  list<array{name:string,rows:array<int, array<int, mixed>>}>  $workbook
     * @param  array<string, mixed>  $config
     * @return array<int, array<int, mixed>>
     */
    private function selectSheet(array $workbook, array $config): array
    {
        $preferred = $this->normalizeHeader((string) ($config['preferred_sheet'] ?? ''));

        if ($preferred !== '') {
            foreach ($workbook as $sheet) {
                if ($this->normalizeHeader($sheet['name']) === $preferred) {
                    return $sheet['rows'];
                }
            }
        }

        $markers = $config['markers'] ?? [];
        foreach ($workbook as $sheet) {
            if ($this->detectHeaderRowIndex($sheet['rows'], $markers) !== null) {
                return $sheet['rows'];
            }
        }

        return $workbook[0]['rows'] ?? [];
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @param  list<string>  $markers
     */
    private function detectHeaderRowIndex(array $sheet, array $markers): ?int
    {
        $normalizedMarkers = array_map(fn (string $marker): string => $this->normalizeHeader($marker), $markers);

        foreach ($sheet as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalizedRow = array_map(fn ($cell): string => $this->normalizeHeader((string) $cell), $row);
            if ($normalizedMarkers === []) {
                return $index;
            }

            if (count(array_intersect($normalizedMarkers, $normalizedRow)) === count($normalizedMarkers)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $row
     * @return list<string>
     */
    private function extractHeaders(array $row): array
    {
        $headers = [];

        foreach ($row as $cell) {
            $value = trim((string) $cell);
            if ($value !== '') {
                $headers[] = $value;
            }
        }

        return $headers;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @return list<array<int, string>>
     */
    private function sampleRows(array $sheet, ?int $headerIndex): array
    {
        $start = $headerIndex === null ? 0 : $headerIndex + 1;
        $rows = [];

        foreach (array_slice($sheet, $start, 3) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $rows[] = array_map(fn ($value): string => trim((string) $value), $row);
        }

        return $rows;
    }

    private function normalizeHeader(string $value): string
    {
        $value = $this->repairEncoding(trim($value));
        $value = Str::ascii($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace(['.', '"', '_'], ['','',' '], $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return Str::lower(trim($value));
    }

    private function repairEncoding(string $value): string
    {
        if (! preg_match('/Ãƒ|Ã‚|Ã¢/u', $value)) {
            return $value;
        }

        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);

        return is_string($converted) && $converted !== '' ? $converted : $value;
    }
}
