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
        Schema::create('cliente_membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('membresia_id')->constrained('membresias')->onDelete('restrict');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->enum('estado', ['activa', 'vencida', 'cancelada', 'congelada'])->default('activa');
            $table->decimal('precio_lista', 10, 2);
            $table->decimal('descuento_monto', 10, 2)->default(0);
            $table->decimal('precio_final', 10, 2);
            $table->foreignId('asesor_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('canal_venta')->nullable();
            $table->json('fechas_congelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('membresia_id');
            $table->index('estado');
            $table->index('fecha_inicio');
            $table->index('fecha_fin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cliente_membresias');
    }
};
