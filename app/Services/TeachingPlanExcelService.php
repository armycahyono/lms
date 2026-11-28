<?php

namespace App\Services;

use App\Models\TeachingPlan;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TeachingPlanExcelService
{
    public function buildTemplateSpreadsheet(TeachingPlan $plan): Spreadsheet
    {
        $plan->loadMissing(['learningObjectives', 'lessonPlans.learningObjective', 'subject']);

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setTitle("Template Perangkat Ajar - {$plan->title}")
            ->setCreator('LMS SMK PGRI Kras');

        // Sheet 1: CP_Prota_KKTP
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('CP_Prota_KKTP');
        $sheet1->fromArray([
            ['Kolom', 'Isi Nilai'],
            ['Judul_Perangkat', $plan->title],
            ['Fase', $plan->phase ?? ($plan->grade_level === 10 ? 'Fase E' : 'Fase F')],
            ['Capaian_Pembelajaran', $plan->capaian_pembelajaran ?? 'Tuliskan Capaian Pembelajaran di sini...'],
            ['Minggu_Efektif', $plan->effective_weeks ?? 36],
            ['Target_Total_JP', $plan->total_jp ?? 108],
            ['Pendekatan_KKTP', $plan->kktp_type ?? 'interval'],
            ['Skor_Minimal_KKTP', $plan->kktp_min_score ?? 75],
            ['Pedoman_KKTP', $plan->kktp_notes ?? '0-74: Belum Tuntas (Remedial) | 75-100: Tuntas'],
        ], null, 'A1');
        $this->styleHeader($sheet1, 'A1:B1');
        $sheet1->getColumnDimension('A')->setWidth(28);
        $sheet1->getColumnDimension('B')->setWidth(85);
        $sheet1->getStyle('B2:B9')->getAlignment()->setWrapText(true);

        // Sheet 2: ATP
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('ATP');
        $atpRows = [
            ['Kode_TP', 'Tujuan_Pembelajaran', 'Materi_Pokok_Deskripsi', 'Alokasi_JP', 'Periode_Bulan'],
        ];
        if ($plan->learningObjectives->isNotEmpty()) {
            foreach ($plan->learningObjectives as $obj) {
                $atpRows[] = [
                    $obj->code,
                    $obj->title,
                    $obj->description ?? '',
                    $obj->jp_allocation,
                    $obj->target_month ?? '',
                ];
            }
        } else {
            $atpRows[] = ['TP-1', 'Contoh: Memahami konsep dasar materi', 'Ringkasan materi pokok TP-1', 12, 'Juli - Agustus'];
        }
        $sheet2->fromArray($atpRows, null, 'A1');
        $this->styleHeader($sheet2, 'A1:E1');
        $sheet2->getColumnDimension('A')->setWidth(14);
        $sheet2->getColumnDimension('B')->setWidth(45);
        $sheet2->getColumnDimension('C')->setWidth(45);
        $sheet2->getColumnDimension('D')->setWidth(14);
        $sheet2->getColumnDimension('E')->setWidth(22);
        $sheet2->getStyle('A2:E100')->getAlignment()->setWrapText(true);

        // Sheet 3: Modul_Ajar
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Modul_Ajar');
        $modulRows = [
            ['Judul_Modul_Ajar', 'Kode_TP_Terkait', 'Tujuan_Pembelajaran', 'Langkah_Kegiatan', 'Rencana_Asesmen', 'Tautan_Video_atau_Drive'],
        ];
        if ($plan->lessonPlans->isNotEmpty()) {
            foreach ($plan->lessonPlans as $lp) {
                $modulRows[] = [
                    $lp->title,
                    $lp->learningObjective?->code ?? '',
                    $lp->learning_goals ?? '',
                    $lp->activities ?? '',
                    $lp->assessment_plan ?? '',
                    $lp->external_url ?? '',
                ];
            }
        } else {
            $modulRows[] = [
                'Modul Ajar 1: Pertemuan Pembuka',
                'TP-1',
                'Siswa mampu menjelaskan konsep dasar.',
                'Pendahuluan: Apersepsi. Inti: Diskusi & praktik. Penutup: Refleksi.',
                'Formatif: LKPD dan kuis singkat.',
                'https://drive.google.com/file/d/contoh-tautan',
            ];
        }
        $sheet3->fromArray($modulRows, null, 'A1');
        $this->styleHeader($sheet3, 'A1:F1');
        $sheet3->getColumnDimension('A')->setWidth(34);
        $sheet3->getColumnDimension('B')->setWidth(18);
        $sheet3->getColumnDimension('C')->setWidth(38);
        $sheet3->getColumnDimension('D')->setWidth(45);
        $sheet3->getColumnDimension('E')->setWidth(32);
        $sheet3->getColumnDimension('F')->setWidth(36);
        $sheet3->getStyle('A2:F100')->getAlignment()->setWrapText(true);

        // Sheet 4: Panduan_Pengisian
        $sheet4 = $spreadsheet->createSheet();
        $sheet4->setTitle('Panduan_Pengisian');
        $sheet4->fromArray([
            ['Bagian / Sheet', 'Aturan Pengisian'],
            ['Sheet CP_Prota_KKTP', 'Jangan ubah nama kolom di Kolom A. Isi nilai di Kolom B. Pendekatan_KKTP harus bernilai: interval, deskripsi, atau rubrik.'],
            ['Sheet ATP', 'Isi mulai baris 2. Kolom Kode_TP (mis. TP-10.1), Tujuan_Pembelajaran, dan Alokasi_JP (angka) wajib diisi.'],
            ['Sheet Modul_Ajar', 'Isi mulai baris 2. Kode_TP_Terkait dapat diisi kode TP yang ada di Sheet ATP agar otomatis terhubung.'],
            ['Tautan_Video_atau_Drive', 'Opsional. Jika diisi, gunakan URL lengkap diawali https:// (misal link Google Drive atau YouTube).'],
        ], null, 'A1');
        $this->styleHeader($sheet4, 'A1:B1');
        $sheet4->getColumnDimension('A')->setWidth(28);
        $sheet4->getColumnDimension('B')->setWidth(90);
        $sheet4->getStyle('A2:B10')->getAlignment()->setWrapText(true);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * @return array{
     *     cp: array<string, mixed>,
     *     objectives: array<int, array<string, mixed>>,
     *     lesson_plans: array<int, array<string, mixed>>,
     *     errors: array<int, string>
     * }
     */
    public function parseUploadedFile(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $errors = [];

        // 1. Parse CP_Prota_KKTP
        $cpSheet = $spreadsheet->getSheetByName('CP_Prota_KKTP');
        $cpData = [];
        if ($cpSheet) {
            foreach ($cpSheet->toArray(null, true, true, false) as $index => $row) {
                if ($index === 0) {
                    continue;
                }
                $key = trim((string) ($row[0] ?? ''));
                $val = $row[1] ?? null;
                if ($key !== '') {
                    $cpData[$key] = is_string($val) ? trim($val) : $val;
                }
            }
        } else {
            $errors[] = 'Sheet "CP_Prota_KKTP" tidak ditemukan pada file Excel.';
        }

        $kktpType = strtolower(trim((string) ($cpData['Pendekatan_KKTP'] ?? 'interval')));
        if (! in_array($kktpType, ['interval', 'deskripsi', 'rubrik'], true)) {
            $errors[] = 'Pendekatan_KKTP pada sheet CP_Prota_KKTP harus bernilai "interval", "deskripsi", atau "rubrik".';
        }

        $minScore = (int) ($cpData['Skor_Minimal_KKTP'] ?? 75);
        if ($minScore < 0 || $minScore > 100) {
            $errors[] = 'Skor_Minimal_KKTP harus berada di antara 0 sampai 100.';
        }

        $cp = [
            'title' => (string) ($cpData['Judul_Perangkat'] ?? ''),
            'phase' => (string) ($cpData['Fase'] ?? ''),
            'capaian_pembelajaran' => (string) ($cpData['Capaian_Pembelajaran'] ?? ''),
            'effective_weeks' => is_numeric($cpData['Minggu_Efektif'] ?? null) ? (int) $cpData['Minggu_Efektif'] : null,
            'total_jp' => is_numeric($cpData['Target_Total_JP'] ?? null) ? (int) $cpData['Target_Total_JP'] : null,
            'kktp_type' => $kktpType,
            'kktp_min_score' => $minScore,
            'kktp_notes' => (string) ($cpData['Pedoman_KKTP'] ?? ''),
        ];

        // 2. Parse ATP
        $objectives = [];
        $atpSheet = $spreadsheet->getSheetByName('ATP');
        if ($atpSheet) {
            foreach ($atpSheet->toArray(null, true, true, false) as $index => $row) {
                if ($index === 0) {
                    continue;
                }
                $code = trim((string) ($row[0] ?? ''));
                $title = trim((string) ($row[1] ?? ''));
                $desc = trim((string) ($row[2] ?? ''));
                $jpRaw = $row[3] ?? null;
                $month = trim((string) ($row[4] ?? ''));

                if ($code === '' && $title === '' && $desc === '') {
                    continue;
                }

                $rowNum = $index + 1;
                if ($code === '' || $title === '') {
                    $errors[] = "Sheet ATP baris {$rowNum}: Kode_TP dan Tujuan_Pembelajaran wajib diisi.";

                    continue;
                }

                if (! is_numeric($jpRaw) || (int) $jpRaw < 1) {
                    $errors[] = "Sheet ATP baris {$rowNum} ({$code}): Alokasi_JP harus berupa angka minimal 1.";

                    continue;
                }

                $objectives[] = [
                    'code' => $code,
                    'title' => $title,
                    'description' => $desc !== '' ? $desc : null,
                    'jp_allocation' => (int) $jpRaw,
                    'target_month' => $month !== '' ? $month : null,
                ];
            }
        } else {
            $errors[] = 'Sheet "ATP" tidak ditemukan pada file Excel.';
        }

        // 3. Parse Modul_Ajar
        $lessonPlans = [];
        $modulSheet = $spreadsheet->getSheetByName('Modul_Ajar');
        if ($modulSheet) {
            foreach ($modulSheet->toArray(null, true, true, false) as $index => $row) {
                if ($index === 0) {
                    continue;
                }
                $title = trim((string) ($row[0] ?? ''));
                $tpCode = trim((string) ($row[1] ?? ''));
                $goals = trim((string) ($row[2] ?? ''));
                $activities = trim((string) ($row[3] ?? ''));
                $assessment = trim((string) ($row[4] ?? ''));
                $url = trim((string) ($row[5] ?? ''));

                if ($title === '' && $goals === '' && $activities === '') {
                    continue;
                }

                $rowNum = $index + 1;
                if ($title === '') {
                    $errors[] = "Sheet Modul_Ajar baris {$rowNum}: Judul_Modul_Ajar wajib diisi.";

                    continue;
                }

                if ($url !== '' && ! filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = "Sheet Modul_Ajar baris {$rowNum} ({$title}): Format Tautan_Video_atau_Drive tidak valid (harus diawali https://).";

                    continue;
                }

                $lessonPlans[] = [
                    'title' => $title,
                    'tp_code' => $tpCode !== '' ? $tpCode : null,
                    'learning_goals' => $goals !== '' ? $goals : null,
                    'activities' => $activities !== '' ? $activities : null,
                    'assessment_plan' => $assessment !== '' ? $assessment : null,
                    'external_url' => $url !== '' ? $url : null,
                ];
            }
        } else {
            $errors[] = 'Sheet "Modul_Ajar" tidak ditemukan pada file Excel.';
        }

        return [
            'cp' => $cp,
            'objectives' => $objectives,
            'lesson_plans' => $lessonPlans,
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
