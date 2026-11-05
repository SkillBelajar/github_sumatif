<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_objective_id')->constrained('learning_objectives')->cascadeOnDelete();
            $table->string('phase')->default('Fase D');
            $table->json('classes_tagged')->nullable();
            $table->enum('type', ['multiple_choice', 'true_false', 'short_answer', 'essay']);
            $table->text('content');
            $table->json('options')->nullable();
            $table->text('correct_answer')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
