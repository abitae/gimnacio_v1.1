<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_membresias', function (Blueprint $table) {
            $table->date('fecha_matricula')->nullable()->after('membresia_id');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_membresias', function (Blueprint $table) {
            $table->dropColumn('fecha_matricula');
        });
    }
};
