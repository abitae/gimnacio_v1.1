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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->string('numero_venta')->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('caja_id')->constrained('cajas')->onDelete('restrict');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('restrict');
            $table->enum('tipo_comprobante', ['boleta', 'factura'])->default('boleta');
            $table->string('numero_comprobante')->nullable();
            $table->string('serie_comprobante')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('igv', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('metodo_pago', 50);
            $table->enum('estado', ['pendiente', 'completada', 'anulada'])->default('completada');
            $table->dateTime('fecha_venta');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('numero_venta');
            $table->index('cliente_id');
            $table->index('caja_id');
            $table->index('usuario_id');
            $table->index('fecha_venta');
            $table->index('estado');
            $table->index('tipo_comprobante');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
