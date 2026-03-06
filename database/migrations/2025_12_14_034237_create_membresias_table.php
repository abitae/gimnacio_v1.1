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
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->integer('duracion_dias');
            $table->decimal('precio_base', 10, 2);
            $table->string('tipo_acceso')->nullable();
            $table->integer('max_visitas_dia')->nullable();
            $table->boolean('permite_congelacion')->default(false);
            $table->integer('max_dias_congelacion')->nullable();
            $table->string('estado')->default('activa');
            $table->timestamps();

            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membresias');
    }
};
