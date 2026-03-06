<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('biotime_access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('biotime_user_id');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->string('device_id')->nullable();
            $table->dateTime('event_time');
            $table->enum('event_type', ['entry', 'exit']);
            $table->enum('result', ['success', 'denied']);
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index('biotime_user_id');
            $table->index('cliente_id');
            $table->index('event_time');
            $table->index('event_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biotime_access_logs');
    }
};
