<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Agregar cliente_matricula_id a pagos
        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('cliente_matricula_id')->nullable()->after('cliente_membresia_id')->constrained('cliente_matriculas')->onDelete('set null');
            $table->index('cliente_matricula_id');
        });

        // Agregar cliente_matricula_id a asistencias
        Schema::table('asistencias', function (Blueprint $table) {
            $table->foreignId('cliente_matricula_id')->nullable()->after('cliente_membresia_id')->constrained('cliente_matriculas')->onDelete('set null');
            $table->index('cliente_matricula_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['cliente_matricula_id']);
            $table->dropIndex(['cliente_matricula_id']);
            $table->dropColumn('cliente_matricula_id');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropForeign(['cliente_matricula_id']);
            $table->dropIndex(['cliente_matricula_id']);
            $table->dropColumn('cliente_matricula_id');
        });
    }
};
