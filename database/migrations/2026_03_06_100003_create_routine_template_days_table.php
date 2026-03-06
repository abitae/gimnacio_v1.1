<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_template_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_template_id')->constrained('routine_templates')->onDelete('cascade');
            $table->string('nombre');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index('routine_template_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_template_days');
    }
};
