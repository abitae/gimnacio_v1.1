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
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('cliente_membresia_id')->nullable()->constrained('cliente_membresias')->onDelete('set null');
            $table->dateTime('fecha_hora_ingreso');
            $table->dateTime('fecha_hora_salida')->nullable();
            $table->enum('origen', ['manual', 'app', 'biotime'])->default('manual');
            $table->boolean('valido_por_membresia')->default(true);
            $table->foreignId('registrada_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('cliente_membresia_id');
            $table->index('fecha_hora_ingreso');
            $table->index('origen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
