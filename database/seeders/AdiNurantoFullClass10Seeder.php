<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\CbtExam;
use App\Models\Classroom;
use App\Models\Course;
use App\Models\CourseModule;
use App\Models\LearningObjective;
use App\Models\LessonPlan;
use App\Models\Material;
use App\Models\QuestionBank;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\Subject;
use App\Models\TeachingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdiNurantoFullClass10Seeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::where('username', 'adinuranto')->first();
        $year = AcademicYear::where('is_active', true)->first();
        $subject = Subject::where('name', 'Bahasa Indonesia')->first();

        if (! $teacher || ! $year || ! $subject) {
            return;
        }

        // 1. Master Perangkat Ajar Kelas 10 (Fase E)
        $plan = TeachingPlan::updateOrCreate(
            [
                'academic_year_id' => $year->id,
                'subject_id' => $subject->id,
                'grade_level' => 10,
                'teacher_id' => $teacher->id,
            ],
            [
                'phase' => 'Fase E',
                'title' => 'Perangkat Ajar Bahasa Indonesia Kelas X (Fase E)',
                'capaian_pembelajaran' => 'Peserta didik memiliki kemampuan berbahasa untuk berkomunikasi dan bernalar sesuai dengan tujuan, konteks sosial, akademis, dan dunia kerja (khususnya bidang teknik otomotif). Peserta didik mampu menyimak, membaca, memirsa, berbicara, mempresentasikan, dan menulis berbagai tipe teks (Laporan Hasil Observasi, Anekdot, Hikayat, Negosiasi, Biografi, dan Puisi) secara kritis, kreatif, dan santun.',
                'effective_weeks' => 36,
                'total_jp' => 108,
                'kktp_type' => 'interval',
                'kktp_min_score' => 75,
                'kktp_notes' => '0-59: Belum Mencapai KKTP (Remedial seluruh bagian) | 60-74: Belum Mencapai KKTP (Remedial bagian tertentu) | 75-89: Mencapai KKTP (Tuntas) | 90-100: Melampaui KKTP (Pengayaan).',
            ]
        );

        // 2. ATP (6 Tujuan Pembelajaran - Total 108 JP)
        $objectivesData = [
            [
                'code' => 'TP-10.1',
                'title' => 'Mengevaluasi informasi, fakta-opini, serta menyusun Teks Laporan Hasil Observasi (LHO) bertema lingkungan bengkel & teknologi otomotif.',
                'description' => 'Mengidentifikasi fakta, opini, struktur pernyataan umum-deskripsi bagian-deskripsi manfaat, serta menulis teks LHO bertema peralatan bengkel TKR.',
                'jp_allocation' => 18,
                'target_month' => 'Juli - Agustus',
                'sort_order' => 1,
            ],
            [
                'code' => 'TP-10.2',
                'title' => 'Menganalisis pesan tersirat, kritik sosial, dan kaidah kebahasaan Teks Anekdot serta memproduksi anekdot kreatif.',
                'description' => 'Menelaah struktur abstraksi-orientasi-krisis-reaksi-koda serta memproduksi teks/komik anekdot layanan publik yang santun.',
                'jp_allocation' => 16,
                'target_month' => 'September - Oktober',
                'sort_order' => 2,
            ],
            [
                'code' => 'TP-10.3',
                'title' => 'Menginterpretasi nilai-nilai luhur dan karakter dalam Hikayat serta mengadaptasinya ke dalam teks narasi modern.',
                'description' => 'Mengidentifikasi karakteristik hikayat dan mengalihwahanakan nilai keteladanan hikayat ke bentuk cerpen modern.',
                'jp_allocation' => 16,
                'target_month' => 'November',
                'sort_order' => 3,
            ],
            [
                'code' => 'TP-10.4',
                'title' => 'Merancang dan mempraktikkan Teks Negosiasi efektif dalam konteks layanan bengkel dan kewirausahaan.',
                'description' => 'Menerapkan pola orientasi-pengajuan-penawaran-persetujuan-penutup dalam simulasi negosiasi pelanggan bengkel.',
                'jp_allocation' => 20,
                'target_month' => 'Januari - Februari',
                'sort_order' => 4,
            ],
            [
                'code' => 'TP-10.5',
                'title' => 'Menganalisis keteladanan tokoh industri/nasional dalam Teks Biografi dan menulis biografi tokoh inspiratif.',
                'description' => 'Menelaah struktur orientasi-peristiwa penting-reorientasi dan menulis biografi singkat tokoh otomotif/wirausaha.',
                'jp_allocation' => 18,
                'target_month' => 'Maret - April',
                'sort_order' => 5,
            ],
            [
                'code' => 'TP-10.6',
                'title' => 'Mengapresiasi diksi, majas, dan makna Puisi serta membacakan/menulis puisi bertema etos kerja.',
                'description' => 'Menganalisis citraan, diksi, majas, dan mempresentasikan pembacaan puisi dengan intonasi dan ekspresi tepat.',
                'jp_allocation' => 20,
                'target_month' => 'Mei',
                'sort_order' => 6,
            ],
        ];

        $objectives = [];
        foreach ($objectivesData as $obj) {
            $objectives[$obj['code']] = LearningObjective::updateOrCreate(
                ['teaching_plan_id' => $plan->id, 'code' => $obj['code']],
                $obj
            );
        }

        // 3. 6 RPP / Modul Ajar
        $lessonPlansData = [
            [
                'code' => 'TP-10.1',
                'title' => 'Modul Ajar 1: Mengungkap Fakta Bengkel dalam Teks LHO',
                'learning_goals' => 'Siswa mampu membedakan kalimat fakta dan opini serta menyusun teks LHO hasil pengamatan peralatan praktik TKR.',
                'activities' => 'Pendahuluan: Apersepsi video bengkel otomotif & pertanyaan pemantik. Inti: Observasi langsung alat bengkel, diskusi kelompok memetakan pernyataan umum, deskripsi bagian, dan deskripsi manfaat. Penutup: Refleksi & umpan balik.',
                'assessment_plan' => 'Diagnostik awal + LKPD 1 + Tugas Menulis Teks LHO.',
                'sort_order' => 1,
            ],
            [
                'code' => 'TP-10.2',
                'title' => 'Modul Ajar 2: Menyampaikan Kritik Santun Lewat Teks Anekdot',
                'learning_goals' => 'Siswa mampu menelaah struktur abstraksi-orientasi-krisis-reaksi-koda dan menulis teks anekdot bertema disiplin.',
                'activities' => 'Pendahuluan: Mengamati komik/cerita humor layanan publik. Inti: Analisis struktur & kaidah kalimat retoris, lalu menyusun teks anekdot kreatif. Penutup: Saling review karya.',
                'assessment_plan' => 'Kuis Formatif Anekdot + Tugas Menyusun Teks Anekdot.',
                'sort_order' => 2,
            ],
            [
                'code' => 'TP-10.3',
                'title' => 'Modul Ajar 3: Menggali Kearifan Lokal dalam Hikayat',
                'learning_goals' => 'Siswa mampu mengidentifikasi nilai moral dalam hikayat dan mengalihwahanakannya menjadi cerpen modern.',
                'activities' => 'Pendahuluan: Membaca petikan Hikayat Indera Bangsawan. Inti: Membandingkan nilai budaya masa lalu dengan etos kerja masa kini, menulis draf alih wahana cerpen. Penutup: Refleksi nilai karakter.',
                'assessment_plan' => 'Tugas Alih Wahana Hikayat ke Cerpen.',
                'sort_order' => 3,
            ],
            [
                'code' => 'TP-10.4',
                'title' => 'Modul Ajar 4: Negosiasi Profesional di Dunia Kerja',
                'learning_goals' => 'Siswa mampu menyusun naskah dan mempraktikkan negosiasi layanan servis kendaraan dengan bahasa santun dan persuasif.',
                'activities' => 'Pendahuluan: Simulasi singkat tawar-menawar. Inti: Bermain peran (roleplay) negosiasi antara service advisor dan pelanggan bengkel. Penutup: Evaluasi antar kelompok.',
                'assessment_plan' => 'Tugas Sumatif Praktik Roleplay Negosiasi + Kuis Negosiasi.',
                'sort_order' => 4,
            ],
            [
                'code' => 'TP-10.5',
                'title' => 'Modul Ajar 5: Meneladani Tokoh Inspiratif dalam Teks Biografi',
                'learning_goals' => 'Siswa mampu menelaah karakter unggul tokoh dan menulis biografi singkat tokoh otomotif atau wirausaha lokal.',
                'activities' => 'Pendahuluan: Membaca kutipan biografi B.J. Habibie / Soichiro Honda. Inti: Riset pustaka/wawancara tokoh inspiratif dan penulisan biografi sesuai EYD. Penutup: Presentasi singkat.',
                'assessment_plan' => 'Tugas Menulis Teks Biografi + Kuis Formatif.',
                'sort_order' => 5,
            ],
            [
                'code' => 'TP-10.6',
                'title' => 'Modul Ajar 6: Mengekspresikan Gagasan melalui Puisi',
                'learning_goals' => 'Siswa mampu menganalisis diksi, majas, dan citraan puisi serta membacakan puisi bertema etos kerja dengan tepat.',
                'activities' => 'Pendahuluan: Menyimak video deklamasi puisi. Inti: Bedah diksi & suasana puisi, menulis puisi tema kerja keras, dan latihan vokal/intonasi. Penutup: Unjuk kerja baca puisi.',
                'assessment_plan' => 'Tugas Sumatif Mencipta & Membacakan Puisi + Ujian PAS CBT.',
                'sort_order' => 6,
            ],
        ];

        foreach ($lessonPlansData as $lp) {
            LessonPlan::updateOrCreate(
                ['teaching_plan_id' => $plan->id, 'title' => $lp['title']],
                [
                    'learning_objective_id' => $objectives[$lp['code']]->id,
                    'learning_goals' => $lp['learning_goals'],
                    'activities' => $lp['activities'],
                    'assessment_plan' => $lp['assessment_plan'],
                    'sort_order' => $lp['sort_order'],
                ]
            );
        }

        // 4. Bank Soal (15 Soal + Kisi-kisi)
        $bankQuestionsData = [
            [
                'category' => 'Diagnostik Awal',
                'kisi_indicator' => 'Membedakan kalimat fakta dan opini dalam paragraf informasi.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Manakah di antara kalimat berikut yang merupakan kalimat fakta?',
                'options_json' => ['A' => 'Bengkel sekolah kita terasa sangat nyaman.', 'B' => 'Mesin 4-tak bekerja melalui empat langkah piston.', 'C' => 'Pelayanan bengkel itu sebaiknya lebih cepat.', 'D' => 'Pekerjaan mekanik adalah profesi paling menarik.'],
                'correct_answer' => 'B',
                'points' => 20,
            ],
            [
                'category' => 'Diagnostik Awal',
                'kisi_indicator' => 'Menggunakan ejaan dan tanda baca baku sesuai EYD V.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Penulisan kata baku yang tepat dalam laporan bengkel adalah...',
                'options_json' => ['A' => 'tekhnik', 'B' => 'analisa', 'C' => 'praktik', 'D' => 'sistemik'],
                'correct_answer' => 'C',
                'points' => 20,
            ],
            [
                'category' => 'Bab 1: Teks LHO',
                'kisi_indicator' => 'Mengidentifikasi struktur bagian pembuka (pernyataan umum) teks LHO.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Bagian teks LHO yang berisi definisi umum atau klasifikasi objek yang diamati disebut...',
                'options_json' => ['A' => 'Pernyataan umum', 'B' => 'Deskripsi bagian', 'C' => 'Deskripsi manfaat', 'D' => 'Orientasi'],
                'correct_answer' => 'A',
                'points' => 10,
            ],
            [
                'category' => 'Bab 1: Teks LHO',
                'kisi_indicator' => 'Menentukan kalimat definisi dalam teks LHO.',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Kalimat yang menggunakan verba penghubung "adalah" atau "merupakan" pada teks LHO berfungsi sebagai...',
                'options_json' => ['A' => 'Kalimat perintah', 'B' => 'Kalimat definisi', 'C' => 'Kalimat retoris', 'D' => 'Kalimat seru'],
                'correct_answer' => 'B',
                'points' => 10,
            ],
            [
                'category' => 'Bab 1: Teks LHO',
                'kisi_indicator' => 'Mengidentifikasi sifat objektif teks laporan hasil observasi.',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Salah satu ciri utama teks Laporan Hasil Observasi adalah bersifat objektif, artinya...',
                'options_json' => ['A' => 'Berdasarkan imajinasi penulis', 'B' => 'Sesuai data pengamatan tanpa prasangka', 'C' => 'Mengajak pembaca membeli produk', 'D' => 'Mengandung unsur humor'],
                'correct_answer' => 'B',
                'points' => 10,
            ],
            [
                'category' => 'Bab 2: Teks Anekdot',
                'kisi_indicator' => 'Mengidentifikasi tujuan komunikatif teks anekdot.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Tujuan utama dari teks anekdot adalah...',
                'options_json' => ['A' => 'Melaporkan hasil pengamatan ilmiah', 'B' => 'Menyampaikan kritik sosial secara humoris dan santun', 'C' => 'Menawarkan barang atau jasa', 'D' => 'Menceritakan riwayat hidup tokoh'],
                'correct_answer' => 'B',
                'points' => 10,
            ],
            [
                'category' => 'Bab 2: Teks Anekdot',
                'kisi_indicator' => 'Menentukan bagian krisis dalam struktur teks anekdot.',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Bagian pada teks anekdot yang memunculkan masalah atau hal unik/menggelitik disebut...',
                'options_json' => ['A' => 'Abstraksi', 'B' => 'Orientasi', 'C' => 'Krisis', 'D' => 'Koda'],
                'correct_answer' => 'C',
                'points' => 10,
            ],
            [
                'category' => 'Bab 2: Teks Anekdot',
                'kisi_indicator' => 'Mengidentifikasi kalimat retoris dalam teks anekdot.',
                'difficulty' => 'sulit',
                'type' => 'multiple_choice',
                'question_text' => 'Kalimat "Apakah kamu mau nilai bagus tanpa pernah belajar?" termasuk kaidah kebahasaan anekdot berupa...',
                'options_json' => ['A' => 'Kalimat retoris', 'B' => 'Kalimat definisi', 'C' => 'Konjungsi kausalitas', 'D' => 'Kalimat pasif'],
                'correct_answer' => 'A',
                'points' => 10,
            ],
            [
                'category' => 'Bab 3: Hikayat',
                'kisi_indicator' => 'Mengidentifikasi karakteristik kemustahilan dan kesaktian dalam hikayat.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Ciri khas hikayat yang membedakannya dengan cerpen modern adalah...',
                'options_json' => ['A' => 'Menggunakan bahasa baku EYD V', 'B' => 'Istanasentris dan mengandung kemustahilan', 'C' => 'Berlatar dunia industri modern', 'D' => 'Selalu mencantumkan nama pengarang'],
                'correct_answer' => 'B',
                'points' => 10,
            ],
            [
                'category' => 'Bab 4: Teks Negosiasi',
                'kisi_indicator' => 'Menentukan inti bagian pengajuan dan penawaran dalam teks negosiasi.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Proses tawar-menawar antara penyedia jasa bengkel dan pelanggan untuk mencapai kesepakatan terjadi pada tahap...',
                'options_json' => ['A' => 'Orientasi', 'B' => 'Pengajuan dan Penawaran', 'C' => 'Penutup', 'D' => 'Abstraksi'],
                'correct_answer' => 'B',
                'points' => 10,
            ],
            [
                'category' => 'Bab 4: Teks Negosiasi',
                'kisi_indicator' => 'Menerapkan prinsip kesantunan berbahasa dalam negosiasi.',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Agar negosiasi berjalan lancar dan saling menguntungkan (win-win solution), bahasa yang digunakan harus...',
                'options_json' => ['A' => 'Memaksa dan meninggikan suara', 'B' => 'Santun, persuasif, dan berdasar alasan logis', 'C' => 'Bertele-tele dan ambigu', 'D' => 'Menggunakan istilah asing seluruhnya'],
                'correct_answer' => 'B',
                'points' => 10,
            ],
            [
                'category' => 'Bab 5: Teks Biografi',
                'kisi_indicator' => 'Mengidentifikasi struktur orientasi, peristiwa penting, dan reorientasi biografi.',
                'difficulty' => 'mudah',
                'type' => 'multiple_choice',
                'question_text' => 'Bagian teks biografi yang berisi pandangan atau simpulan penulis mengenai keteladanan tokoh disebut...',
                'options_json' => ['A' => 'Orientasi', 'B' => 'Peristiwa penting', 'C' => 'Reorientasi', 'D' => 'Komplikasi'],
                'correct_answer' => 'C',
                'points' => 10,
            ],
            [
                'category' => 'Bab 5: Teks Biografi',
                'kisi_indicator' => 'Menentukan kata ganti orang ketiga dalam teks biografi.',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Teks biografi umumnya menggunakan sudut pandang orang ketiga, yang ditandai dengan pronomina...',
                'options_json' => ['A' => 'Aku, saya', 'B' => 'Kamu, Anda', 'C' => 'Ia, dia, beliau', 'D' => 'Kita, kami'],
                'correct_answer' => 'C',
                'points' => 10,
            ],
            [
                'category' => 'Bab 6: Puisi',
                'kisi_indicator' => 'Menganalisis citraan dan majas dalam teks puisi.',
                'difficulty' => 'sedang',
                'type' => 'multiple_choice',
                'question_text' => 'Larik "Dentang kunci pas menyapa pagi di ruang praktik" mengandung majas...',
                'options_json' => ['A' => 'Personifikasi', 'B' => 'Hiperbola', 'C' => 'Ironi', 'D' => 'Litotes'],
                'correct_answer' => 'A',
                'points' => 10,
            ],
            [
                'category' => 'Bab 1: Teks LHO',
                'kisi_indicator' => 'Menyusun contoh kalimat deskripsi manfaat alat praktik bengkel.',
                'difficulty' => 'sulit',
                'type' => 'essay',
                'question_text' => 'Tuliskan 1 paragraf singkat deskripsi manfaat salah satu alat bengkel otomotif yang Anda ketahui!',
                'options_json' => null,
                'correct_answer' => null,
                'points' => 20,
            ],
        ];

        $savedBank = [];
        foreach ($bankQuestionsData as $bq) {
            $savedBank[] = QuestionBank::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                    'question_text' => $bq['question_text'],
                ],
                $bq
            );
        }

        // 5. Terapkan ke Kedua Kelas Paralel Kelas 10 (X TKR 1 & X TKR 2)
        $courses = Course::query()
            ->where('academic_year_id', $year->id)
            ->where('subject_id', $subject->id)
            ->whereHas('teachers', fn ($q) => $q->where('users.id', $teacher->id))
            ->with('classroom')
            ->get()
            ->filter(fn (Course $c) => $c->classroom && Classroom::parseGradeNumber($c->classroom->grade_level, $c->classroom->name) === 10);

        $modulesSpec = [
            [
                'title' => 'Bab 1: Teks Laporan Hasil Observasi (LHO)',
                'description' => 'Mengenal struktur, fakta-opini, dan penyusunan laporan observasi lingkungan bengkel.',
                'materials' => [
                    ['title' => 'Konsep & Struktur Teks LHO', 'category' => 'materi', 'type' => 'text', 'content' => "Teks Laporan Hasil Observasi (LHO) menyajikan informasi objektif berdasarkan pengamatan langsung.\n\nStruktur utama:\n1. Pernyataan Umum (definisi & klasifikasi)\n2. Deskripsi Bagian (rincian bagian/komponen)\n3. Deskripsi Manfaat (kegunaan objek yang diamati)."],
                    ['title' => 'LKPD 1: Observasi Peralatan Bengkel TKR', 'category' => 'lkpd', 'type' => 'text', 'content' => "Petunjuk LKPD:\n1. Pilih 1 peralatan atau unit praktik di bengkel TKR (mis. Dongkrak Hidrolik, Kunci Torsi, atau Mesin 4-Tak).\n2. Catat 5 fakta hasil pengamatan fisik dan fungsinya.\n3. Susun menjadi kerangka Pernyataan Umum, Deskripsi Bagian, dan Deskripsi Manfaat."],
                    ['title' => 'Kaidah Kebahasaan LHO (Kalimat Definisi & Frasa Nomina)', 'category' => 'buku', 'type' => 'text', 'content' => 'Pelajari penggunaan verba relasional (adalah, merupakan, ialah), kalimat deskripsi, serta penulisan istilah teknik otomotif sesuai EYD V.'],
                ],
            ],
            [
                'title' => 'Bab 2: Teks Anekdot',
                'description' => 'Menelaah kritik sosial yang santun dan humoris melalui struktur teks anekdot.',
                'materials' => [
                    ['title' => 'Struktur & Unsur Kebahasaan Anekdot', 'category' => 'materi', 'type' => 'text', 'content' => "Struktur Anekdot:\n1. Abstraksi\n2. Orientasi\n3. Krisis (inti kelucuan/masalah)\n4. Reaksi\n5. Koda."],
                    ['title' => 'LKPD 2: Menyusun Kritik Santun dalam Anekdot', 'category' => 'lkpd', 'type' => 'text', 'content' => 'Buatlah draf teks anekdot 5 bagian bertema kedisiplinan penggunaan APD (Alat Pelindung Diri) di bengkel sekolah.'],
                ],
            ],
            [
                'title' => 'Bab 3: Hikayat & Cerpen',
                'description' => 'Menggali nilai kearifan lokal hikayat dan alih wahana ke cerpen modern.',
                'materials' => [
                    ['title' => 'Karakteristik & Nilai Kehidupan dalam Hikayat', 'category' => 'materi', 'type' => 'text', 'content' => 'Hikayat bercirikan istanasentris, kemustahilan, kesaktian tokoh, dan anonim, namun sarat nilai moral, kejujuran, serta kerja keras.'],
                    ['title' => 'LKPD 3: Alih Wahana Hikayat ke Cerpen Modern', 'category' => 'lkpd', 'type' => 'text', 'content' => 'Pilih satu nilai keteladanan dari Hikayat Indera Bangsawan, lalu ubah menjadi cerita pendek berlatar kehidupan siswa SMK masa kini.'],
                ],
            ],
            [
                'title' => 'Bab 4: Teks Negosiasi',
                'description' => 'Menerapkan komunikasi persuasif dan negosiasi layanan bengkel yang saling menguntungkan.',
                'materials' => [
                    ['title' => 'Struktur & Strategi Negosiasi Dunia Usaha', 'category' => 'materi', 'type' => 'text', 'content' => 'Negosiasi bertujuan mencapai kesepakatan bersama (win-win solution) melalui tahap Orientasi, Pengajuan, Penawaran, Persetujuan, dan Penutup.'],
                    ['title' => 'LKPD 4: Skenario Negosiasi Service Advisor & Pelanggan', 'category' => 'lkpd', 'type' => 'text', 'content' => 'Susun dialog negosiasi estimasi biaya dan waktu perbaikan rem kendaraan antara Service Advisor dan pemilik mobil.'],
                ],
            ],
            [
                'title' => 'Bab 5: Teks Biografi',
                'description' => 'Meneladani perjalanan hidup tokoh inspiratif industri dan nasional.',
                'materials' => [
                    ['title' => 'Pola Penyajian & Keteladanan Tokoh dalam Biografi', 'category' => 'materi', 'type' => 'text', 'content' => 'Biografi memuat Orientasi (latar belakang tokoh), Peristiwa Penting (perjuangan & prestasi), dan Reorientasi (simpulan keteladanan).'],
                    ['title' => 'LKPD 5: Menulis Biografi Tokoh Inspiratif', 'category' => 'lkpd', 'type' => 'text', 'content' => 'Tuliskan biografi singkat tokoh otomotif (mis. Soichiro Honda) atau tokoh wirausaha sukses di lingkungan sekitar Anda.'],
                ],
            ],
            [
                'title' => 'Bab 6: Apresiasi Puisi',
                'description' => 'Memahami diksi, majas, citraan, dan teknik deklamasi puisi bertema etos kerja.',
                'materials' => [
                    ['title' => 'Diksi, Majas, Citraan, & Teknik Deklamasi Puisi', 'category' => 'materi', 'type' => 'text', 'content' => 'Perhatikan pilihan kata (diksi), gaya bahasa (majas), pengindraan (citraan), serta olah vokal, intonasi, dan ekspresi saat membaca puisi.'],
                    ['title' => 'LKPD 6: Menulis Puisi Bertema Etos Kerja', 'category' => 'lkpd', 'type' => 'text', 'content' => 'Ciptakan 1 puisi minimal 3 bait bertema perjuangan dan etos kerja siswa SMK, lalu persiapkan untuk dibacakan di kelas.'],
                ],
            ],
        ];

        $assignmentsSpec = [
            ['code' => 'TP-10.1', 'assessment_type' => 'formatif', 'title' => 'Tugas 1: Menulis Teks LHO Pengamatan Bengkel TKR', 'instructions' => 'Susun teks Laporan Hasil Observasi lengkap (Pernyataan Umum, Deskripsi Bagian, Deskripsi Manfaat) dari hasil pengamatan peralatan bengkel.', 'rubric_notes' => 'Ketepatan 3 struktur (30%), kelengkapan fakta/data (30%), kaidah kebahasaan & EYD (25%), kerapian (15%).'],
            ['code' => 'TP-10.2', 'assessment_type' => 'formatif', 'title' => 'Tugas 2: Menyusun Teks Anekdot Bertema Disiplin Bengkel', 'instructions' => 'Buat 1 teks anekdot lengkap 5 struktur yang mengandung pesan moral atau kritik santun tentang kedisiplinan kerja.', 'rubric_notes' => 'Kelengkapan 5 struktur (35%), pesan/sindiran santun (35%), kaidah kebahasaan (30%).'],
            ['code' => 'TP-10.3', 'assessment_type' => 'formatif', 'title' => 'Tugas 3: Alih Wahana Hikayat ke Cerpen Modern', 'instructions' => 'Adaptasikan amanat hikayat klasik menjadi cerita pendek berlatar lingkungan SMK atau dunia kerja modern.', 'rubric_notes' => 'Kesesuaian amanat (35%), kreativitas alur modern (35%), kebahasaan (30%).'],
            ['code' => 'TP-10.4', 'assessment_type' => 'sumatif', 'title' => 'Tugas 4: Naskah & Praktik Roleplay Negosiasi Layanan Bengkel', 'instructions' => 'Unggah naskah dialog negosiasi berpasangan antara Service Advisor dan pelanggan, lalu praktikkan di kelas.', 'rubric_notes' => 'Struktur pengajuan-penawaran-persetujuan (40%), kesantunan & persuasi (35%), kelancaran komunikasi (25%).'],
            ['code' => 'TP-10.5', 'assessment_type' => 'formatif', 'title' => 'Tugas 5: Menyusun Biografi Tokoh Otomotif / Wirausaha', 'instructions' => 'Tulis teks biografi tokoh inspiratif (minimal 3 paragraf) lengkap dengan orientasi, peristiwa penting, dan reorientasi.', 'rubric_notes' => 'Struktur lengkap (35%), keteladanan tokoh (35%), ketepatan EYD (30%).'],
            ['code' => 'TP-10.6', 'assessment_type' => 'sumatif', 'title' => 'Tugas 6: Mencipta & Membacakan Puisi Etos Kerja', 'instructions' => 'Unggah naskah puisi bertema etos kerja minimal 3 bait dan tampilkan pembacaannya.', 'rubric_notes' => 'Kekuatan diksi & majas (40%), vokal/intonasi/ekspresi (40%), kesesuaian tema (20%).'],
        ];

        $quizzesSpec = [
            [
                'title' => 'Kuis 0: Asesmen Diagnostik Awal Bahasa Indonesia X',
                'assessment_type' => 'diagnostik',
                'code' => 'TP-10.1',
                'duration_minutes' => 15,
                'categories' => ['Diagnostik Awal'],
            ],
            [
                'title' => 'Kuis 1: Pemahaman Teks LHO',
                'assessment_type' => 'formatif',
                'code' => 'TP-10.1',
                'duration_minutes' => 25,
                'categories' => ['Bab 1: Teks LHO'],
            ],
            [
                'title' => 'Kuis 2: Struktur & Kebahasaan Teks Anekdot',
                'assessment_type' => 'formatif',
                'code' => 'TP-10.2',
                'duration_minutes' => 25,
                'categories' => ['Bab 2: Teks Anekdot'],
            ],
            [
                'title' => 'Kuis 3: Teks Negosiasi, Biografi & Puisi',
                'assessment_type' => 'sumatif',
                'code' => 'TP-10.4',
                'duration_minutes' => 30,
                'categories' => ['Bab 4: Teks Negosiasi', 'Bab 5: Teks Biografi', 'Bab 6: Puisi'],
            ],
        ];

        $examsSpec = [
            ['title' => 'UH 1: Evaluasi Teks LHO & Anekdot', 'exam_type' => 'UH', 'duration_minutes' => 45, 'cbt_exam_code' => 'BIN-X-UH1'],
            ['title' => 'PTS: Penilaian Tengah Tahun Bahasa Indonesia X', 'exam_type' => 'PTS', 'duration_minutes' => 60, 'cbt_exam_code' => 'BIN-X-PTS'],
            ['title' => 'UH 2: Evaluasi Teks Negosiasi & Biografi', 'exam_type' => 'UH', 'duration_minutes' => 45, 'cbt_exam_code' => 'BIN-X-UH2'],
            ['title' => 'PAS: Asesmen Sumatif Akhir Tahun Bahasa Indonesia X', 'exam_type' => 'PAS', 'duration_minutes' => 90, 'cbt_exam_code' => 'BIN-X-PAS'],
        ];

        foreach ($courses as $course) {
            // Modul & Bahan Ajar
            foreach ($modulesSpec as $mIndex => $modData) {
                $module = CourseModule::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $modData['title']],
                    [
                        'description' => $modData['description'],
                        'sort_order' => $mIndex + 1,
                        'is_published' => true,
                    ]
                );

                foreach ($modData['materials'] as $matIndex => $matData) {
                    Material::updateOrCreate(
                        ['course_id' => $course->id, 'module_id' => $module->id, 'title' => $matData['title']],
                        [
                            'type' => $matData['type'],
                            'category' => $matData['category'],
                            'content' => $matData['content'],
                            'sort_order' => $matIndex + 1,
                            'is_published' => true,
                        ]
                    );
                }
            }

            // Tugas + Rubrik
            foreach ($assignmentsSpec as $aData) {
                Assignment::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $aData['title']],
                    [
                        'assessment_type' => $aData['assessment_type'],
                        'learning_objective_id' => $objectives[$aData['code']]->id,
                        'instructions' => $aData['instructions'],
                        'rubric_notes' => $aData['rubric_notes'],
                        'max_score' => 100,
                        'allow_late' => true,
                        'is_published' => true,
                    ]
                );
            }

            // Kuis + Impor Butir Soal
            foreach ($quizzesSpec as $qData) {
                $quiz = Quiz::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $qData['title']],
                    [
                        'assessment_type' => $qData['assessment_type'],
                        'learning_objective_id' => $objectives[$qData['code']]->id,
                        'duration_minutes' => $qData['duration_minutes'],
                        'max_attempts' => 2,
                        'shuffle_questions' => true,
                        'shuffle_options' => true,
                        'passing_score' => 75,
                        'quiz_type' => 'reguler',
                        'is_published' => true,
                    ]
                );

                $matchingQuestions = collect($savedBank)->whereIn('category', $qData['categories'])->values();
                foreach ($matchingQuestions as $qIdx => $bankItem) {
                    QuizQuestion::updateOrCreate(
                        ['quiz_id' => $quiz->id, 'question_text' => $bankItem->question_text],
                        [
                            'type' => $bankItem->type,
                            'options_json' => $bankItem->options_json,
                            'correct_answer' => $bankItem->correct_answer,
                            'points' => $bankItem->points,
                            'sort_order' => $qIdx + 1,
                        ]
                    );
                }
            }

            // Ujian CBTPGRI
            foreach ($examsSpec as $eData) {
                CbtExam::updateOrCreate(
                    ['course_id' => $course->id, 'title' => $eData['title']],
                    [
                        'cbt_exam_code' => $eData['cbt_exam_code'],
                        'exam_type' => $eData['exam_type'],
                        'duration_minutes' => $eData['duration_minutes'],
                        'is_published' => true,
                    ]
                );
            }
        }
    }
}
