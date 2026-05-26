<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_reprints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reprinted_at');
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['venta_id', 'reprinted_at']);
            $table->index(['user_id', 'reprinted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_reprints');
    }
};
