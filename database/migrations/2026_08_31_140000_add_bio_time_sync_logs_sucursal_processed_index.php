<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bio_time_sync_logs')) {
            return;
        }

        if (! $this->indexExists('bio_time_sync_logs', 'bio_time_sync_logs_sucursal_processed_index')) {
            Schema::table('bio_time_sync_logs', function (Blueprint $table): void {
                $table->index(['sucursal_id', 'processed_at'], 'bio_time_sync_logs_sucursal_processed_index');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('bio_time_sync_logs')) {
            return;
        }

        if ($this->indexExists('bio_time_sync_logs', 'bio_time_sync_logs_sucursal_processed_index')) {
            Schema::table('bio_time_sync_logs', function (Blueprint $table): void {
                $table->dropIndex('bio_time_sync_logs_sucursal_processed_index');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn (array $index) => ($index['name'] ?? '') === $indexName);
        } catch (\Throwable) {
            return false;
        }
    }
};
