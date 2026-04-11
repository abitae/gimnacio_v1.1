<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('razon_social')->nullable();
            $table->string('ruc', 20)->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->timestamps();
        });

        Schema::create('sucursales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->string('codigo', 50)->unique();
            $table->string('nombre');
            $table->text('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->enum('estado', ['activa', 'inactiva'])->default('activa');
            $table->boolean('es_principal')->default(false);
            $table->json('horarios_acceso')->nullable();
            $table->text('politicas_acceso')->nullable();
            $table->timestamps();
        });

        Schema::create('sucursal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['sucursal_id', 'user_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('default_sucursal_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('sucursales')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_sucursal_id');
        });

        Schema::dropIfExists('sucursal_user');
        Schema::dropIfExists('sucursales');
        Schema::dropIfExists('empresas');
    }
};
