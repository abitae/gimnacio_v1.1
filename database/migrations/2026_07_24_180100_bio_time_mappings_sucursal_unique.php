<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bio_time_mappings')) {
            return;
        }

        foreach ($this->uniqueIndexesToReplace('bio_time_mappings') as $indexName) {
            Schema::table('bio_time_mappings', function (Blueprint $table) use ($indexName) {
                try {
                    $table->dropUnique($indexName);
                } catch (\Throwable) {
                }
            });
        }

        if (! $this->indexExists('bio_time_mappings', 'bio_time_mappings_sucursal_type_biotime_unique')) {
            Schema::table('bio_time_mappings', function (Blueprint $table) {
                $table->unique(['sucursal_id', 'mapping_type', 'biotime_id'], 'bio_time_mappings_sucursal_type_biotime_unique');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bio_time_mappings')) {
            return;
        }

        Schema::table('bio_time_mappings', function (Blueprint $table) {
            try {
                $table->dropUnique('bio_time_mappings_sucursal_type_biotime_unique');
            } catch (\Throwable) {
            }
        });
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
    protected function uniqueIndexesToReplace(string $table): array
    {
        try {
            return collect(Schema::getIndexes($table))
                ->filter(fn (array $index) => ($index['unique'] ?? false) === true)
                ->filter(function (array $index) {
                    $columns = $index['columns'] ?? [];

                    return in_array('mapping_type', $columns, true)
                        && in_array('biotime_id', $columns, true)
                        && ! in_array('sucursal_id', $columns, true);
                })
                ->pluck('name')
                ->filter()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
};
