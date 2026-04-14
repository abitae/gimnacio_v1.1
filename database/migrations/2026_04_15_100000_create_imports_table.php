<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_importacion', 64);
            $table->string('archivo_nombre');
            $table->string('archivo_path')->nullable();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->unsignedInteger('total_filas')->default(0);
            $table->unsignedInteger('filas_validas')->default(0);
            $table->unsignedInteger('filas_error')->default(0);
            $table->unsignedInteger('filas_importadas')->default(0);
            $table->string('estado', 32)->default('preview');
            $table->text('observaciones')->nullable();
            $table->json('opciones')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tipo_importacion', 'created_at']);
            $table->index(['sucursal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imports');
    }
};
