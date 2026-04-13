<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->after('id');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->unique(['sucursal_id', 'codigo'], 'clientes_sucursal_codigo_unique');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropUnique('clientes_sucursal_codigo_unique');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('codigo');
        });
    }
};
