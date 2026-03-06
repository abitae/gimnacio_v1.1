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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_documento', ['DNI', 'CE']);
            $table->string('numero_documento', 20);
            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('direccion')->nullable();
            $table->string('estado_cliente')->default('activo');
            $table->string('foto')->nullable();
            $table->enum('sexo', ['masculino', 'femenino'])->nullable();
            $table->json('datos_salud')->nullable();
            $table->json('datos_emergencia')->nullable();
            $table->json('consentimientos')->nullable();
            $table->boolean('biotime_state')->default(false)->comment('Si el cliente ya está sincronizado en BioTime');
            $table->boolean('biotime_update')->default(false);
            $table->foreignId('trainer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['tipo_documento', 'numero_documento']);
            $table->index('estado_cliente');
            $table->index('biotime_state');
            $table->index('trainer_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clientes');
    }
};
