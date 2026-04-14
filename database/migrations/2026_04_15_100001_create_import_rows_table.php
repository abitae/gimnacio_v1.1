<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->cascadeOnDelete();
            $table->unsignedInteger('fila_numero');
            $table->string('estado', 32)->default('pending');
            $table->json('data_json')->nullable();
            $table->json('errores_json')->nullable();
            $table->string('modelo_tipo', 120)->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();
            $table->timestamps();

            $table->index(['import_id', 'fila_numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
