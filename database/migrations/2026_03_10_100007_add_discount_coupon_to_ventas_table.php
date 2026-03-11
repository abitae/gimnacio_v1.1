<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('discount_coupon_id')->nullable()->after('entidad_financiera')->constrained('discount_coupons')->onDelete('set null');
            $table->decimal('monto_descuento_cupon', 10, 2)->default(0)->after('discount_coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['discount_coupon_id']);
            $table->dropColumn(['discount_coupon_id', 'monto_descuento_cupon']);
        });
    }
};
