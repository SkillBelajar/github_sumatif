<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /** Dasbor analitik: metrik ringkas + rekapitulasi nilai per TP. */
    public function results(Request $request, AssessmentSession $assessmentSession)
    {
        abort_unless($assessmentSession->user_id === $request->user()->id, 403);

        $assessmentSession->load(['results.student.classroom']);
        $rows = $this->recap($assessmentSession);

        return inertia('Teacher/Reports/Results', [
            'session' => [
                'id' => $assessmentSession->id,
                'name' => $assessmentSession->name,
                'token' => $assessmentSession->token,
                'status' => $assessmentSession->status,
            ],
            'summary' => $this->summary($rows),
            'tpColumns' => $this->tpColumns($rows),
            'rows' => $rows,
            'classes' => $this->classes($assessmentSession, $rows),
        ]);
    }

    /** Unduh rekap sebagai CSV (dibuka langsung di Excel). */
    public function exportCsv(Request $request, AssessmentSession $assessmentSession)
    {
        abort_unless($assessmentSession->user_id === $request->user()->id, 403);

        $assessmentSession->load(['results.student.classroom']);
        $rows = $this->recap($assessmentSession);
        $tpColumns = $this->tpColumns($rows);
        $filename = 'hasil-'.$assessmentSession->token.'-'.now()->format('Ymd-His').'.csv';

        $callback = function () use ($assessmentSession, $tpColumns, $rows) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 supaya Excel tidak merusak nama beraksen.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_merge(
                ['Nama Siswa', 'Kelas'],
                $tpColumns->map(fn ($tp) => $tp['label'].' ('.$tp['name'].')')->all(),
                ['Nilai Akhir']
            ));

            foreach ($rows as $row) {
                fputcsv($out, array_merge(
                    [$row['student_name'], $row['classroom_name']],
                    $tpColumns->map(fn ($tp) => $row['scores'][$tp['key']]['label'] ?? '-')->all(),
                    [$row['final_score'] ?? '-']
                ));
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Rekap per siswa: nama, kelas, nilai per TP, nilai akhir (rata-rata TP).
     * kolom 'label' = "80 ✓" — dipakai tabel & CSV.
     *
     * @return \Illuminate\Support\Collection<int, array>
     */
    private function recap(AssessmentSession $session): Collection
    {
        // Ujian ulang bebas: ambil NILAI TERTINGGI tiap TP di antara semua
        // percobaan siswa yang sudah selesai, bukan nilai terakhir.
        $completed = $session->results
            ->filter(fn (AssessmentResult $r) => $r->status === 'completed' && $r->student_id);

        $byStudent = $completed
            ->groupBy(fn (AssessmentResult $r) => $r->student_id)
            ->sortBy([
                // urut per kelas, lalu nama abjad di dalam kelas
                fn (Collection $group) => $group->first()->student?->classroom?->name ?? '~',
                fn (Collection $group) => $group->first()->student?->name ?? '~',
            ]);

        return $byStudent
            ->map(function (Collection $attempts) {
                $best = [];      // tp_id => TP terbaik
                $bestResultId = null;
                $bestFinal = -1;

                foreach ($attempts as $result) {
                    $values = [];
                    foreach ($result->scores_per_tp ?? [] as $tp) {
                        $values[] = $tp['score'];
                        $key = 'tp_'.$tp['tp_id'];
                        if (!isset($best[$key]) || $tp['score'] > $best[$key]['score']) {
                            $best[$key] = [
                                'score' => $tp['score'],
                                'is_passed' => (bool) $tp['is_passed'],
                                'correct' => $tp['correct'],
                                'total' => $tp['total'],
                                'label' => $tp['score'].' '.($tp['is_passed'] ? '✓' : '*'),
                                'name' => $tp['tp_name'],
                            ];
                        }
                    }
                    // catat percobaan dengan rata-rata tertinggi (untuk status tampilan)
                    if ($values !== []) {
                        $final = (int) round(collect($values)->avg());
                        if ($final > $bestFinal) {
                            $bestFinal = $final;
                            $bestResultId = $result->id;
                        }
                    }
                }

                $first = $attempts->first();
                $values = collect($best)->pluck('score');

                return [
                    'result_id' => $bestResultId ?? $first->id,
                    'student_name' => $first->student?->name ?? 'Siswa terhapus',
                    'classroom_name' => $first->student?->classroom?->name ?? '-',
                    'status' => 'completed',
                    'scores' => $best,
                    'final_score' => $values->isEmpty() ? null : (int) round($values->avg()),
                ];
            })
            ->values();
    }

    /**
     * Rekap per kelas: daftar siswa yang sudah & belum mengerjakan.
     *
     * @return \Illuminate\Support\Collection<int, array{classroom_id: mixed, name: string, total: int, done: int, done_list: array, not_done_list: array}>
     */
    private function classes(AssessmentSession $session, Collection $rows): Collection
    {
        // siswa yang sudah punya result
        // Nilai tertinggi per siswa (ujian ulang bebas).
        $doneByStudent = $session->results
            ->filter(fn ($r) => $r->student_id && $r->status === 'completed')
            ->groupBy(fn ($r) => $r->student_id)
            ->map(fn (Collection $attempts) => $attempts->sortByDesc(function ($r) {
                return collect($r->scores_per_tp ?? [])->pluck('score')->avg() ?? -1;
            })->first())
            ->all();

        // kelas yang ditugaskan ke sesi ini
        $classrooms = $session->classrooms()->orderBy('name')->get();

        return $classrooms->map(function ($classroom) use ($doneByStudent) {
            $students = $classroom->students()->orderBy('name')->get();

            $done = [];
            $notDone = [];
            foreach ($students as $student) {
                if (array_key_exists($student->id, $doneByStudent)) {
                    $result = $doneByStudent[$student->id];
                    $done[] = [
                        'name' => $student->name,
                        'final_score' => (int) round(collect($result->scores_per_tp ?? [])->pluck('score')->avg() ?? 0),
                        'status' => $result->status,
                    ];
                } else {
                    $notDone[] = ['name' => $student->name];
                }
            }

            return [
                'classroom_id' => $classroom->id,
                'name' => $classroom->name,
                'total' => $students->count(),
                'done' => count($done),
                'done_list' => $done,
                'not_done_list' => $notDone,
            ];
        });
    }

    /**
     * Kolom TP dinamis: urut berdasarkan TP paling sering muncul agar stabil
     * walau siswa hanya mengerjakan sebagian TP.
     *
     * @return \Illuminate\Support\Collection<int, array{key: string, tp_id: mixed, name: string, label: string}>
     */
    private function tpColumns(Collection $rows): Collection
    {
        $frequency = $rows
            ->flatMap(fn ($row) => collect($row['scores'])->map(fn ($s, $key) => [$key, $s['tp_id'] ?? $key]))
            ->countBy(fn ($pair) => $pair[0]);

        $names = $rows
            ->flatMap(fn ($row) => collect($row['scores'])->map(fn ($s, $key) => [$key, $s['name'] ?? null]))
            ->keyBy(0)
            ->map(fn ($pair) => $pair[1]);

        return $frequency
            ->sortKeys()
            ->map(function ($count, $key) use ($names) {
                $tpId = Str::after($key, 'tp_');

                return [
                    'key' => $key,
                    'tp_id' => $tpId,
                    'name' => $names[$key] ?? 'TP '.$tpId,
                    'label' => 'Nilai TP '.$tpId,
                ];
            })
            ->values();
    }

    /**
     * Metrik: total siswa, rata-rata kelas, tuntas vs belum tuntas per TP.
     *
     * @return array{total_students: int, average: int|null, per_tp: array<int, array>}
     */
    private function summary(Collection $rows): array
    {
        $finalScores = $rows->pluck('final_score')->filter(fn ($v) => !is_null($v));

        $perTp = $this->tpColumns($rows)
            ->map(function ($tp) use ($rows) {
                $values = $rows->pluck("scores.{$tp['key']}.score")->filter(fn ($v) => !is_null($v));
                $passed = $rows->filter(fn ($row) => ($row['scores'][$tp['key']]['is_passed'] ?? false) === true)->count();

                return [
                    'tp_id' => $tp['tp_id'],
                    'name' => $tp['name'],
                    'answered' => $values->count(),
                    'passed' => $passed,
                    'failed' => $values->count() - $passed,
                    'average' => $values->isEmpty() ? null : (int) round($values->avg()),
                ];
            })
            ->all();

        return [
            'total_students' => $rows->count(),
            'average' => $finalScores->isEmpty() ? null : (int) round($finalScores->avg()),
            'per_tp' => $perTp,
        ];
    }
}
