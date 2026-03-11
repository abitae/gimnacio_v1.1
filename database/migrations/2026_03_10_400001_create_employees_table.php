<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('documento', 30);
            $table->string('cargo', 80)->nullable();
            $table->string('area', 80)->nullable();
            $table->string('telefono', 20)->nullable();
            $table->date('fecha_ingreso')->nullable();
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
