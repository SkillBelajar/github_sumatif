<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Hapus UNIQUE(assessment_session_id, student_id) pada assessment_results.
 * Diperlukan agar ujian ulang bebas bisa membuat baris percobaan baru
 * untuk siswa yang sama di sesi yang sama. Untuk SQLite, tabel di-copy ulang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('assessment_results')) return;

        // Cek apakah unique index masih ada (beri nama generik; SQLite tak punya nama stabil)
        $indexes = DB::select("PRAGMA index_list(assessment_results)");
        foreach ($indexes as $idx) {
            if (!$idx->unique) continue;
            $cols = collect(DB::select("PRAGMA index_info({$idx->name})"))->pluck('name')->sort()->values()->all();
            if ($cols === ['assessment_session_id', 'student_id']) {
                // SQLite tidak mendukung DROP CONSTRAINT → copy ulang tabel
                DB::statement("PRAGMA foreign_keys=OFF");
                DB::beginTransaction();
                DB::statement("CREATE TABLE assessment_results_new (
                    id integer primary key autoincrement not null,
                    assessment_session_id integer not null,
                    student_id integer not null,
                    attempt integer not null default '1',
                    is_latest tinyint(1) not null default '1',
                    start_time datetime not null,
                    end_time datetime,
                    status varchar check (status in ('in_progress', 'completed')) not null default 'in_progress',
                    scores_per_tp text,
                    created_at datetime,
                    updated_at datetime,
                    foreign key(assessment_session_id) references assessment_sessions(id) on delete cascade,
                    foreign key(student_id) references students(id) on delete cascade
                )");
                DB::statement("INSERT INTO assessment_results_new (id, assessment_session_id, student_id, attempt, is_latest, start_time, end_time, status, scores_per_tp, created_at, updated_at)
                    SELECT id, assessment_session_id, student_id, attempt, is_latest, start_time, end_time, status, scores_per_tp, created_at, updated_at FROM assessment_results");
                DB::statement("DROP TABLE assessment_results");
                DB::statement("ALTER TABLE assessment_results_new RENAME TO assessment_results");
                DB::statement("CREATE INDEX idx_ar_latest ON assessment_results (assessment_session_id, student_id, is_latest)");
                DB::commit();
                DB::statement("PRAGMA foreign_keys=ON");
                return;
            }
        }
    }

    public function down(): void
    {
        // Tidak mengembalikan unique constraint (akan merusak data ujian ulang).
    }
};
