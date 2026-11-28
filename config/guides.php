<?php

return [
    'dashboard' => [
        'title' => 'Panduan Beranda LMS',
        'summary' => 'Ringkasan aktivitas pembelajaran Anda pada Tahun Ajaran yang sedang dipilih di header.',
        'steps' => [
            ['title' => 'Periksa Tahun Ajaran di Header', 'desc' => 'Guru & Admin dapat mengganti Tahun Ajaran di kanan atas header untuk meninjau data tahun sebelumnya.'],
            ['title' => 'Pantau Tugas & Kuis', 'desc' => 'Gunakan kartu ringkasan untuk melihat tugas yang perlu dinilai atau aktivitas terbaru kelas.'],
            ['title' => 'Navigasi Cepat', 'desc' => 'Pilih menu di sidebar kiri untuk masuk ke Kelas Digital, Perangkat Ajar, atau Bank Soal.'],
        ],
        'tips' => [
            'Presensi tatap muka harian tetap dikelola di SIAP; LMS mencatat aktivitas pembelajaran digital.',
        ],
    ],

    'teaching-plans.*' => [
        'title' => 'Panduan Master Perangkat Ajar',
        'summary' => 'Susun 1 paket perangkat perencanaan (CP, Prota, KKTP, ATP, RPP/Modul Ajar) untuk setiap Mapel & Tingkat, lalu terbitkan ke semua kelas paralel sekaligus.',
        'steps' => [
            ['title' => 'Buat / Buka Perangkat Mapel', 'desc' => 'Klik "+ Buat / Buka Perangkat Mapel", pilih Mata Pelajaran dan Tingkat kelas yang Anda ampu.'],
            ['title' => 'Lengkapi CP, Prota & KKTP', 'desc' => 'Klik tombol "Edit CP, Prota & KKTP" di tab pertama untuk mengisi Capaian Pembelajaran, minggu efektif, total JP, dan batas minimal tuntas (KKTP).'],
            ['title' => 'Susun ATP & Modul Ajar', 'desc' => 'Di tab ATP, klik "+ Tambah Tujuan Pembelajaran" (kode TP, alokasi JP, periode). Lalu di tab RPP/Modul Ajar, kaitkan modul ajar ke TP tersebut.'],
            ['title' => 'Terbitkan ke Kelas Paralel', 'desc' => 'Buka tab "Terbitkan ke Kelas Paralel" lalu klik "+ Terbitkan Materi / LKPD Serentak" agar tidak perlu input ulang di tiap rombel.'],
        ],
        'tips' => [
            'Semua formulir terkunci (read-only) secara default untuk mencegah perubahan tidak disengaja. Gunakan tombol Edit / Tambah bila ingin mengubah.',
            'Daftar TP dan KKTP di sini otomatis terhubung ke Tugas, Kuis, dan Jurnal Mengajar di Kelas Digital.',
        ],
    ],

    'courses.index' => [
        'title' => 'Panduan Daftar Kelas Digital',
        'summary' => 'Menampilkan kelas digital yang Anda ampu serta kelas perwalian (jika Anda bertugas sebagai Wali Kelas).',
        'steps' => [
            ['title' => 'Bedakan Kelas Diampu & Perwalian', 'desc' => 'Gunakan tab "Kelas yang Diampu" untuk mengelola mapel Anda, dan tab "Kelas Sebagai Wali" untuk memantau seluruh mapel rombel perwalian.'],
            ['title' => 'Buka atau Pantau Kelas', 'desc' => 'Klik "Buka Kelas" untuk mengelola materi, tugas, kuis, dan jurnal pembelajaran.'],
        ],
        'tips' => [
            'Kelas berstatus DRAFT belum bisa dilihat siswa sampai Anda menekan "Buka Kelas & Auto-Enroll Siswa" di dalam halaman kelas.',
        ],
    ],

    'courses.show' => [
        'title' => 'Panduan Mengelola Kelas Digital',
        'summary' => 'Pusat pelaksanaan pembelajaran harian per rombel: modul & bahan ajar, tugas, kuis, diskusi, jurnal & kehadiran digital, serta KKTP & remedial.',
        'steps' => [
            ['title' => 'Aktifkan Kelas (Jika Masih Draft)', 'desc' => 'Klik "Buka Kelas & Auto-Enroll Siswa" di kanan atas agar seluruh siswa rombel otomatis masuk ke kelas.'],
            ['title' => 'Isi Modul, Tugas & Kuis', 'desc' => 'Gunakan panel di kanan untuk menambah Modul/Materi (pilih kategori Materi/LKPD/Buku), Tugas (pilih jenis Diagnostik/Formatif/Sumatif + Rubrik), dan Kuis.'],
            ['title' => 'Isi Jurnal & Buka Sesi Digital', 'desc' => 'Di tab "Jurnal & Kehadiran", pilih TP dari ATP, tulis ringkasan materi & refleksi, lalu klik "Simpan Jurnal & Buka Sesi".'],
            ['title' => 'Pantau KKTP & Catat Remedial', 'desc' => 'Di tab "KKTP & Remedial", lihat status tuntas siswa secara otomatis dan catat program remedial/pengayaan bagi siswa yang membutuhkan.'],
        ],
        'tips' => [
            'Kehadiran pembelajaran digital tercatat OTOMATIS saat siswa membuka kelas digital atau materi selama sesi aktif.',
            'Jika mengajar kelas paralel, gunakan menu Perangkat Ajar atau panel "Duplikasi Materi dari Kelas Lain" agar lebih cepat.',
        ],
    ],

    'question-bank.*' => [
        'title' => 'Panduan Bank Soal & Kisi-Kisi',
        'summary' => 'Simpan koleksi soal lintas kelas berdasarkan mata pelajaran lengkap dengan kisi-kisi/indikator untuk diimpor ke kuis.',
        'steps' => [
            ['title' => 'Tambah Soal + Kisi-Kisi', 'desc' => 'Pilih Mata Pelajaran, Bab, Tingkat Kesulitan, isi Kisi-kisi/Indikator Soal, lalu tulis pertanyaan dan kunci jawabannya.'],
            ['title' => 'Impor ke Kuis Kelas', 'desc' => 'Buka halaman Kuis di dalam Kelas Digital, lalu centang soal dari Bank Soal untuk langsung dimasukkan ke kuis.'],
        ],
        'tips' => [
            'Gunakan filter di atas daftar soal untuk mencari soal berdasarkan mapel, kategori bab, atau tingkat kesulitan.',
        ],
    ],

    'exams.*' => [
        'title' => 'Panduan Kuis & Ujian',
        'summary' => 'Mengelola Ujian Kelas di LMS (UH, Kuis, Remedial) dan Ujian Sekolah Resmi CBTPGRI (PTS, PAS, US, Simulasi, Lainnya).',
        'steps' => [
            ['title' => 'Buat Ujian Sekolah (CBT) dalam 1 Langkah', 'desc' => 'Pilih Mapel & Tingkat (kelas yang Anda ampu otomatis tercentang), isi jenis ujian, lalu langsung centang soal dari Bank Soal di halaman yang sama.'],
            ['title' => 'Ref Durasi (menit)', 'desc' => 'Nilai Ref Durasi di LMS hanya sebagai referensi guru. Jadwal dan durasi resmi pelaksanaan ujian sekolah tetap diatur dan dikunci oleh panitia/proktor di CBTPGRI.'],
            ['title' => 'Jumlah Soal Ujian (Acak dari Pool)', 'desc' => 'Jika jumlah soal yang dipilih lebih banyak daripada Jumlah Soal Ujian (misal memilih 70 soal dan mengisi Jumlah Soal Ujian = 50), sistem LMS dan CBTPGRI otomatis mengacak dan menampilkan 50 soal berbeda untuk tiap siswa.'],
            ['title' => 'Kirim Paket & Tarik Nilai CBTPGRI', 'desc' => 'Setelah paket dikirim ke CBTPGRI, proktor akan melihat tanggal terima serta lencana "Baru" hingga paket dipakai di jadwal. Setelah ujian selesai, klik Tarik Nilai CBTPGRI.'],
        ],
        'tips' => [
            'Pastikan pilihan kelas pada pengaturan ujian sesuai karena terhubung permanen ke paket soal di CBTPGRI.',
        ],
    ],

    'projects.*' => [
        'title' => 'Panduan Projek / Kokurikuler',
        'summary' => 'Menyusun modul kegiatan kokurikuler / projek penguatan profil pelajar lintas mata pelajaran.',
        'steps' => [
            ['title' => 'Tambah Modul Projek', 'desc' => 'Isi Judul Projek, Tema, Fase, Dimensi Profil Pelajar, Tujuan, Alur Aktivitas, dan Rencana Asesmen.'],
            ['title' => 'Unggah Dokumen Pendukung', 'desc' => 'Lampirkan panduan PDF/DOC (opsional) agar koordinator lain dan siswa dapat mengunduhnya.'],
        ],
        'tips' => [
            'Modul projek yang dibuat tersimpan sesuai Tahun Ajaran yang sedang aktif.',
        ],
    ],

    'reports.*' => [
        'title' => 'Panduan Laporan Pembelajaran',
        'summary' => 'Melihat rekapitulasi keaktifan kelas digital dan mengekspor data untuk dokumentasi kurikulum.',
        'steps' => [
            ['title' => 'Pilih Tahun Ajaran', 'desc' => 'Gunakan pemilih Tahun Ajaran di header jika ingin memeriksa laporan tahun ajaran sebelumnya.'],
            ['title' => 'Ekspor CSV atau Cetak', 'desc' => 'Gunakan tombol "Ekspor Kelas (CSV)" untuk rekap kelas atau "Ekspor Jurnal Mengajar (CSV)" untuk rekap jurnal harian guru.'],
        ],
        'tips' => [
            'File CSV dapat langsung dibuka menggunakan Microsoft Excel atau Google Sheets.',
        ],
    ],

    'directory.*' => [
        'title' => 'Panduan Data Guru & Siswa',
        'summary' => 'Direktori lengkap daftar guru beserta jabatan struktural serta daftar siswa beserta kelas dan nomor kontak.',
        'steps' => [
            ['title' => 'Pilih Kategori', 'desc' => 'Gunakan tab "Data Guru" atau "Data Siswa" untuk berpindah daftar.'],
            ['title' => 'Cari atau Filter Kelas', 'desc' => 'Ketik nama/NIP/NISN di kolom pencarian, atau pilih rombel pada tab Data Siswa.'],
            ['title' => 'Hubungi Siswa', 'desc' => 'Klik nomor telepon atau tombol "Hubungi WA" jika tersedia pada data siswa.'],
        ],
        'tips' => [
            'Data master guru, jabatan, rombel, dan siswa disinkronkan langsung dari SIAP.',
        ],
    ],

    'monitoring.*' => [
        'title' => 'Panduan Monitoring Pembelajaran',
        'summary' => 'Panel pengawasan Kepala Sekolah, Waka Kurikulum, dan Admin untuk memantau aktivitas kelas digital dan guru.',
        'steps' => [
            ['title' => 'Tinjau KPI Sekolah', 'desc' => 'Lihat jumlah kelas aktif, draft, guru aktif, dan aktivitas 7 hari terakhir.'],
            ['title' => 'Periksa Progres Guru & Kelas', 'desc' => 'Gunakan tab Guru dan Kelas Digital atau kotak pencarian untuk menemukan guru/kelas tertentu.'],
        ],
        'tips' => [
            'Gunakan pemilih Tahun Ajaran di header untuk membandingkan kinerja pembelajaran antar tahun ajaran.',
        ],
    ],

    'schedule.*' => [
        'title' => 'Panduan Jadwal & Kalender',
        'summary' => 'Kalender agenda pembelajaran, jadwal tatap muka/sesi kelas, serta tenggat tugas dan kuis.',
        'steps' => [
            ['title' => 'Navigasi Bulan', 'desc' => 'Gunakan tombol Sebelumnya / Berikutnya untuk melihat agenda pembelajaran tiap bulan.'],
            ['title' => 'Tambah Jadwal', 'desc' => 'Jadwal kelas dapat ditambahkan langsung dari dalam halaman Kelas Digital masing-masing.'],
        ],
        'tips' => [
            'Tenggat Tugas dan Kuis otomatis tampil di kalender kelas.',
        ],
    ],

    'progress.*' => [
        'title' => 'Panduan Progres Belajar',
        'summary' => 'Memantau perkembangan penyelesaian materi, tugas, dan nilai kuis pada Tahun Ajaran terpilih.',
        'steps' => [
            ['title' => 'Cek Persentase Materi', 'desc' => 'Lihat kemajuan penyelesaian materi dan tugas pada setiap kelas digital.'],
        ],
        'tips' => [
            'Siswa dinyatakan menyelesaikan materi setelah menekan tombol "Tandai Selesai" pada halaman materi.',
        ],
    ],

    'tasks.*' => [
        'title' => 'Panduan Tugas & Kuis',
        'summary' => 'Pusat pengelolaan seluruh Tugas dan Kuis lintas kelas digital pada Tahun Ajaran terpilih.',
        'steps' => [
            ['title' => 'Pilih Tab Tugas atau Kuis', 'desc' => 'Gunakan tab Tugas atau Kuis di atas daftar untuk berpindah tampilan tanpa memuat ulang halaman.'],
            ['title' => 'Buat Tugas / Kuis Sekaligus', 'desc' => 'Guru dapat menekan "+ Buat Tugas" atau "+ Buat Kuis" lalu mencentang satu atau beberapa kelas paralel sekaligus.'],
            ['title' => 'Periksa atau Kerjakan', 'desc' => 'Klik "Periksa" / "Kelola" (Guru) atau "Buka" / "Lihat Kuis" (Siswa) untuk masuk ke detail penilaian atau pengerjaan.'],
        ],
        'tips' => [
            'Setelah membuat 1 kuis baru, Anda langsung diarahkan ke halaman kuis untuk menambah atau mengimpor soal dari Bank Soal.',
        ],
    ],

    'quizzes.index' => [
        'title' => 'Panduan Tugas & Kuis',
        'summary' => 'Pusat pengelolaan seluruh Tugas dan Kuis lintas kelas digital pada Tahun Ajaran terpilih.',
        'steps' => [
            ['title' => 'Pilih Tab Tugas atau Kuis', 'desc' => 'Gunakan tab Tugas atau Kuis di atas daftar untuk berpindah tampilan.'],
            ['title' => 'Buat Tugas / Kuis Sekaligus', 'desc' => 'Guru dapat menekan "+ Buat Tugas" atau "+ Buat Kuis" lalu mencentang satu atau beberapa kelas paralel sekaligus.'],
        ],
        'tips' => [
            'Gunakan Bank Soal agar butir soal dapat dipakai ulang lintas kelas.',
        ],
    ],

    'courses.quizzes.*' => [
        'title' => 'Panduan Kelola Kuis & Analisis Soal',
        'summary' => 'Mengelola butir soal kuis, mengimpor dari Bank Soal, menilai hasil siswa, dan melihat analisis butir soal.',
        'steps' => [
            ['title' => 'Tambah atau Impor Soal', 'desc' => 'Klik "+ Tambah Pertanyaan" atau "+ Impor Bank Soal" untuk mengisi daftar soal kuis.'],
            ['title' => 'Tinjau Analisis Butir Soal', 'desc' => 'Setelah siswa mengerjakan kuis, sistem otomatis menampilkan persentase benar dan tingkat kesukaran setiap butir soal.'],
            ['title' => 'Periksa Nilai', 'desc' => 'Periksa atau sesuaikan nilai peserta pada bagian Riwayat Pengerjaan di bawah.'],
        ],
        'tips' => [
            'Soal esai dapat dinilai manual pada daftar Riwayat Pengerjaan.',
        ],
    ],

    'courses.assignments.*' => [
        'title' => 'Panduan Detail & Penilaian Tugas',
        'summary' => 'Melihat instruksi tugas, rubrik penilaian, serta memeriksa dan memberi nilai pada pengumpulan siswa.',
        'steps' => [
            ['title' => 'Unduh Lampiran / Tugas Siswa', 'desc' => 'Klik tautan file yang dikumpulkan siswa untuk memeriksanya.'],
            ['title' => 'Input Nilai & Umpan Balik', 'desc' => 'Masukkan skor sesuai rubrik penilaian dan catatan umpan balik, lalu simpan.'],
        ],
        'tips' => [
            'Nilai tugas otomatis masuk ke rekap Ketercapaian KKTP kelas.',
        ],
    ],

    'courses.materials.*' => [
        'title' => 'Panduan Materi Pembelajaran',
        'summary' => 'Halaman baca materi pembelajaran digital, unduh lampiran, dan diskusi khusus pada materi tersebut.',
        'steps' => [
            ['title' => 'Pelajari & Unduh Lampiran', 'desc' => 'Baca ringkasan materi, tonton video tautan, atau unduh dokumen pendukung jika tersedia.'],
            ['title' => 'Tandai Selesai (Siswa)', 'desc' => 'Siswa dapat menekan "Tandai Selesai" agar progres belajar di kelas bertambah.'],
        ],
        'tips' => [
            'Kehadiran pembelajaran digital siswa otomatis tercatat saat membuka halaman materi selama sesi kelas aktif.',
        ],
    ],

    'notifications.*' => [
        'title' => 'Panduan Notifikasi',
        'summary' => 'Pusat pemberitahuan aktivitas pembelajaran seperti materi baru, tugas, kuis, dan sesi kelas digital.',
        'steps' => [
            ['title' => 'Tandai Sudah Dibaca', 'desc' => 'Klik tombol baca pada notifikasi tertentu atau "Tandai Semua Dibaca" di kanan atas.'],
        ],
        'tips' => [
            'Badge angka pada ikon lonceng di header menunjukkan jumlah notifikasi yang belum dibaca.',
        ],
    ],

    'profile.*' => [
        'title' => 'Panduan Profil & Kartu Identitas',
        'summary' => 'Mengelola informasi profil, nomor telepon/alamat, tampilan Kartu Identitas di sidebar, serta kata sandi akun.',
        'steps' => [
            ['title' => 'Sesuaikan Kartu Identitas', 'desc' => 'Pilih gaya kartu, posisi foto, warna aksen, atau unggah foto profil sesuai keinginan.'],
            ['title' => 'Perbarui Kontak', 'desc' => 'Pastikan nomor telepon terisi agar mudah dihubungi melalui Direktori Sekolah.'],
        ],
        'tips' => [
            'Tombol Keluar (Logout) juga tersedia di bagian bawah kartu profil Anda.',
        ],
    ],
];
