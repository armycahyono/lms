<?php

namespace App\Services;

use App\Models\QuestionBank;
use App\Models\Subject;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class QuestionBankExcelService
{
    public function buildTemplateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle('Template Impor Massal Bank Soal LMS')
            ->setCreator('LMS SMK PGRI Kras');

        $subjects = Subject::orderBy('name')->get(['id', 'code', 'name']);
        $sampleSubject = $subjects->first()?->name ?? 'Bahasa Indonesia';

        // Sheet 1: Bank_Soal
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Bank_Soal');
        $sheet1->fromArray([
            [
                'Mata_Pelajaran',
                'Tingkat_Kelas',
                'Bab_Kategori',
                'Kisi_Kisi',
                'Tipe',
                'Kesulitan',
                'Poin',
                'Pertanyaan',
                'Opsi_A',
                'Opsi_B',
                'Opsi_C',
                'Opsi_D',
                'Opsi_E',
                'Pasangan_Menjodohkan',
                'Kunci_Jawaban',
                'Bagikan',
            ],
            [
                $sampleSubject,
                10,
                'Bab 1 LHO',
                'Membedakan kalimat fakta dan opini',
                'PG',
                'mudah',
                10,
                'Contoh PG: Manakah kalimat yang berisi fakta objektif?',
                'Bengkel itu sangat nyaman.',
                'Mesin 4-tak memiliki 4 langkah kerja piston.',
                'Pelayanan seharusnya lebih cepat.',
                'Alat ini sangat mahal.',
                'Mekanik itu paling rajin.',
                '',
                'B',
                'TIDAK',
            ],
            [
                $sampleSubject,
                10,
                'Bab 1 LHO',
                'Menentukan struktur teks LHO',
                'PG Kompleks',
                'sedang',
                15,
                'Contoh PG Kompleks: Pilih dua bagian yang termasuk struktur teks LHO!',
                'Pernyataan Umum',
                'Abstraksi',
                'Deskripsi Bagian',
                'Koda',
                'Krisis',
                '',
                'A,C',
                'TIDAK',
            ],
            [
                $sampleSubject,
                10,
                'Bab 2 Anekdot',
                'Menjodohkan struktur anekdot dengan fungsinya',
                'Menjodohkan',
                'sedang',
                15,
                'Contoh Menjodohkan: Pasangkan istilah struktur teks anekdot berikut!',
                '',
                '',
                '',
                '',
                '',
                'Krisis=Bagian munculnya masalah lucu|Koda=Bagian penutup/simpulan|Orientasi=Pengenalan latar cerita',
                '',
                'TIDAK',
            ],
            [
                $sampleSubject,
                10,
                'Bab 1 LHO',
                'Menyebutkan verba penghubung kalimat definisi',
                'Isian Singkat',
                'mudah',
                10,
                'Contoh Isian Singkat: Kata penghubung yang biasa digunakan pada kalimat definisi adalah...',
                '',
                '',
                '',
                '',
                '',
                '',
                'adalah',
                'TIDAK',
            ],
            [
                $sampleSubject,
                10,
                'Bab 1 LHO',
                'Menyusun deskripsi manfaat alat praktik',
                'Esai',
                'sulit',
                20,
                'Contoh Esai: Jelaskan manfaat kunci torsi di bengkel otomotif!',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                'TIDAK',
            ],
        ], null, 'A1');

        $this->styleHeader($sheet1, 'A1:P1');
        $widths = ['A' => 22, 'B' => 14, 'C' => 18, 'D' => 30, 'E' => 16, 'F' => 12, 'G' => 8, 'H' => 42, 'I' => 22, 'J' => 22, 'K' => 22, 'L' => 22, 'M' => 22, 'N' => 38, 'O' => 18, 'P' => 12];
        foreach ($widths as $col => $w) {
            $sheet1->getColumnDimension($col)->setWidth($w);
        }
        $sheet1->getStyle('A2:P200')->getAlignment()->setWrapText(true);

        // Sheet 2: Panduan_&_Daftar_Mapel
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Panduan_&_Daftar_Mapel');
        $guideRows = [
            ['Kolom / Aturan', 'Penjelasan & Contoh'],
            ['Tipe', 'Isi salah satu: PG | PG Kompleks | Menjodohkan | Isian Singkat | Esai'],
            ['PG (Pilihan Ganda)', 'Isi Opsi_A s.d. Opsi_E (minimal A & B). Kunci_Jawaban: A, B, C, D, atau E.'],
            ['PG Kompleks', 'Isi Opsi_A s.d. Opsi_E. Kunci_Jawaban dipisah koma, contoh: A,C atau A,C,E.'],
            ['Menjodohkan', 'Isi kolom Pasangan_Menjodohkan dengan format Kiri=Kanan dipisah tanda | (contoh: A=1|B=2|C=3).'],
            ['Isian Singkat', 'Isi Kunci_Jawaban dengan teks jawaban singkat yang benar.'],
            ['Esai', 'Opsi dan Kunci_Jawaban boleh dikosongkan.'],
            ['Tingkat_Kelas', 'Isi angka 10, 11, atau 12 (opsional).'],
            ['Bagikan', 'Isi YA untuk membagikan ke rekan guru mapel ini, atau TIDAK (default Privat).'],
            ['', ''],
            ['Daftar Mata Pelajaran Resmi di LMS', 'Kode Mapel'],
        ];
        foreach ($subjects as $s) {
            $guideRows[] = [$s->name, $s->code ?? '-'];
        }
        $sheet2->fromArray($guideRows, null, 'A1');
        $this->styleHeader($sheet2, 'A1:B1');
        $sheet2->getColumnDimension('A')->setWidth(36);
        $sheet2->getColumnDimension('B')->setWidth(75);
        $sheet2->getStyle('A2:B100')->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{
     *     rows: array<int, array<string, mixed>>,
     *     duplicates: array<int, string>,
     *     errors: array<int, string>
     * }
     */
    public function parseUploadedFile(string $filePath, int $teacherId, array $context = []): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getSheetByName('Bank_Soal') ?? $spreadsheet->getActiveSheet();

        $subjectsByName = Subject::all()->keyBy(fn (Subject $s) => mb_strtolower(trim($s->name)));
        $existingQuestions = QuestionBank::where('teacher_id', $teacherId)
            ->pluck('question_text')
            ->map(fn ($t) => mb_strtolower(trim(strip_tags((string) $t))))
            ->flip()
            ->all();

        $contextSubject = ! empty($context['subject_id']) ? Subject::find($context['subject_id']) : null;
        $contextGrade = ! empty($context['grade_level']) ? (int) $context['grade_level'] : null;
        $contextDept = ! empty($context['department']) ? (string) $context['department'] : null;
        $contextType = ! empty($context['type']) ? (string) $context['type'] : null;
        $contextShared = isset($context['is_shared']) ? (bool) $context['is_shared'] : null;

        $rows = [];
        $duplicates = [];
        $errors = [];

        foreach ($sheet->toArray(null, true, true, false) as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $subjectName = trim((string) ($row[0] ?? ''));
            $gradeRaw = trim((string) ($row[1] ?? ''));
            $category = trim((string) ($row[2] ?? ''));
            $kisi = trim((string) ($row[3] ?? ''));
            $typeRaw = mb_strtolower(trim((string) ($row[4] ?? '')));
            $difficultyRaw = mb_strtolower(trim((string) ($row[5] ?? 'sedang')));
            $pointsRaw = $row[6] ?? 10;
            $questionText = trim((string) ($row[7] ?? ''));
            $optA = trim((string) ($row[8] ?? ''));
            $optB = trim((string) ($row[9] ?? ''));
            $optC = trim((string) ($row[10] ?? ''));
            $optD = trim((string) ($row[11] ?? ''));
            $optE = trim((string) ($row[12] ?? ''));
            $matchingRaw = trim((string) ($row[13] ?? ''));
            $answerRaw = trim((string) ($row[14] ?? ''));
            $shareRaw = mb_strtolower(trim((string) ($row[15] ?? 'tidak')));

            if ($questionText === '' && $subjectName === '' && $typeRaw === '') {
                continue;
            }

            $rowNum = $index + 1;

            if ($questionText === '') {
                $errors[] = "Baris {$rowNum}: Pertanyaan wajib diisi.";

                continue;
            }

            $normText = mb_strtolower($questionText);
            if (isset($existingQuestions[$normText])) {
                $duplicates[] = "Baris {$rowNum}: \"{$questionText}\" sudah ada di Bank Soal Anda (dilewati).";

                continue;
            }

            $subjectId = $contextSubject?->id;
            $resolvedSubjectName = $contextSubject?->name ?? 'Umum';
            if ($subjectId === null && $subjectName !== '') {
                $matchedSubject = $subjectsByName->get(mb_strtolower($subjectName));
                if (! $matchedSubject) {
                    $errors[] = "Baris {$rowNum}: Mata pelajaran \"{$subjectName}\" tidak ditemukan di LMS.";

                    continue;
                }
                $subjectId = $matchedSubject->id;
                $resolvedSubjectName = $matchedSubject->name;
            }

            $gradeLevel = $contextGrade;
            if ($gradeLevel === null && $gradeRaw !== '') {
                if (! in_array((int) $gradeRaw, [10, 11, 12], true)) {
                    $errors[] = "Baris {$rowNum}: Tingkat_Kelas harus 10, 11, atau 12.";

                    continue;
                }
                $gradeLevel = (int) $gradeRaw;
            }

            $type = $contextType ?: match ($typeRaw) {
                'pg', 'pilihan ganda', 'multiple_choice' => 'multiple_choice',
                'pg kompleks', 'kompleks', 'multiple_choice_complex' => 'multiple_choice_complex',
                'menjodohkan', 'jodohkan', 'matching' => 'matching',
                'isian singkat', 'isian', 'short_answer' => 'short_answer',
                'esai', 'uraian', 'essay' => 'essay',
                default => null,
            };

            if (! $type) {
                $errors[] = "Baris {$rowNum}: Tipe \"{$row[4]}\" tidak dikenali (gunakan PG, PG Kompleks, Menjodohkan, Isian Singkat, atau Esai).";

                continue;
            }

            $difficulty = in_array($difficultyRaw, ['mudah', 'sedang', 'sulit'], true) ? $difficultyRaw : 'sedang';
            $points = is_numeric($pointsRaw) && (float) $pointsRaw > 0 ? (float) $pointsRaw : 10.0;

            $options = null;
            $correctAnswer = $answerRaw !== '' ? $answerRaw : null;

            if (in_array($type, ['multiple_choice', 'multiple_choice_complex'], true)) {
                $options = array_filter([
                    'A' => $optA !== '' ? $optA : null,
                    'B' => $optB !== '' ? $optB : null,
                    'C' => $optC !== '' ? $optC : null,
                    'D' => $optD !== '' ? $optD : null,
                    'E' => $optE !== '' ? $optE : null,
                ], fn ($v) => $v !== null);

                if (count($options) < 2) {
                    $errors[] = "Baris {$rowNum}: Soal {$typeRaw} minimal harus memiliki 2 opsi jawaban.";

                    continue;
                }

                if ($type === 'multiple_choice') {
                    $ansKey = strtoupper((string) $correctAnswer);
                    if (! array_key_exists($ansKey, $options)) {
                        $errors[] = "Baris {$rowNum}: Kunci jawaban PG \"{$correctAnswer}\" tidak cocok dengan opsi yang tersedia.";

                        continue;
                    }
                    $correctAnswer = $ansKey;
                } else {
                    $keys = collect(explode(',', strtoupper((string) $correctAnswer)))
                        ->map(fn ($k) => trim($k))
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values();

                    if ($keys->isEmpty() || $keys->contains(fn ($k) => ! array_key_exists($k, $options))) {
                        $errors[] = "Baris {$rowNum}: Kunci jawaban PG Kompleks harus berisi huruf opsi valid yang dipisah koma (misal: A,C).";

                        continue;
                    }
                    $correctAnswer = $keys->join(',');
                }
            } elseif ($type === 'matching') {
                $pairs = [];
                foreach (explode('|', $matchingRaw) as $pairIdx => $pairStr) {
                    $parts = explode('=', $pairStr, 2);
                    if (count($parts) === 2 && trim($parts[0]) !== '' && trim($parts[1]) !== '') {
                        $pairs[(string) ($pairIdx + 1)] = [
                            'left' => trim($parts[0]),
                            'right' => trim($parts[1]),
                        ];
                    }
                }
                if (count($pairs) < 2) {
                    $errors[] = "Baris {$rowNum}: Soal Menjodohkan minimal harus memiliki 2 pasangan dengan format Kiri=Kanan dipisah tanda |.";

                    continue;
                }
                $options = $pairs;
                $correctAnswer = json_encode(
                    collect($pairs)->mapWithKeys(fn ($p) => [$p['left'] => $p['right']])->all(),
                    JSON_UNESCAPED_UNICODE
                );
            } elseif ($type === 'short_answer' && ($correctAnswer === null || $correctAnswer === '')) {
                $errors[] = "Baris {$rowNum}: Soal Isian Singkat wajib memiliki Kunci_Jawaban.";

                continue;
            }

            // Convert Excel line breaks into clean paragraphs for Bahasa/long texts
            $formattedQuestion = str_contains($questionText, "\n")
                ? collect(preg_split('/\r\n|\r|\n/', $questionText))
                    ->map(fn ($line) => trim($line))
                    ->filter()
                    ->map(fn ($line) => '<p>'.e($line).'</p>')
                    ->implode('')
                : $questionText;

            $rows[] = [
                'subject_id' => $subjectId,
                'subject_name' => $resolvedSubjectName,
                'grade_level' => $gradeLevel,
                'department' => $contextDept,
                'category' => $category !== '' ? $category : null,
                'kisi_indicator' => $kisi !== '' ? $kisi : null,
                'difficulty' => $difficulty,
                'type' => $type,
                'type_label' => QuestionBank::TYPES[$type] ?? $type,
                'question_text' => $formattedQuestion,
                'question_image' => null,
                'options_json' => $options,
                'option_images_json' => null,
                'correct_answer' => $correctAnswer,
                'points' => $points,
                'is_shared' => $contextShared ?? in_array($shareRaw, ['ya', 'yes', '1', 'true'], true),
            ];
            $existingQuestions[$normText] = true;
        }

        return [
            'rows' => $rows,
            'duplicates' => $duplicates,
            'errors' => $errors,
        ];
    }

    private function styleHeader(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0284C7'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(24);
    }
}
