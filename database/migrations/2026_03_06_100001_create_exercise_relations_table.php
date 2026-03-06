<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercise_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('cascade');
            $table->foreignId('related_exercise_id')->constrained('exercises')->onDelete('cascade');
            $table->string('relation_type'); // variation, substitution
            $table->timestamps();

            $table->unique(['exercise_id', 'related_exercise_id', 'relation_type'], 'ex_relations_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercise_relations');
    }
};
