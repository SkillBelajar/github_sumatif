<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            // Nomor percobaan ujian (1 = pertama). Untuk fitur ujian ulang bebas.
            $table->unsignedInteger('attempt')->default(1)->after('student_id');
            // Tandai percobaan mana yang terbaru per (session, student) -> dipakai rekap guru.
            $table->boolean('is_latest')->default(true)->after('attempt');
            $table->index(['assessment_session_id', 'student_id', 'is_latest']);
        });
    }

    public function down(): void
    {
        Schema::table('assessment_results', function (Blueprint $table) {
            $table->dropIndex(['assessment_session_id', 'student_id', 'is_latest']);
            $table->dropColumn(['attempt', 'is_latest']);
        });
    }
};
