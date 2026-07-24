<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, string> */
    protected array $catalogTables = [
        'productos' => 'productos_sucursal_codigo_unique',
        'servicios_externos' => 'servicios_externos_sucursal_codigo_unique',
        'membresias' => 'membresias_sucursal_codigo_unique',
    ];

    public function up(): void
    {
        foreach ($this->catalogTables as $table => $compositeIndex) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sucursal_id') || ! Schema::hasColumn($table, 'codigo')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $compositeIndex) {
                foreach ($this->uniqueIndexesOnColumn($table, 'codigo') as $indexName) {
                    try {
                        $blueprint->dropUnique($indexName);
                    } catch (\Throwable) {
                    }
                }

                if (! $this->indexExists($table, $compositeIndex)) {
                    $blueprint->unique(['sucursal_id', 'codigo'], $compositeIndex);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->catalogTables as $table => $compositeIndex) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table, $compositeIndex) {
                try {
                    $blueprint->dropUnique($compositeIndex);
                } catch (\Throwable) {
                }

                if (! $this->indexExists($table, "{$table}_codigo_unique")) {
                    try {
                        $blueprint->unique('codigo', "{$table}_codigo_unique");
                    } catch (\Throwable) {
                    }
                }
            });
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn (array $index) => ($index['name'] ?? '') === $indexName);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function uniqueIndexesOnColumn(string $table, string $column): array
    {
        try {
            return collect(Schema::getIndexes($table))
                ->filter(fn (array $index) => ($index['unique'] ?? false) === true)
                ->filter(fn (array $index) => in_array($column, $index['columns'] ?? [], true))
                ->pluck('name')
                ->filter(fn (?string $name) => $name !== null && $name !== 'PRIMARY')
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
};
