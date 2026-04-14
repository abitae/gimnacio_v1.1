<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_debts', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->nullable()->after('cliente_id')->constrained('sucursales')->nullOnDelete();
            $table->index('sucursal_id');
        });

        if (Schema::hasTable('clientes') && Schema::hasColumn('client_debts', 'sucursal_id')) {
            DB::statement('
                UPDATE client_debts cd
                INNER JOIN clientes c ON c.id = cd.cliente_id
                SET cd.sucursal_id = c.sucursal_id
                WHERE cd.sucursal_id IS NULL AND c.sucursal_id IS NOT NULL
            ');
        }
    }

    public function down(): void
    {
        Schema::table('client_debts', function (Blueprint $table) {
            $table->dropForeign(['sucursal_id']);
        });
    }
};
