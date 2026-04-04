<?php

namespace App\Services\System;

use App\Models\User;
use App\Support\PermissionCatalog;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class DatabaseBackupService
{
    private const RESTORE_READ_CHUNK_BYTES = 1024 * 1024;

    private const LARGE_RESTORE_THRESHOLD_BYTES = 5 * 1024 * 1024;

    private ?array $excludedSuperAdminContext = null;

    public function createBackup(): array
    {
        $directory = $this->backupDirectory();
        File::ensureDirectoryExists($directory);

        $filename = 'backup_'.now()->format('Ymd_His').'.sql';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;

        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo crear el archivo de backup.');
        }

        try {
            fwrite($handle, $this->dumpHeader());

            foreach ($this->databaseObjects() as $object) {
                fwrite($handle, $this->dumpTableStructure($object['name'], $object['create_sql']));
                fwrite($handle, $this->dumpTableData($object['name']));
            }
        } finally {
            fclose($handle);
        }

        return $this->backupMetadata($path);
    }

    /**
     * @return list<array{filename:string,path:string,size_bytes:int,size_human:string,modified_at:string}>
     */
    public function listBackups(): array
    {
        $directory = $this->backupDirectory();
        File::ensureDirectoryExists($directory);

        $files = collect(File::files($directory))
            ->filter(fn (\SplFileInfo $file) => $file->getExtension() === 'sql')
            ->sortByDesc(fn (\SplFileInfo $file) => $file->getMTime())
            ->values();

        return $files
            ->map(fn (\SplFileInfo $file) => $this->backupMetadata($file->getRealPath()))
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
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['sql', 'txt'], true)) {
            throw new RuntimeException('Solo se permiten archivos .sql o .txt.');
        }

        $tempPath = $file->storeAs('backups/uploads', uniqid('restore_', true).'.sql');
        $absolutePath = storage_path('app'.DIRECTORY_SEPARATOR.$tempPath);

        try {
            $this->restoreFromPath($absolutePath);
        } finally {
            File::delete($absolutePath);
        }
    }

    public function restoreFromPath(string $path, ?callable $progressCallback = null): void
    {
        if (! File::exists($path)) {
            throw new RuntimeException('No se encontro el archivo SQL a restaurar.');
        }

        $fileSize = (int) File::size($path);
        if ($fileSize <= 0 || trim((string) File::get($path)) === '') {
            throw new RuntimeException('El archivo SQL esta vacio.');
        }

        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $totalStatements = $this->countStatementsInFile($path);
        $totalParts = max(1, (int) ceil($fileSize / self::RESTORE_READ_CHUNK_BYTES));
        $isLargeRestore = $fileSize >= self::LARGE_RESTORE_THRESHOLD_BYTES;

        $notify = function (
            string $step,
            int $executed,
            ?string $command = null,
            ?string $log = null,
            ?int $currentPart = null
        ) use ($progressCallback, $totalStatements, $totalParts, $isLargeRestore, $fileSize): void {
            if (! $progressCallback) {
                return;
            }

            $progressCallback([
                'current_step' => $step,
                'executed_statements' => $executed,
                'total_statements' => $totalStatements,
                'progress' => $totalStatements > 0 ? (int) floor(($executed / max(1, $totalStatements)) * 100) : 0,
                'current_command' => $command,
                'log' => $log,
                'current_part' => $currentPart ?? 1,
                'total_parts' => $totalParts,
                'is_large_restore' => $isLargeRestore,
                'file_size_bytes' => $fileSize,
            ]);
        };

        if ($driver === 'mysql') {
            $connection->unprepared('SET FOREIGN_KEY_CHECKS=0');
            $notify('Deshabilitando llaves foraneas', 0, 'SET FOREIGN_KEY_CHECKS=0;', 'SET FOREIGN_KEY_CHECKS=0;', 1);
        } elseif ($driver === 'sqlite') {
            $connection->unprepared('PRAGMA foreign_keys = OFF');
            $notify('Deshabilitando llaves foraneas', 0, 'PRAGMA foreign_keys = OFF;', 'PRAGMA foreign_keys = OFF;', 1);
        }

        try {
            foreach ($this->statementStream($path) as $index => $statementData) {
                $trimmed = $statementData['statement'];
                $currentPart = $statementData['part'];
                $executed = $index + 1;
                $step = $isLargeRestore
                    ? 'Ejecutando parte '.$currentPart.' de '.$totalParts.' | sentencia '.$executed.' de '.$totalStatements
                    : 'Ejecutando sentencia '.$executed.' de '.$totalStatements;

                $notify(
                    $step,
                    $index,
                    $this->truncateCommand($trimmed),
                    $this->truncateCommand($trimmed),
                    $currentPart
                );
                $connection->unprepared($trimmed);
                $notify(
                    $step,
                    $executed,
                    $this->truncateCommand($trimmed),
                    null,
                    $currentPart
                );
            }
        } finally {
            if ($driver === 'mysql') {
                $connection->unprepared('SET FOREIGN_KEY_CHECKS=1');
                $notify('Rehabilitando llaves foraneas', $totalStatements, 'SET FOREIGN_KEY_CHECKS=1;', 'SET FOREIGN_KEY_CHECKS=1;', $totalParts);
            } elseif ($driver === 'sqlite') {
                $connection->unprepared('PRAGMA foreign_keys = ON');
                $notify('Rehabilitando llaves foraneas', $totalStatements, 'PRAGMA foreign_keys = ON;', 'PRAGMA foreign_keys = ON;', $totalParts);
            }
        }
    }

    private function backupDirectory(): string
    {
        return storage_path('app'.DIRECTORY_SEPARATOR.'backups');
    }

    private function dumpHeader(): string
    {
        $driver = DB::connection()->getDriverName();
        $database = DB::connection()->getDatabaseName();

        return implode(PHP_EOL, [
            '-- Backup generado por FitCenter OS',
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

    private function dumpTableData(string $table): string
    {
        $columns = Schema::getColumnListing($table);
        if ($columns === []) {
            return '';
        }

        $wrappedColumns = implode(', ', array_map(fn (string $column) => $this->wrapIdentifier($column), $columns));
        $pdo = DB::connection()->getPdo();
        $sql = '';

        foreach (DB::table($table)->cursor() as $row) {
            if ($this->shouldSkipRow($table, (array) $row)) {
                continue;
            }

            $values = [];
            foreach ($columns as $column) {
                $values[] = $this->sqlLiteral($row->{$column} ?? null, $pdo);
            }

            $sql .= 'INSERT INTO '.$this->wrapIdentifier($table).' ('.$wrappedColumns.') VALUES ('.implode(', ', $values).');'.PHP_EOL;
        }

        return $sql.PHP_EOL;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function shouldSkipRow(string $table, array $row): bool
    {
        $context = $this->excludedSuperAdminContext();
        if ($context['ids'] === []) {
            return false;
        }

        return match ($table) {
            'users' => in_array((int) ($row['id'] ?? 0), $context['ids'], true),
            'model_has_roles', 'model_has_permissions' => ($row['model_type'] ?? null) === User::class
                && in_array((int) ($row['model_id'] ?? 0), $context['ids'], true),
            'personal_access_tokens' => ($row['tokenable_type'] ?? null) === User::class
                && in_array((int) ($row['tokenable_id'] ?? 0), $context['ids'], true),
            'sessions' => in_array((int) ($row['user_id'] ?? 0), $context['ids'], true),
            'password_reset_tokens' => in_array(strtolower((string) ($row['email'] ?? '')), $context['emails'], true),
            default => false,
        };
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

    /**
     * @return list<string>
     */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $length = strlen($sql);
        $inSingle = false;
        $inDouble = false;
        $escape = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
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
                $statements[] = $buffer;
                $buffer = '';
            }
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
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
                        $statement = trim($buffer);
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

            $statement = trim($buffer);
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
     * @return array{filename:string,path:string,size_bytes:int,size_human:string,created_at:string}
     */
    private function backupMetadata(string $path): array
    {
        $size = File::size($path);
        $createdTimestamp = @filectime($path);
        if (! is_int($createdTimestamp) || $createdTimestamp <= 0) {
            $createdTimestamp = File::lastModified($path);
        }

        return [
            'filename' => basename($path),
            'path' => $path,
            'size_bytes' => $size,
            'size_human' => $this->formatBytes($size),
            'created_at' => date('Y-m-d H:i:s', $createdTimestamp),
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

    /**
     * @return array{ids:list<int>,emails:list<string>}
     */
    private function excludedSuperAdminContext(): array
    {
        if ($this->excludedSuperAdminContext !== null) {
            return $this->excludedSuperAdminContext;
        }

        $users = User::query()
            ->role(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)
            ->get(['id', 'email']);

        $this->excludedSuperAdminContext = [
            'ids' => $users->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'emails' => $users->pluck('email')->filter()->map(fn ($email) => strtolower((string) $email))->values()->all(),
        ];

        return $this->excludedSuperAdminContext;
    }
}
