<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rentable_space_id')->constrained('rentable_spaces')->onDelete('restrict');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('nombre_externo', 120)->nullable();
            $table->string('documento_externo', 30)->nullable();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->decimal('precio', 12, 2);
            $table->string('estado', 20)->default('reservado'); // reservado, confirmado, pagado, cancelado, finalizado
            $table->decimal('descuento', 12, 2)->default(0);
            $table->foreignId('discount_coupon_id')->nullable()->constrained('discount_coupons')->onDelete('set null');
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['rentable_space_id', 'fecha']);
            $table->index(['fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
