<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\LearningObjective;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuestionImportController extends Controller
{
    public function downloadTemplate()
    {
        $rows = [
            ['TP_ID', 'Fase', 'Tipe', 'Soal', 'Opsi_A', 'Opsi_B', 'Opsi_C', 'Opsi_D', 'Kunci_Jawaban', 'Pembahasan'],
            ['1', 'Fase D', 'multiple_choice', 'Hasil dari 2 + 3 x 4 adalah...', '14', '20', '24', '11', 'A', 'Perkalian dikerjakan lebih dulu: 3x4=12, lalu 2+12=14.'],
            ['1', 'Fase D', 'true_false', 'Bilangan prima terkecil adalah 2.', '', '', '', '', 'benar', 'Ya, 2 adalah bilangan prima terkecil.'],
            ['1', 'Fase D', 'short_answer', 'Hasil dari 25 - 10 adalah...', '', '', '', '', '15', '25 - 10 = 15.'],
            ['1', 'Fase D', 'essay', 'Jelaskan langkah menyelesaikan persamaan 2x + 4 = 10.', '', '', '', '', 'x = 3', 'Kurangi 4 kedua sisi: 2x=6, bagi 2: x=3.'],
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, 'template_import_soal.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $path = $request->file('file')->getRealPath();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return back()->with('error', 'Gagal membuka file.');
        }

        // Deteksi & lewati baris header
        $header = fgetcsv($handle);
        $imported = 0;
        $errors = [];
        $line = 1;

        $objectiveIds = LearningObjective::pluck('id')->all();

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count($row) < 4 || trim((string) ($row[0] ?? '')) === '') {
                    continue;
                }

                $tpId = (int) trim($row[0]);
                $phase = trim((string) ($row[1] ?? '')) ?: 'Fase D';
                $type = trim((string) ($row[2] ?? ''));
                $content = trim((string) ($row[3] ?? ''));
                $optA = trim((string) ($row[4] ?? ''));
                $optB = trim((string) ($row[5] ?? ''));
                $optC = trim((string) ($row[6] ?? ''));
                $optD = trim((string) ($row[7] ?? ''));
                $correct = trim((string) ($row[8] ?? ''));
                $explanation = trim((string) ($row[9] ?? ''));

                if (!in_array($tpId, $objectiveIds, true)) {
                    $errors[] = "Baris {$line}: TP_ID {$tpId} tidak ditemukan.";
                    continue;
                }

                if (!array_key_exists($type, Question::TYPES)) {
                    $errors[] = "Baris {$line}: Tipe '{$type}' tidak valid.";
                    continue;
                }

                if ($content === '' || $correct === '') {
                    $errors[] = "Baris {$line}: Soal / Kunci kosong.";
                    continue;
                }

                $options = null;
                if ($type === 'multiple_choice') {
                    $options = array_filter([
                        'A' => $optA,
                        'B' => $optB,
                        'C' => $optC,
                        'D' => $optD,
                    ], fn ($v) => $v !== '');

                    $correct = strtoupper($correct);
                    if (!isset($options[$correct])) {
                        $errors[] = "Baris {$line}: Kunci '{$correct}' harus salah satu dari A/B/C/D.";
                        continue;
                    }
                }

                Question::create([
                    'learning_objective_id' => $tpId,
                    'phase' => $phase,
                    'type' => $type,
                    'content' => $content,
                    'options' => $options,
                    'correct_answer' => $correct,
                    'explanation' => $explanation ?: null,
                ]);

                $imported++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            fclose($handle);

            return back()->with('error', 'Import gagal: '.$e->getMessage());
        }

        fclose($handle);

        $message = "{$imported} soal berhasil diimport.";
        if ($errors) {
            $message .= ' '.count($errors).' baris dilewati.';
        }

        return back()
            ->with($imported > 0 ? 'success' : 'error', $message)
            ->with('import_errors', array_slice($errors, 0, 20));
    }
}
