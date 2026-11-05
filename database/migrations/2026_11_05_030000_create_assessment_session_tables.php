<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['user_id', 'name']);
        });

        Schema::create('assessment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('duration_minutes')->default(60);
            $table->string('token', 16)->unique();
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->timestamps();
        });

        Schema::create('assessment_session_classroom', function (Blueprint $table) {
            $table->foreignId('assessment_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->primary(['assessment_session_id', 'classroom_id']);
        });

        Schema::create('assessment_session_question', function (Blueprint $table) {
            $table->foreignId('assessment_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->primary(['assessment_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        // ponytail: satu file untuk 4 tabel supaya rollback Slice 5 atomik.
        // Kalau nanti butuh seed/urutan terpisah, pecah per tabel.
        Schema::dropIfExists([
            'assessment_session_question',
            'assessment_session_classroom',
            'assessment_sessions',
            'classrooms',
        ]);
    }
};
