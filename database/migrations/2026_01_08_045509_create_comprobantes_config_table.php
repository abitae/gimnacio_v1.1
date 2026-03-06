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
        Schema::create('comprobantes_config', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo', ['boleta', 'factura']);
            $table->string('serie', 10);
            $table->integer('numero_actual')->default(0);
            $table->integer('numero_inicial')->default(1);
            $table->integer('numero_final')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->unique(['tipo', 'serie']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comprobantes_config');
    }
};
