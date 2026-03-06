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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('cliente_membresia_id')->nullable()->constrained('cliente_membresias')->onDelete('set null');
            $table->decimal('monto', 10, 2);
            $table->string('moneda', 3)->default('PEN');
            $table->string('metodo_pago', 50);
            $table->dateTime('fecha_pago');
            $table->boolean('es_pago_parcial')->default(false);
            $table->decimal('saldo_pendiente', 10, 2)->default(0);
            $table->string('comprobante_tipo')->nullable();
            $table->string('comprobante_numero')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('cliente_membresia_id');
            $table->index('fecha_pago');
            $table->index('metodo_pago');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};
