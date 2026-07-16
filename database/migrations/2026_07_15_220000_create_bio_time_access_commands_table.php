<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_time_access_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('emp_code', 50)->index();
            $table->string('action', 20);
            $table->unsignedBigInteger('desired_area_biotime_id')->nullable()->index();
            $table->string('status', 20)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('acked_at')->nullable();
            $table->timestamps();

            $table->index(['sucursal_id', 'status']);
            $table->index(['cliente_id', 'sucursal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_time_access_commands');
    }
};
