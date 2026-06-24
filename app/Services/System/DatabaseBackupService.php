<?php

namespace App\Services\System;

use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

class DatabaseBackupService
{
    private const RESTORE_READ_CHUNK_BYTES = 1024 * 1024;

    private const LARGE_RESTORE_THRESHOLD_BYTES = 5 * 1024 * 1024;

    private const MANIFEST_FORMAT_VERSION = 1;

    public function createBackup(): array
    {
        $directory = $this->backupDirectory();
        File::ensureDirectoryExists($directory);

        $backupId = 'backup_'.now()->format('Ymd_His');
        $stagingDirectory = $this->stagingDirectory($backupId);
        File::ensureDirectoryExists($stagingDirectory);
        $writer = new BackupPartWriter($stagingDirectory, $backupId);

        try {
            $writer->writeBlock($this->dumpHeader(), 'cabecera SQL');

            foreach ($this->databaseObjects() as $object) {
                $writer->writeBlock(
                    $this->dumpTableStructure($object['name'], $object['create_sql']),
                    'estructura de tabla '.$object['name']
                );

                foreach ($this->dumpTableDataStatements($object['name']) as $statement) {
                    $writer->writeBlock($statement, 'datos de tabla '.$object['name']);
                }
            }

            $manifestPath = $writer->finalize([
                'backup_id' => $backupId,
                'created_at' => now()->toDateTimeString(),
                'driver' => DB::connection()->getDriverName(),
                'database' => DB::connection()->getDatabaseName(),
                'format_version' => self::MANIFEST_FORMAT_VERSION,
                'max_part_size_bytes' => null,
            ]);

            $zipPath = $this->createBackupArchive($backupId, $manifestPath, $writer->partPaths());
        } catch (\Throwable $e) {
            $writer->cleanup();
            File::deleteDirectory($stagingDirectory);
            throw $e;
        }

        File::deleteDirectory($stagingDirectory);

        return $this->archiveMetadata($zipPath);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listBackups(): array
    {
        $directory = $this->backupDirectory();
        File::ensureDirectoryExists($directory);

        $archiveBackups = collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file) => $this->isBackupArchiveFilename($file->getFilename()))
            ->map(function (\SplFileInfo $file): ?array {
                try {
                    return $this->archiveMetadata($file->getRealPath());
                } catch (\Throwable) {
                    return null;
                }
            })
            ->filter()
            ->values();

        $legacyBackups = collect(File::files($directory))
            ->filter(function (\SplFileInfo $file): bool {
                if ($file->getExtension() !== 'sql') {
                    return false;
                }

                return ! preg_match('/\.part\d{3}\.sql$/i', $file->getFilename());
            })
            ->map(fn (\SplFileInfo $file) => $this->legacyBackupMetadata($file->getRealPath()))
            ->values();

        return $archiveBackups
            ->merge($legacyBackups)
            ->sortByDesc(function (array $backup): int {
                $createdAt = strtotime((string) ($backup['created_at'] ?? '')) ?: 0;

                return $createdAt;
            })
            ->values()
            ->all();
    }

    public function absolutePathFor(string $filename): string
    {
        $path = $this->backupDirectory().DIRECTORY_SEPARATOR.basename($filename);

        if (! File::exists($path)) {
            throw new RuntimeException('El backup solicitado no existe.');
        }

        return $path;
    }

    public function deleteBackup(string $filename): void
    {
        $path = $this->absolutePathFor($filename);

        File::delete($path);
    }

    public function restoreFromUploadedFile(UploadedFile $file): void
    {
        $originalName = $file->getClientOriginalName();

        if ($this->isBackupArchiveFilename($originalName)) {
            $tempPath = $this->backupDirectory().DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.uniqid('restore_zip_', true).'.sql';
            File::ensureDirectoryExists(dirname($tempPath));

            try {
                $this->materializeArchiveToSql($file->getRealPath(), $tempPath);
                $this->restoreFromPath($tempPath);
            } finally {
                File::delete($tempPath);
            }

            return;
        }

        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['sql', 'txt'], true)) {
            throw new RuntimeException('Solo se permiten archivos .zip, .sql o .txt.');
        }

        $absolutePath = $this->backupDirectory().DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.uniqid('restore_', true).'.sql';
        File::ensureDirectoryExists(dirname($absolutePath));
        File::copy($file->getRealPath(), $absolutePath);

        try {
            $this->restoreFromPath($absolutePath);
        } finally {
            File::delete($absolutePath);
        }
    }

    public function restoreFromArchivePath(string $archivePath, ?callable $progressCallback = null): void
    {
        $tempPath = $this->backupDirectory().DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.uniqid('restore_zip_', true).'.sql';
        File::ensureDirectoryExists(dirname($tempPath));

        try {
            $this->materializeArchiveToSql($archivePath, $tempPath);
            $this->restoreFromPath($tempPath, $progressCallback);
        } finally {
            File::delete($tempPath);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function materializeArchiveToSql(string $archivePath, string $targetPath): array
    {
        $manifest = $this->readManifestFromArchivePath($archivePath);
        File::ensureDirectoryExists(dirname($targetPath));

        $handle = fopen($targetPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo crear el archivo temporal para la restauracion.');
        }

        $zip = $this->openArchive($archivePath);

        try {
            foreach ($manifest['parts'] as $index => $part) {
                $partFilename = (string) ($part['filename'] ?? '');
                $zipEntry = 'parts/'.$partFilename;

                if ($partFilename === '' || $zip->locateName($zipEntry) === false) {
                    throw new RuntimeException('Falta la parte '.($index + 1).' del backup: '.$partFilename);
                }

                $expectedChecksum = strtolower((string) ($part['checksum'] ?? ''));
                $contents = $zip->getFromName($zipEntry);
                if ($contents === false) {
                    throw new RuntimeException('No se pudo leer la parte '.$partFilename.' del backup.');
                }
                $actualChecksum = strtolower(hash('sha256', $contents));
                if ($expectedChecksum === '' || $expectedChecksum !== $actualChecksum) {
                    throw new RuntimeException('La integridad de la parte '.$partFilename.' no es valida.');
                }

                $partHandle = $zip->getStream($zipEntry);
                if ($partHandle === false) {
                    throw new RuntimeException('No se pudo abrir la parte '.$partFilename.' del backup.');
                }

                try {
                    while (! feof($partHandle)) {
                        $chunk = fread($partHandle, 8192);
                        if ($chunk === false) {
                            throw new RuntimeException('No se pudo leer la parte '.$partFilename.' del backup.');
                        }

                        if ($chunk === '') {
                            continue;
                        }

                        if (fwrite($handle, $chunk) === false) {
                            throw new RuntimeException('No se pudo ensamblar el backup temporal para restaurar.');
                        }
                    }
                } finally {
                    fclose($partHandle);
                }
            }
        } catch (\Throwable $e) {
            $zip->close();
            fclose($handle);
            File::delete($targetPath);
            throw $e;
        }

        $zip->close();
        fclose($handle);

        return [
            'backup_id' => $manifest['backup_id'],
            'manifest_filename' => basename($archivePath),
            'part_count' => (int) $manifest['part_count'],
            'total_size_bytes' => (int) ($manifest['total_size_bytes'] ?? 0),
        ];
    }

    public function isBackupArchiveFilename(string $filename): bool
    {
        return str_ends_with(strtolower($filename), '.zip');
    }

    /**
     * @return array<string, mixed>
     */
    public function readManifest(string $filename): array
    {
        return $this->readManifestFromArchivePath($this->absolutePathFor($filename));
    }

    /**
     * @return array<string, mixed>
     */
    public function readManifestFromArchivePath(string $path): array
    {
        if (! File::exists($path)) {
            throw new RuntimeException('El backup ZIP solicitado no existe.');
        }

        $zip = $this->openArchive($path);
        $manifestContents = $zip->getFromName('manifest.json');
        $zip->close();

        if ($manifestContents === false) {
            throw new RuntimeException('El backup ZIP no contiene manifest.json.');
        }

        $decoded = json_decode($manifestContents, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('El manifest del backup no es un JSON valido.');
        }

        $requiredKeys = ['backup_id', 'created_at', 'driver', 'database', 'part_count', 'max_part_size_bytes', 'parts', 'format_version'];
        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $decoded)) {
                throw new RuntimeException('El manifest del backup no contiene el campo requerido: '.$key);
            }
        }

        if (! is_array($decoded['parts']) || $decoded['parts'] === []) {
            throw new RuntimeException('El manifest del backup no contiene partes validas.');
        }

        $partCount = (int) $decoded['part_count'];
        if ($partCount !== count($decoded['parts'])) {
            throw new RuntimeException('El manifest del backup tiene un conteo de partes inconsistente.');
        }

        foreach ($decoded['parts'] as $index => $part) {
            if (
                ! is_array($part)
                || empty($part['filename'])
                || ! isset($part['size_bytes'])
                || empty($part['checksum'])
            ) {
                throw new RuntimeException('La parte '.($index + 1).' del manifest es invalida.');
            }
        }

        return $decoded;
    }

    public function restoreFromPath(string $path, ?callable $progressCallback = null): void
    {
        if (! File::exists($path)) {
            throw new RuntimeException('No se encontro el archivo SQL a restaurar.');
        }

        $fileSize = (int) File::size($path);
        if ($fileSize <= 0 || ! $this->fileHasMeaningfulContent($path)) {
            throw new RuntimeException('El archivo SQL esta vacio.');
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $totalParts = max(1, (int) ceil($fileSize / self::RESTORE_READ_CHUNK_BYTES));
        $isLargeRestore = $fileSize >= self::LARGE_RESTORE_THRESHOLD_BYTES;
        $totalStatements = $isLargeRestore ? null : $this->countStatementsInFile($path);

        $notify = function (
            string $step,
            int $executed,
            ?string $command = null,
            ?string $log = null,
            ?int $currentPart = null,
            ?string $stage = null
        ) use ($progressCallback, $totalStatements, $totalParts, $isLargeRestore, $fileSize): void {
            if (! $progressCallback) {
                return;
            }

            $progressValue = $totalStatements && $totalStatements > 0
                ? (int) floor(($executed / max(1, $totalStatements)) * 100)
                : ($currentPart !== null ? min(99, (int) floor(($currentPart / max(1, $totalParts)) * 100)) : 0);

            $progressCallback([
                'current_step' => $step,
                'executed_statements' => $executed,
                'total_statements' => $totalStatements,
                'progress' => $progressValue,
                'current_command' => $command,
                'log' => $log,
                'current_part' => $currentPart ?? 1,
                'total_parts' => $totalParts,
                'is_large_restore' => $isLargeRestore,
                'file_size_bytes' => $fileSize,
                'stage' => $stage ?? 'executing_sql',
                'statement_index' => $executed,
            ]);
        };

        if ($driver === 'mysql') {
            $connection->unprepared('SET FOREIGN_KEY_CHECKS=0');
            $notify('Deshabilitando llaves foraneas', 0, 'SET FOREIGN_KEY_CHECKS=0;', 'SET FOREIGN_KEY_CHECKS=0;', 1, 'disabling_foreign_keys');
        } elseif ($driver === 'sqlite') {
            $connection->unprepared('PRAGMA foreign_keys = OFF');
            $notify('Deshabilitando llaves foraneas', 0, 'PRAGMA foreign_keys = OFF;', 'PRAGMA foreign_keys = OFF;', 1, 'disabling_foreign_keys');
        }

        $lastExecuted = 0;

        try {
            foreach ($this->statementStream($path) as $index => $statementData) {
                $trimmed = $statementData['statement'];
                $currentPart = $statementData['part'];
                $executed = $index + 1;
                $lastExecuted = $executed;
                $step = $isLargeRestore
                    ? 'Ejecutando parte '.$currentPart.' de '.$totalParts.' | sentencia '.$executed.' de '.($totalStatements ?? '?')
                    : 'Ejecutando sentencia '.$executed.' de '.$totalStatements;

                $notify(
                    $step,
                    $index,
                    $this->truncateCommand($trimmed),
                    $this->truncateCommand($trimmed),
                    $currentPart,
                    'executing_sql'
                );
                $connection->unprepared($trimmed);
                $notify(
                    $step,
                    $executed,
                    $this->truncateCommand($trimmed),
                    null,
                    $currentPart,
                    'executing_sql'
                );
            }
        } finally {
            if ($driver === 'mysql') {
                $connection->unprepared('SET FOREIGN_KEY_CHECKS=1');
                $notify('Rehabilitando llaves foraneas', $totalStatements ?? $lastExecuted, 'SET FOREIGN_KEY_CHECKS=1;', 'SET FOREIGN_KEY_CHECKS=1;', $totalParts, 'enabling_foreign_keys');
            } elseif ($driver === 'sqlite') {
                $connection->unprepared('PRAGMA foreign_keys = ON');
                $notify('Rehabilitando llaves foraneas', $totalStatements ?? $lastExecuted, 'PRAGMA foreign_keys = ON;', 'PRAGMA foreign_keys = ON;', $totalParts, 'enabling_foreign_keys');
            }
        }
    }

    private function backupDirectory(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'backups');
    }

    private function dumpHeader(): string
    {
        $driver = DB::connection()->getDriverName();
        $database = DB::connection()->getDatabaseName();

        return implode(PHP_EOL, [
            '-- Backup completo generado por FitCenter OS',
            '-- Incluye todas las tablas y todos los registros de la base de datos',
            '-- Fecha: '.now()->toDateTimeString(),
            '-- Driver: '.$driver,
            '-- Base de datos: '.$database,
            '',
            $driver === 'mysql' ? 'SET FOREIGN_KEY_CHECKS=0;' : 'PRAGMA foreign_keys = OFF;',
            '',
        ]);
    }

    /**
     * @return list<array{name:string,create_sql:string}>
     */
    private function databaseObjects(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $database = DB::connection()->getDatabaseName();
            $rows = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
            $key = 'Tables_in_'.$database;

            return collect($rows)
                ->map(function (object $row) use ($key): array {
                    $table = $row->{$key};
                    $createRow = DB::selectOne('SHOW CREATE TABLE '.$this->wrapIdentifier($table));

                    return [
                        'name' => $table,
                        'create_sql' => $createRow->{'Create Table'},
                    ];
                })
                ->all();
        }

        if ($driver === 'sqlite') {
            return collect(DB::select("
                SELECT name, sql
                FROM sqlite_master
                WHERE type = 'table'
                  AND name NOT LIKE 'sqlite_%'
                ORDER BY name
            "))
                ->map(fn (object $row) => [
                    'name' => $row->name,
                    'create_sql' => $row->sql,
                ])
                ->all();
        }

        throw new RuntimeException("Driver de base de datos no soportado para backup: {$driver}");
    }

    private function dumpTableStructure(string $table, string $createSql): string
    {
        return implode(PHP_EOL, [
            '',
            '-- --------------------------------------------------------',
            '-- Estructura de tabla para '.$table,
            '-- --------------------------------------------------------',
            'DROP TABLE IF EXISTS '.$this->wrapIdentifier($table).';',
            rtrim($createSql, ';').';',
            '',
        ]);
    }

    /**
     * @return \Generator<int, string>
     */
    private function dumpTableDataStatements(string $table): \Generator
    {
        $columns = Schema::getColumnListing($table);
        if ($columns === []) {
            return;
        }

        $wrappedColumns = implode(', ', array_map(fn (string $column) => $this->wrapIdentifier($column), $columns));
        $pdo = DB::connection()->getPdo();

        foreach (DB::table($table)->cursor() as $row) {
            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->sqlLiteral($row->{$column} ?? null, $pdo);
            }

            yield 'INSERT INTO '.$this->wrapIdentifier($table).' ('.$wrappedColumns.') VALUES ('.implode(', ', $values).');'.PHP_EOL;
        }

        yield PHP_EOL;
    }

    private function sqlLiteral(mixed $value, \PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $pdo->quote($value->format('Y-m-d H:i:s'));
        }

        if (is_resource($value)) {
            $value = stream_get_contents($value);
        }

        return $pdo->quote((string) $value);
    }

    private function wrapIdentifier(string $identifier): string
    {
        $driver = DB::connection()->getDriverName();

        return $driver === 'mysql'
            ? '`'.str_replace('`', '``', $identifier).'`'
            : '"'.str_replace('"', '""', $identifier).'"';
    }

    private function countStatementsInFile(string $path): int
    {
        $count = 0;

        foreach ($this->statementStream($path) as $statementData) {
            if (($statementData['statement'] ?? '') !== '') {
                $count++;
            }
        }

        return $count;
    }

    private function fileHasMeaningfulContent(string $path): bool
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo SQL para validarlo.');
        }

        try {
            while (! feof($handle)) {
                $chunk = fread($handle, 8192);
                if ($chunk === false) {
                    throw new RuntimeException('No se pudo leer el archivo SQL para validarlo.');
                }

                if (trim($chunk) !== '') {
                    return true;
                }
            }

            return false;
        } finally {
            fclose($handle);
        }
    }

    private function sanitizeStatementForRestore(string $statement): string
    {
        $statement = preg_replace('/^\xEF\xBB\xBF/', '', $statement) ?? $statement;

        // Descarta comentarios iniciales del dump y comandos de contexto que la
        // conexion actual no necesita o no puede ejecutar en entornos gestionados.
        $statement = preg_replace('/\A\s*(?:--[^\r\n]*(?:\r?\n|$)|#[^\r\n]*(?:\r?\n|$)|\/\*.*?\*\/\s*)+/s', '', $statement) ?? $statement;
        $statement = trim($statement);

        if ($statement === '') {
            return '';
        }

        if (preg_match('/^use\s+/i', $statement) === 1) {
            return '';
        }

        if (preg_match('/^set\s+foreign_key_checks\s*=\s*[01]\s*;?$/i', $statement) === 1) {
            return '';
        }

        if (preg_match('/^pragma\s+foreign_keys\s*=\s*(off|on)\s*;?$/i', $statement) === 1) {
            return '';
        }

        return $statement;
    }

    /**
     * @return \Generator<int, array{statement:string,part:int}>
     */
    private function statementStream(string $path): \Generator
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el archivo SQL para restaurar.');
        }

        $buffer = '';
        $inSingle = false;
        $inDouble = false;
        $escape = false;
        $currentPart = 1;
        $statementPart = 1;

        try {
            while (! feof($handle)) {
                $chunk = fread($handle, self::RESTORE_READ_CHUNK_BYTES);
                if ($chunk === false) {
                    throw new RuntimeException('No se pudo leer el archivo SQL durante la restauracion.');
                }

                if ($chunk === '') {
                    continue;
                }

                $statementPart = $currentPart;
                $length = strlen($chunk);

                for ($i = 0; $i < $length; $i++) {
                    $char = $chunk[$i];
                    $buffer .= $char;

                    if ($escape) {
                        $escape = false;

                        continue;
                    }

                    if ($char === '\\') {
                        $escape = true;

                        continue;
                    }

                    if ($char === "'" && ! $inDouble) {
                        $inSingle = ! $inSingle;

                        continue;
                    }

                    if ($char === '"' && ! $inSingle) {
                        $inDouble = ! $inDouble;

                        continue;
                    }

                    if ($char === ';' && ! $inSingle && ! $inDouble) {
                        $statement = $this->sanitizeStatementForRestore(trim($buffer));
                        if ($statement !== '') {
                            yield [
                                'statement' => $statement,
                                'part' => $statementPart,
                            ];
                        }
                        $buffer = '';
                        $statementPart = $currentPart;
                    }
                }

                $currentPart++;
            }

            $statement = $this->sanitizeStatementForRestore(trim($buffer));
            if ($statement !== '') {
                yield [
                    'statement' => $statement,
                    'part' => max(1, $statementPart),
                ];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function archiveMetadata(string $path): array
    {
        $manifest = $this->readManifestFromArchivePath($path);
        $totalSizeBytes = (int) ($manifest['total_size_bytes'] ?? collect($manifest['parts'])->sum('size_bytes'));
        $parts = collect($manifest['parts'])
            ->map(fn (array $part) => [
                'filename' => $part['filename'],
                'size_bytes' => (int) $part['size_bytes'],
                'size_human' => $this->formatBytes((int) $part['size_bytes']),
                'checksum' => $part['checksum'],
            ])
            ->values()
            ->all();

        return [
            'storage_type' => 'zip_bundle',
            'filename' => basename($path),
            'manifest_filename' => 'manifest.json',
            'path' => $path,
            'backup_id' => $manifest['backup_id'],
            'display_name' => $manifest['backup_id'],
            'part_count' => (int) $manifest['part_count'],
            'parts' => $parts,
            'size_bytes' => $totalSizeBytes,
            'size_human' => $this->formatBytes($totalSizeBytes),
            'total_size_bytes' => $totalSizeBytes,
            'total_size_human' => $this->formatBytes($totalSizeBytes),
            'created_at' => (string) $manifest['created_at'],
            'driver' => (string) $manifest['driver'],
            'database' => (string) $manifest['database'],
            'format_version' => (int) $manifest['format_version'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyBackupMetadata(string $path): array
    {
        $size = File::size($path);
        $createdTimestamp = @filectime($path);
        if (! is_int($createdTimestamp) || $createdTimestamp <= 0) {
            $createdTimestamp = File::lastModified($path);
        }

        return [
            'storage_type' => 'legacy_sql',
            'filename' => basename($path),
            'manifest_filename' => null,
            'path' => $path,
            'backup_id' => basename($path, '.sql'),
            'display_name' => basename($path),
            'part_count' => 1,
            'parts' => [[
                'filename' => basename($path),
                'size_bytes' => $size,
                'size_human' => $this->formatBytes($size),
                'checksum' => strtolower(hash_file('sha256', $path)),
            ]],
            'size_bytes' => $size,
            'size_human' => $this->formatBytes($size),
            'total_size_bytes' => $size,
            'total_size_human' => $this->formatBytes($size),
            'created_at' => date('Y-m-d H:i:s', $createdTimestamp),
            'driver' => DB::connection()->getDriverName(),
            'database' => DB::connection()->getDatabaseName(),
            'format_version' => 0,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KB', 'MB', 'GB'];
        $value = $bytes / 1024;
        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'GB') {
                return number_format($value, 2).' '.$unit;
            }

            $value /= 1024;
        }

        return number_format($value, 2).' GB';
    }

    private function truncateCommand(string $sql): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($sql)) ?? trim($sql);

        return mb_strlen($normalized) > 220
            ? mb_substr($normalized, 0, 220).'...'
            : $normalized;
    }

    private function stagingDirectory(string $backupId): string
    {
        return $this->backupDirectory().DIRECTORY_SEPARATOR.'tmp'.DIRECTORY_SEPARATOR.$backupId;
    }

    /**
     * @param  list<string>  $partPaths
     */
    private function createBackupArchive(string $backupId, string $manifestPath, array $partPaths): string
    {
        $zipPath = $this->backupDirectory().DIRECTORY_SEPARATOR.$backupId.'.zip';
        $zip = new ZipArchive;

        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            throw new RuntimeException('No se pudo crear el archivo ZIP del backup.');
        }

        if (! $zip->addFile($manifestPath, 'manifest.json')) {
            $zip->close();
            throw new RuntimeException('No se pudo agregar el manifest al backup ZIP.');
        }

        foreach ($partPaths as $partPath) {
            if (! $zip->addFile($partPath, 'parts/'.basename($partPath))) {
                $zip->close();
                throw new RuntimeException('No se pudo agregar una parte SQL al backup ZIP.');
            }
        }

        $zip->close();

        return $zipPath;
    }

    private function openArchive(string $path): ZipArchive
    {
        $zip = new ZipArchive;
        $result = $zip->open($path);

        if ($result !== true) {
            throw new RuntimeException('No se pudo abrir el archivo ZIP del backup.');
        }

        return $zip;
    }
}

class BackupPartWriter
{
    private $handle = null;

    /** @var list<string> */
    private array $partPaths = [];

    public function __construct(
        private readonly string $directory,
        private readonly string $backupId,
    ) {}

    public function writeBlock(string $block, string $description = 'bloque SQL'): void
    {
        if ($block === '') {
            return;
        }

        if ($this->handle === null) {
            $this->openSinglePart();
        }

        $written = fwrite($this->handle, $block);
        if ($written === false || $written !== strlen($block)) {
            throw new RuntimeException('No se pudo escribir el contenido SQL del backup.');
        }
    }

    /**
     * @param  array<string, mixed>  $manifestData
     */
    public function finalize(array $manifestData): string
    {
        $this->closeHandle();

        if ($this->partPaths === []) {
            throw new RuntimeException('No se genero ningun contenido para el backup.');
        }

        $parts = collect($this->partPaths)
            ->map(function (string $path): array {
                return [
                    'filename' => basename($path),
                    'size_bytes' => File::size($path),
                    'checksum' => strtolower(hash_file('sha256', $path)),
                ];
            })
            ->values()
            ->all();

        $payload = $manifestData + [
            'part_count' => count($parts),
            'parts' => $parts,
            'total_size_bytes' => (int) collect($parts)->sum('size_bytes'),
            'bundle_checksum' => strtolower(hash('sha256', implode('|', array_map(
                fn (array $part) => $part['filename'].':'.$part['checksum'],
                $parts
            )))),
        ];

        $manifestPath = $this->directory.DIRECTORY_SEPARATOR.$this->backupId.'.backup.json';
        File::put($manifestPath, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $manifestPath;
    }

    public function cleanup(): void
    {
        $this->closeHandle();

        if ($this->partPaths !== []) {
            File::delete($this->partPaths);
        }

        $manifestPath = $this->directory.DIRECTORY_SEPARATOR.$this->backupId.'.backup.json';
        if (File::exists($manifestPath)) {
            File::delete($manifestPath);
        }
    }

    /**
     * @return list<string>
     */
    public function partPaths(): array
    {
        return $this->partPaths;
    }

    private function openSinglePart(): void
    {
        $this->closeHandle();

        $partPath = $this->directory.DIRECTORY_SEPARATOR.$this->backupId.'.sql';
        $handle = fopen($partPath, 'wb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo crear el archivo SQL del backup.');
        }

        $this->handle = $handle;
        $this->partPaths = [$partPath];
    }

    private function closeHandle(): void
    {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }

        $this->handle = null;
    }
}
