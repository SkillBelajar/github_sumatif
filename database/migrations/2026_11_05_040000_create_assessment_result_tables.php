<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['classroom_id', 'name']);
        });

        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->enum('status', ['in_progress', 'completed'])->default('in_progress');
            $table->timestamps();
            // ponytail: satu siswa = satu pengerjaan aktif per sesi. Anti "buka 2 tab".
            // Kalau nanti perlu riwayat percobaan ulang, tambah kolom attempt + ganti unique ini.
            $table->unique(['assessment_session_id', 'student_id']);
        });

        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('student_answer')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();
            $table->unique(['assessment_result_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists([
            'assessment_answers',
            'assessment_results',
            'students',
        ]);
    }
};
