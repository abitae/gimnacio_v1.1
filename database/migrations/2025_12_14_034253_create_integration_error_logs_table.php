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
        Schema::create('integration_error_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('source', ['biotime', 'api', 'webhook']);
            $table->json('payload')->nullable();
            $table->text('error_message');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('source');
            $table->index('resolved_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('integration_error_logs');
    }
};
