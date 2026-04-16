<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('venta_items') || Schema::hasColumn('venta_items', 'sucursal_id')) {
            return;
        }

        Schema::table('venta_items', function (Blueprint $table) {
            $table->foreignId('sucursal_id')
                ->nullable()
                ->after('subtotal')
                ->constrained('sucursales')
                ->nullOnDelete();

            $table->index('sucursal_id');
        });

        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'sucursal_id')) {
            DB::statement('
                UPDATE venta_items vi
                INNER JOIN ventas v ON v.id = vi.venta_id
                SET vi.sucursal_id = v.sucursal_id
                WHERE vi.sucursal_id IS NULL
            ');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('venta_items') || ! Schema::hasColumn('venta_items', 'sucursal_id')) {
            return;
        }

        Schema::table('venta_items', function (Blueprint $table) {
            $table->dropIndex(['sucursal_id']);
            $table->dropConstrainedForeignId('sucursal_id');
        });
    }
};
