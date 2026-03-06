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
        Schema::create('seguimientos_nutricion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('nutricionista_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cita_id')->nullable()->constrained('citas')->onDelete('set null');
            $table->enum('tipo', ['plan_inicial', 'seguimiento', 'recomendacion'])->default('seguimiento');
            $table->date('fecha');
            $table->string('objetivo')->nullable();
            $table->unsignedInteger('calorias_objetivo')->nullable();
            $table->json('macros')->nullable()->comment('proteina, grasa, carbohidratos');
            $table->text('contenido')->nullable();
            $table->enum('estado', ['borrador', 'activo', 'archivado'])->default('activo');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('nutricionista_id');
            $table->index('cita_id');
            $table->index('fecha');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimientos_nutricion');
    }
};
