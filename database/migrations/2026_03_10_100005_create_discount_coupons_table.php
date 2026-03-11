<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('nombre', 120);
            $table->text('descripcion')->nullable();
            $table->enum('tipo_descuento', ['porcentaje', 'monto_fijo']);
            $table->decimal('valor_descuento', 10, 2);
            $table->date('fecha_inicio');
            $table->date('fecha_vencimiento');
            $table->unsignedInteger('cantidad_max_usos')->nullable();
            $table->unsignedInteger('cantidad_usada')->default(0);
            $table->string('aplica_a', 40)->default('todos')->comment('pos, matricula, membresia, clases, todos');
            $table->string('estado', 20)->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index('codigo');
            $table->index('estado');
            $table->index(['fecha_inicio', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_coupons');
    }
};
