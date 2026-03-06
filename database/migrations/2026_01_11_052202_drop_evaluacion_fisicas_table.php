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
        Schema::dropIfExists('evaluacion_fisicas');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se puede recrear la tabla sin conocer su estructura exacta
        // Si es necesario, se debe crear una migración separada para recrearla
    }
};
