<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            DB::statement("ALTER TABLE ventas MODIFY COLUMN tipo_comprobante ENUM('ticket', 'boleta', 'factura') DEFAULT 'ticket'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            DB::statement("ALTER TABLE ventas MODIFY COLUMN tipo_comprobante ENUM('boleta', 'factura') DEFAULT 'boleta'");
        });
    }
};
