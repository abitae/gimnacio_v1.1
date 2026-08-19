<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_publicidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('titulo', 80);
            $table->string('imagen');
            $table->string('enlace_url')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->string('estado', 20)->default('activo');
            $table->timestamps();

            $table->index(['sucursal_id', 'estado', 'orden']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_publicidades');
    }
};
