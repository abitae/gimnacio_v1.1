<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobro_tickets_secuencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('ultimo_numero')->default(0);
            $table->timestamps();
        });

        // Secuencia singleton (id=1) para cobros.
        DB::table('cobro_tickets_secuencias')->insert([
            'id' => 1,
            'ultimo_numero' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cobro_tickets_secuencias');
    }
};
