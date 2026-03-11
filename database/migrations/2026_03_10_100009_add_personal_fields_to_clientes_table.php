<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('ocupacion', 80)->nullable()->after('direccion');
            $table->date('fecha_nacimiento')->nullable()->after('ocupacion');
            $table->string('lugar_nacimiento', 120)->nullable()->after('fecha_nacimiento');
            $table->string('estado_civil', 40)->nullable()->after('lugar_nacimiento');
            $table->unsignedTinyInteger('numero_hijos')->nullable()->after('estado_civil');
            $table->string('placa_carro', 20)->nullable()->after('numero_hijos');
        });
    }

    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn([
                'ocupacion',
                'fecha_nacimiento',
                'lugar_nacimiento',
                'estado_civil',
                'numero_hijos',
                'placa_carro',
            ]);
        });
    }
};
