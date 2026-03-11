<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentable_spaces', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo', 40)->default('otro'); // cancha_futbol, cancha_basquet, voley, salon_funcional, otro
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('capacidad')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->string('color_calendario', 20)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentable_spaces');
    }
};
