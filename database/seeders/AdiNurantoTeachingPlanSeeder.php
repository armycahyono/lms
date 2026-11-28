<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\LearningObjective;
use App\Models\LessonPlan;
use App\Models\Subject;
use App\Models\TeachingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdiNurantoTeachingPlanSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('username', 'adinuranto')->first();
        $year = AcademicYear::where('is_active', true)->first();
        $subject = Subject::where('name', 'Bahasa Indonesia')->first();

        if (! $teacher || ! $year || ! $subject) {
            return;
        }

        // 1. Bahasa Indonesia Kelas 10 (Fase E) - untuk paralel X TKR 1 & X TKR 2
        $plan10 = TeachingPlan::updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'grade_level' => 10,
                'teacher_id' => $teacher->id,
            ],
            [
                'phase' => 'Fase E',
                'title' => 'Perangkat Ajar Bahasa Indonesia Kelas X (Fase E)',
                'capaian_pembelajaran' => 'Peserta didik memiliki kemampuan berbahasa untuk berkomunikasi dan bernalar sesuai dengan tujuan, konteks sosial, akademis, dan dunia kerja. Peserta didik mampu memahami, mengolah, menginterpretasi, dan mengevaluasi informasi dari berbagai tipe teks (Laporan Hasil Observasi, Anekdot, Hikayat, Negosiasi, Biografi, dan Puisi).',
                'effective_weeks' => 36,
                'total_jp' => 108,
                'kktp_type' => 'interval',
                'kktp_min_score' => 75,
                'kktp_notes' => '0-59: Belum Mencapai (Remedial seluruh bagian) | 60-74: Belum Mencapai (Remedial bagian tertentu) | 75-89: Mencapai KKTP | 90-100: Melampaui KKTP (Pengayaan).',
            ]
        );

        $objectives10 = [
            [
                'code' => 'TP-10.1',
                'title' => 'Mengevaluasi informasi dan struktur Teks Laporan Hasil Observasi (LHO)',
                'description' => 'Mengidentifikasi fakta, opini, struktur pernyataan umum-deskripsi bagian, serta menyusun teks LHO bertema bengkel/otomotif.',
                'jp_allocation' => 12,
                'target_month' => 'Juli - Agustus',
                'sort_order' => 1,
            ],
            [
                'code' => 'TP-10.2',
                'title' => 'Menganalisis pesan tersirat dan kaidah kebahasaan Teks Anekdot',
                'description' => 'Menelaah kritik sosial dalam teks anekdot dan memproduksi komik/teks anekdot kreatif.',
                'jp_allocation' => 12,
                'target_month' => 'September - Oktober',
                'sort_order' => 2,
            ],
            [
                'code' => 'TP-10.3',
                'title' => 'Merancang dan mempraktikkan Teks Negosiasi di dunia kerja',
                'description' => 'Menerapkan pola pengajuan, penawaran, dan persetujuan dalam simulasi negosiasi layanan bengkel/wirausaha.',
                'jp_allocation' => 12,
                'target_month' => 'Januari - Februari',
                'sort_order' => 3,
            ],
        ];

        $savedObjectives10 = [];
        foreach ($objectives10 as $obj) {
            $savedObjectives10[$obj['code']] = LearningObjective::updateOrCreate(
                ['teaching_plan_id' => $plan10->id, 'code' => $obj['code']],
                $obj
            );
        }

        LessonPlan::updateOrCreate(
            ['teaching_plan_id' => $plan10->id, 'title' => 'Modul Ajar 1: Mengungkap Fakta Alam & Bengkel dalam Teks LHO'],
            [
                'learning_objective_id' => $savedObjectives10['TP-10.1']->id,
                'learning_goals' => 'Siswa mampu membedakan kalimat fakta dan opini serta menyusun kerangka teks LHO hasil pengamatan peralatan praktik TKR.',
                'activities' => 'Pendahuluan: Apersepsi video bengkel otomotif & pertanyaan pemantik. Inti: Diskusi kelompok menganalisis contoh teks LHO dan observasi komponen bengkel. Penutup: Refleksi & umpan balik.',
                'assessment_plan' => 'Asesmen Formatif: LKPD identifikasi struktur LHO dan rubrik penulisan teks LHO.',
                'sort_order' => 1,
            ]
        );

        LessonPlan::updateOrCreate(
            ['teaching_plan_id' => $plan10->id, 'title' => 'Modul Ajar 2: Negosiasi Efektif di Dunia Usaha & Industri'],
            [
                'learning_objective_id' => $savedObjectives10['TP-10.3']->id,
                'learning_goals' => 'Siswa mampu menyusun dialog negosiasi layanan jasa servis kendaraan dengan bahasa santun dan persuasif.',
                'activities' => 'Pendahuluan: Simulasi singkat tawar-menawar. Inti: Bermain peran (roleplay) negosiasi antara mekanik/service advisor dan pelanggan. Penutup: Evaluasi antar kelompok.',
                'assessment_plan' => 'Asesmen Sumatif Praktik: Rubrik kelengkapan struktur negosiasi dan kesantunan berbahasa.',
                'sort_order' => 2,
            ]
        );

        // 2. Bahasa Indonesia Kelas 12 (Fase F) - untuk XII TKR 4
        $plan12 = TeachingPlan::updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'grade_level' => 12,
                'teacher_id' => $teacher->id,
            ],
            [
                'phase' => 'Fase F',
                'title' => 'Perangkat Ajar Bahasa Indonesia Kelas XII (Fase F)',
                'capaian_pembelajaran' => 'Peserta didik mampu mengevaluasi gagasan dan pandangan berdasarkan kaidah logika berpikir dari menyimak dan membaca berbagai tipe teks (Surat Lamaran Pekerjaan, Teks Editorial, Novel, Artikel Ilmiah, dan Teks Kritik/Esai) untuk kesiapan kerja dan studi lanjut.',
                'effective_weeks' => 32,
                'total_jp' => 96,
                'kktp_type' => 'interval',
                'kktp_min_score' => 75,
                'kktp_notes' => '0-59: Belum Mencapai (Remedial) | 60-74: Perlu Bimbingan Khusus | 75-89: Mencapai KKTP | 90-100: Sangat Kompeten (Pengayaan).',
            ]
        );

        $objectives12 = [
            [
                'code' => 'TP-12.1',
                'title' => 'Menyusun Surat Lamaran Pekerjaan & CV sesuai standar industri',
                'description' => 'Menganalisis sistematika, unsur kebahasaan EYD V, dan menulis surat lamaran kerja bidang teknik otomotif.',
                'jp_allocation' => 12,
                'target_month' => 'Juli - Agustus',
                'sort_order' => 1,
            ],
            [
                'code' => 'TP-12.2',
                'title' => 'Mengevaluasi isu aktual dalam Teks Editorial secara kritis',
                'description' => 'Membedakan fakta, opini redaksi, dan keberpihakan penulis dalam teks editorial media massa.',
                'jp_allocation' => 10,
                'target_month' => 'September - Oktober',
                'sort_order' => 2,
            ],
            [
                'code' => 'TP-12.3',
                'title' => 'Menulis Artikel Opini / Laporan PKL berbasis kaidah ilmiah populer',
                'description' => 'Menyusun artikel reflektif atau ringkasan laporan pengalaman kerja industri dengan argumen yang logis.',
                'jp_allocation' => 12,
                'target_month' => 'Januari - Februari',
                'sort_order' => 3,
            ],
        ];

        $savedObjectives12 = [];
        foreach ($objectives12 as $obj) {
            $savedObjectives12[$obj['code']] = LearningObjective::updateOrCreate(
                ['teaching_plan_id' => $plan12->id, 'code' => $obj['code']],
                $obj
            );
        }

        LessonPlan::updateOrCreate(
            ['teaching_plan_id' => $plan12->id, 'title' => 'Modul Ajar 1: Menembus Dunia Kerja melalui Surat Lamaran Pekerjaan'],
            [
                'learning_objective_id' => $savedObjectives12['TP-12.1']->id,
                'learning_goals' => 'Siswa mampu menulis surat lamaran pekerjaan yang sistematis, bebas kesalahan ejaan, dan sesuai lowongan industri otomotif.',
                'activities' => 'Pendahuluan: Bedah contoh lowongan kerja DUDI. Inti: Analisis kesalahan umum surat lamaran, praktik menulis surat lamaran + CV, dan peer review. Penutup: Refleksi kesiapan kerja.',
                'assessment_plan' => 'Asesmen Formatif & Sumatif: Rubrik ketepatan sistematika (9 unsur), diksi baku, dan PUEBI/EYD V.',
                'sort_order' => 1,
            ]
        );

        LessonPlan::updateOrCreate(
            ['teaching_plan_id' => $plan12->id, 'title' => 'Modul Ajar 2: Berpikir Kritis melalui Teks Editorial'],
            [
                'learning_objective_id' => $savedObjectives12['TP-12.2']->id,
                'learning_goals' => 'Siswa mampu mengidentifikasi argumen pro-kontra serta rekomendasi penulis dalam teks editorial.',
                'activities' => 'Pendahuluan: Membaca tajuk rencana terkini. Inti: Diskusi kelompok memetakan fakta vs opini dalam editorial. Penutup: Presentasi kesimpulan.',
                'assessment_plan' => 'Asesmen Formatif: Kuis pemahaman teks dan tugas analisis editorial.',
                'sort_order' => 2,
            ]
        );
    }
}
