<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->enum('checkout_origen', ['manual', 'automatico'])
                ->nullable()
                ->after('fecha_hora_salida');
            $table->index('checkout_origen');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropIndex(['checkout_origen']);
            $table->dropColumn('checkout_origen');
        });
    }
};
