<?php

namespace App\Services\Imports;

use App\Models\Import;
use App\Models\ImportRow;
use App\Support\Imports\ImportType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportManagerService
{
    public function __construct(
        private readonly ExcelSociosReader $sociosReader,
        private readonly ExcelDeudasReader $deudasReader,
        private readonly ExcelVendedorColumnReader $vendedorReader,
        private readonly LegacySociosActivosOrchestrator $sociosActivosOrchestrator,
        private readonly LegacyClientImportService $clientImport,
        private readonly LegacyMembershipImportService $membershipImport,
        private readonly LegacyDebtImportService $debtImport,
        private readonly UserImportService $userImport,
        private readonly ClientesAgrupadosImportService $clientesAgrupadosImport,
    ) {}

    /**
     * Guarda archivo, crea registro Import y ejecuta solo validación (sin persistir negocio).
     *
     * @param  array{duplicate_mode?: string, stop_on_error?: bool}  $options
     * @return array{import: Import, result: array<string, mixed>}
     */
    public function preview(
        UploadedFile $file,
        string $tipo,
        int $sucursalId,
        array $options = []
    ): array {
        $stored = $this->storeUploadedFile($file);
        $userId = Auth::id() ?? 0;

        $import = Import::query()->create([
            'tipo_importacion' => $tipo,
            'archivo_nombre' => $stored['original'],
            'archivo_path' => $stored['relative'],
            'sucursal_id' => $sucursalId,
            'estado' => 'preview',
            'opciones' => $options,
            'imported_by' => $userId ?: null,
            'started_at' => now(),
        ]);

        $result = $this->dispatchImport($import, false, $options);

        $this->finalizeImportRecord($import, $result, false);
        $this->persistImportRows($import, $result['row_results'] ?? []);

        return ['import' => $import->fresh(), 'result' => $result];
    }

    /**
     * Ejecuta importación real sobre un Import en preview.
     *
     * @param  array{duplicate_mode?: string, stop_on_error?: bool}  $options
     * @return array{import: Import, result: array<string, mixed>}
     */
    public function commit(Import $import, array $options = []): array
    {
        $merged = array_merge($import->opciones ?? [], $options);
        $import->opciones = $merged;
        $import->started_at = now();
        $import->save();

        $result = $this->dispatchImport($import, true, $merged);

        $import->estado = 'completed';
        $this->finalizeImportRecord($import, $result, true);
        $import->finished_at = now();
        $import->save();

        ImportRow::query()->where('import_id', $import->id)->delete();
        $this->persistImportRows($import, $result['row_results'] ?? []);

        return ['import' => $import->fresh(), 'result' => $result];
    }

    /**
     * @param  array{duplicate_mode?: string, stop_on_error?: bool}  $options
     * @return array<string, mixed>
     */
    private function dispatchImport(Import $import, bool $execute, array $options): array
    {
        $this->applyImportTimeLimit();

        $path = Storage::disk('local')->path($import->archivo_path);
        $userId = Auth::id() ?? 0;
        $sucursalId = (int) $import->sucursal_id;
        $duplicateMode = $options['duplicate_mode'] ?? 'crear_o_actualizar';
        $stopOnError = (bool) ($options['stop_on_error'] ?? false);

        return match ($import->tipo_importacion) {
            ImportType::SOCIOS_ACTIVOS_INTEGRAL => $this->sociosActivosOrchestrator->process(
                $path,
                $sucursalId,
                $userId,
                $execute,
                $options
            ),
            ImportType::CLIENTES => $this->clientImport->process(
                $this->sociosReader->read($path),
                $sucursalId,
                $userId,
                $execute,
                $duplicateMode,
                $stopOnError
            ),
            ImportType::MEMBRESIAS_MATRICULAS => $this->membershipImport->process(
                $this->sociosReader->read($path),
                $sucursalId,
                $userId,
                $execute,
                $stopOnError
            ),
            ImportType::DEUDAS => $this->debtImport->process(
                $this->deudasReader->read($path),
                $sucursalId,
                $userId,
                $execute,
                $stopOnError
            ),
            ImportType::USUARIOS => $this->userImport->process(
                $this->vendedorReader->read($path),
                $sucursalId,
                $userId,
                $execute,
                $stopOnError
            ),
            ImportType::CLIENTES_AGRUPADOS => $this->clientesAgrupadosImport->process(
                $path,
                $sucursalId,
                $userId,
                $execute,
                $stopOnError
            ),
            default => throw new \InvalidArgumentException('Tipo no soportado: '.$import->tipo_importacion),
        };
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function finalizeImportRecord(Import $import, array $result, bool $isCommit): void
    {
        $summary = $result['summary'] ?? [];
        if (isset($result['phase_summaries']) && is_array($result['phase_summaries'])) {
            $import->opciones = array_merge($import->opciones ?? [], [
                'phase_summaries' => $result['phase_summaries'],
            ]);
        }
        $import->total_filas = (int) ($summary['total'] ?? 0);
        $import->filas_validas = (int) ($summary['validas'] ?? 0);
        $import->filas_error = (int) ($summary['errores'] ?? 0);
        $imported = (int) ($summary['importadas'] ?? 0);
        $updated = (int) ($summary['actualizadas'] ?? 0);
        $import->filas_importadas = $isCommit ? $imported + $updated : 0;
        $import->save();
    }

    /**
     * @param  list<array<string, mixed>>  $rowResults
     */
    private function persistImportRows(Import $import, array $rowResults): void
    {
        if ($rowResults === []) {
            return;
        }

        $chunkSize = max(1, (int) config('importacion.import_rows_chunk_size', 250));
        $now = now();

        foreach (array_chunk($rowResults, $chunkSize) as $chunk) {
            $rows = [];
            foreach ($chunk as $row) {
                $errores = is_array($row['errores'] ?? null) ? $row['errores'] : [];
                $rows[] = [
                    'import_id' => $import->id,
                    'fila_numero' => (int) ($row['fila'] ?? 0),
                    'estado' => (string) ($row['estado'] ?? 'pending'),
                    'data_json' => json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'errores_json' => json_encode($errores, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    'modelo_tipo' => null,
                    'modelo_id' => $row['modelo_id'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            ImportRow::query()->insert($rows);
        }
    }

    private function applyImportTimeLimit(): void
    {
        $seconds = (int) config('importacion.time_limit_seconds', 600);
        if ($seconds > 0) {
            set_time_limit($seconds);
        }
    }

    /**
     * @return array{relative: string, original: string}
     */
    public function storeUploadedFile(UploadedFile $file): array
    {
        $dir = 'imports/'.Str::uuid()->toString();
        $relative = $file->store($dir, 'local');

        return [
            'relative' => $relative,
            'original' => $file->getClientOriginalName(),
        ];
    }
}
