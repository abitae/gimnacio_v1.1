<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_coupon_id')->constrained('discount_coupons')->onDelete('cascade');
            $table->string('usable_type', 100)->comment('Venta, Pago, etc.');
            $table->unsignedBigInteger('usable_id');
            $table->decimal('monto_descuento_aplicado', 10, 2);
            $table->foreignId('usado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['usable_type', 'usable_id']);
            $table->index('discount_coupon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
