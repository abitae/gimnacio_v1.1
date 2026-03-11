<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('cliente_venta_nombre')->nullable()->after('employee_id');
            $table->string('cliente_venta_documento')->nullable()->after('cliente_venta_nombre');
            $table->string('cliente_venta_telefono')->nullable()->after('cliente_venta_documento');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['cliente_venta_nombre', 'cliente_venta_documento', 'cliente_venta_telefono']);
        });
    }
};
