<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_routine_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_routine_id')->constrained('client_routines')->onDelete('cascade');
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index('client_routine_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_routine_days');
    }
};
