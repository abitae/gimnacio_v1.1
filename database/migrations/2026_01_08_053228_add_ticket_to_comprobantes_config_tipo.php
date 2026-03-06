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
        Schema::table('comprobantes_config', function (Blueprint $table) {
            DB::statement("ALTER TABLE comprobantes_config MODIFY COLUMN tipo ENUM('ticket', 'boleta', 'factura')");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comprobantes_config', function (Blueprint $table) {
            DB::statement("ALTER TABLE comprobantes_config MODIFY COLUMN tipo ENUM('boleta', 'factura')");
        });
    }
};
