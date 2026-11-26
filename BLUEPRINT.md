# Cetak Biru Teknis (Technical Blueprint) — Sistem LMS Sekolah

**Versi:** 2.0 (Final Approved Architecture)  
**Direktori Proyek:** `C:\laragon\www\LMS`  
**Stack Utama:** Laravel 13 (PHP 8.3), MySQL, Blade + Livewire, Tabler UI, PWA, REST API (`/api/v1/*`)

---

## 1. Arsitektur Ekosistem & Prinsip Dasar

Ekosistem digital sekolah dibagi menjadi **3 aplikasi mandiri (*decoupled*)** dengan database dan penyimpanan terpisah untuk menjamin performa dan skalabilitas:

| Aplikasi | Domain / Lokasi | Tanggung Jawab Utama | Database & Storage |
| :--- | :--- | :--- | :--- |
| **SIAP** | `siap.smkpgrikras.my.id` | Master data akademik/administrasi sekolah & gerbang autentikasi Guru/Pegawai. | Terpisah (Master) |
| **LMS** | `C:\laragon\www\LMS` | Pusat pembelajaran digital (kelas, materi, tugas, diskusi, kuis harian). | Terpisah (Mandiri) |
| **CBTPGRI** | `cbt.smkpgri.my.id` | Mesin ujian resmi berisiko tinggi (UTS/UAS/US), terhubung via API di fase lanjutan. | Terpisah (Ujian) |

### Prinsip Arsitektur Wajib (*Fault Tolerance & Decoupled*)
1. **Kemandirian Operasional:** LMS bukan modul di dalam SIAP. Jika SIAP sedang *maintenance* atau mengalami gangguan, siswa tetap bisa belajar dan guru yang sudah memiliki sesi aktif tetap bisa mengakses materi/tugas.
2. **Pemisahan Akses:**
   * **Guru & Pegawai:** Login melalui SIAP $\rightarrow$ masuk ke LMS via SSO tanpa login ulang.
   * **Siswa:** Login langsung ke LMS (Web/PWA responsif, dengan kesiapan REST API `/api/v1/*` untuk aplikasi mobile Flutter di Fase 3).

---

## 2. Skema SSO Guru (*One-Time Authorization Code*) & Solusi PWA iOS

Untuk mencegah kebocoran token melalui riwayat browser, *access log* server, atau HTTP Referer, mekanisme SSO menggunakan **One-Time Authorization Code** dengan verifikasi *server-to-server* (tanpa payload sensitif di URL).

### Alur Autentikasi Guru (`SIAP` $\leftrightarrow$ `LMS`)
1. Guru menekan tombol **`[ BUKA LMS ]`** di dashboard SIAP.
2. Backend SIAP membuat kode autentikasi acak (`auth_code` UUID/random string) yang disimpan sementara (cache/DB) dengan masa berlaku **60 detik** dan status belum terpakai.
3. SIAP melakukan *top-level redirect* ke LMS: `GET /sso/callback?code=<auth_code>`.
4. Backend LMS menangkap `code`, lalu melakukan panggilan *server-to-server* ke SIAP (`POST /api/integration/lms/sso/verify`) dengan menyertakan `code` dan header `Authorization: Bearer <INTEGRATION_API_TOKEN>`.
5. SIAP memvalidasi `code` (memastikan belum kedaluwarsa dan belum pernah dipakai), menandainya sebagai sudah digunakan (*one-time use*), lalu mengembalikan data JSON user (`siap_user_id`, `username`, `name`, `email`, `base_role`, `assignments`).
6. LMS melakukan *upsert* (buat/perbarui) data user guru dan penugasan strukturalnya, lalu membuat sesi login Laravel native.
7. Di header LMS tersedia tombol **`[ KEMBALI KE SIAP ]`** yang mengarahkan kembali ke dashboard SIAP.

### Catatan Teknis Kompatibilitas iPhone (Safari / iOS PWA)
* **Hindari Iframe Lintas Subdomain:** Kebijakan *Intelligent Tracking Prevention (ITP)* pada iOS memblokir cookie sesi di dalam iframe lintas subdomain.
* **Solusi:** Gunakan navigasi *top-level redirect* (`window.location.href`) antara SIAP dan LMS serta konfigurasikan `manifest.json` PWA secara terpisah. *(Opsional untuk produksi: jika ingin 100% menyatu tanpa in-app browser di iOS PWA, gunakan Reverse Proxy pada domain yang sama di path `/lms`).*

---

## 3. Manajemen Akun, Role, & Keamanan

### A. Pemisahan Role Dasar & Penugasan Struktural
LMS tidak mengunci jabatan struktural sebagai role permanen pada tabel `users`:
* **Role Dasar (`users.role`):** Hanya terdiri dari `admin`, `guru`, dan `siswa`.
* **Jabatan Struktural (`Waka Kurikulum`, `Kepala Sekolah`, `Wali Kelas`):** Dikelola melalui tabel penugasan/kewenangan (`user_assignments`) yang disinkronkan dari SIAP. Jika seorang guru diangkat menjadi Waka Kurikulum, identitas dasarnya di LMS tidak perlu diubah.

### B. Keamanan Akun & Password Siswa
* **Username:** Menggunakan **NISN** (siswa) dan **NIP/username SIAP** (guru).
* **Kredensial Awal Siswa:** Akun dibuat otomatis saat sinkronisasi. Password awal menggunakan **NISN** dengan flag `must_change_password = true`.
* **Wajib Ganti Password:** Middleware LMS memaksa siswa mengganti password saat login pertama sebelum dapat mengakses kelas atau materi. Tersedia fitur reset password ke default oleh Administrator LMS.

### C. Keamanan File & Kesiapan Mobile (Flutter)
* Seluruh file materi dan lampiran tugas disimpan di *private storage* (`storage/app/private/...`), bukan di folder `public`.
* Akses unduhan file melalui Web maupun REST API `/api/v1/*` wajib melewati controller otorisasi (memastikan user terdaftar di kelas terkait) atau menggunakan *Temporary Signed URL*.

---

## 4. Kontrak API & Mekanisme Sinkronisasi (`SIAP` $\leftrightarrow$ `LMS`)

Seluruh komunikasi *server-to-server* menggunakan header `Authorization: Bearer <INTEGRATION_API_TOKEN>` dan tercatat di tabel `sync_logs`.

### Strategi Sinkronisasi Data
* **Initial Sync:** Penarikan penuh data master dari SIAP saat awal semester/instalasi.
* **Incremental Sync:** Penarikan berkala berdasarkan parameter `?updated_since=<timestamp>` untuk meminimalkan beban server.
* **Real-Time Webhook + Queue:** SIAP mengirim event perubahan ke LMS. LMS langsung membalas `200 OK` dan memproses pembaruan data di latar belakang menggunakan **Laravel Queue** untuk mencegah timeout.
* **Idempotensi:** Semua tabel master referensi menggunakan `siap_id` (*unique constraint*) untuk mencegah duplikasi data saat sinkronisasi ulang.

### A. Endpoint di SIAP (Ditarik oleh LMS)
* `POST /api/integration/lms/sso/verify` — Verifikasi *One-Time Authorization Code* SSO guru.
* `GET /api/integration/lms/academic-years` — Tahun ajaran & semester aktif.
* `GET /api/integration/lms/teachers` — Daftar GTK/Guru beserta jabatan/penugasan struktural.
* `GET /api/integration/lms/classrooms` — Daftar kelas/rombel (contoh: `X RPL 1`).
* `GET /api/integration/lms/subjects` — Daftar mata pelajaran.
* `GET /api/integration/lms/teaching-assignments` — Plotting Guru + Mapel + Rombel.
* `GET /api/integration/lms/students` — Data siswa (`NISN`, `NIS`, `nama`, `classroom_id`, `status`).

### B. Webhook di LMS (Dipanggil oleh SIAP)
* `POST /api/v1/webhooks/siap` — Menerima event `student.updated`, `student.mutated`, `classroom.updated`, `assignment.updated`, dan `academic_year.activated`.

### C. Endpoint di LMS (Ditarik oleh SIAP)
* `GET /api/v1/integration/siap/grade-recap` — Rekap nilai pembelajaran per siswa & mapel (hanya nilai yang sudah divalidasi guru).
* `GET /api/v1/integration/siap/class-activity-summary` — Ringkasan kelas aktif, jumlah materi dipublikasikan, dan progres penyelesaian tugas.

---

## 5. Struktur Database Inti LMS

Seluruh tabel menggunakan *foreign key*, *unique constraint*, *database transaction*, dan *soft deletes* pada data transaksional/riwayat penting.

### A. Master Referensi, Akun, & Riwayat
* `academic_years` (`id`, `siap_id`, `name`, `semester`, `is_active`)
* `users` (`id`, `siap_user_id` nullable, `username`, `name`, `email`, `password`, `role` [`admin`, `guru`, `siswa`], `must_change_password`, `is_active`)
* `user_profiles` (`id`, `user_id`, `nip_nisn`, `phone`, `avatar_path`, `bio`)
* `user_assignments` (`id`, `user_id`, `academic_year_id`, `role_type` [`kepsek`, `waka_kurikulum`, `wali_kelas`], `classroom_id` nullable)
* `classrooms` (`id`, `siap_id`, `academic_year_id`, `name`, `grade_level`, `department`)
* `subjects` (`id`, `siap_id`, `code`, `name`)
* `classroom_students` (`id`, `classroom_id`, `student_user_id`, `status`)
* `student_class_histories` (`id`, `student_user_id`, `classroom_id`, `academic_year_id`, `joined_at`, `left_at`, `reason`) — menjaga riwayat belajar saat siswa mutasi/pindah kelas.

### B. Kelas Digital & Materi
* `courses` (`id`, `academic_year_id`, `classroom_id`, `subject_id`, `title`, `description`, `status` [`draft`, `active`, `archived`], `opened_at`) — *unique pada `(academic_year_id, classroom_id, subject_id)` agar mendukung team teaching.*
* `course_teachers` (`id`, `course_id`, `teacher_id`, `siap_assignment_id` nullable, `is_primary`) — mendukung *team teaching* / lebih dari satu guru per kelas.
* `course_enrollments` (`id`, `course_id`, `student_id`, `enrolled_at`, `status`)
* `course_modules` (`id`, `course_id`, `title`, `description`, `sort_order`, `is_published`)
* `materials` (`id`, `module_id`, `course_id`, `title`, `type` [`text`, `file`, `video_link`], `content`, `file_path`, `external_url`, `sort_order`, `is_published`)
* `material_progress` (`id`, `material_id`, `student_id`, `completed_at`)

### C. Tugas, Penilaian, & Kuis Harian
* `assignments` (`id`, `course_id`, `module_id`, `title`, `instructions`, `attachment_path`, `due_at`, `max_score`, `allow_late`, `is_published`)
* `assignment_submissions` (`id`, `assignment_id`, `student_id`, `content`, `file_path`, `submitted_at`, `is_late`, `score`, `feedback`, `graded_at`, `graded_by`, `is_validated_for_siap`)
* `quizzes` (`id`, `course_id`, `module_id`, `title`, `duration_minutes`, `start_at`, `end_at`, `max_attempts`, `is_published`)
* `quiz_questions` (`id`, `quiz_id`, `type` [`multiple_choice`, `essay`], `question_text`, `options_json`, `correct_answer`, `points`, `sort_order`)
* `quiz_attempts` (`id`, `quiz_id`, `student_id`, `started_at`, `submitted_at`, `answers_json`, `score`, `status`)

### D. Diskusi, Notifikasi, & Sistem
* `discussions` (`id`, `course_id`, `discussable_type`, `discussable_id`, `user_id`, `parent_id`, `body`)
* `notifications` (`id`, `user_id`, `type`, `title`, `message`, `data_json`, `read_at`)
* `activity_logs` (`id`, `user_id`, `action`, `subject_type`, `subject_id`, `properties_json`, `ip_address`, `created_at`)
* `personal_access_tokens` — tabel bawaan **Laravel Sanctum** untuk API token (`/api/v1/*`).
* `sync_logs` (`id`, `source`, `event_type`, `status`, `summary_json`, `error_message`, `created_at`)

---

## 6. Roadmap Pengerjaan Bertahap

### Fase 1 — Fondasi, Autentikasi, & Koneksi SIAP
1. Inisialisasi proyek Laravel 13 di `C:\laragon\www\LMS`, konfigurasi database MySQL, Laravel Queue, Sanctum API (`/api/v1/*`), Livewire, dan struktur dasar PWA + Tabler UI responsif.
2. Pembuatan seluruh migrasi tabel database LMS beserta relasi, index, dan constraint.
3. Pembangunan API Integrasi di SIAP serta modul *Initial/Incremental Sync* & *Webhook Receiver* (berbasis Queue) di LMS.
4. Implementasi SSO Guru (*One-Time Authorization Code*) + tombol `[ BUKA LMS ]` di SIAP & `[ KEMBALI KE SIAP ]` di LMS.
5. Autentikasi Siswa (login NISN + middleware wajib ganti password pada login pertama + fitur reset password oleh Admin).

### Fase 2 — Fitur Inti Pembelajaran (MVP)
6. Antarmuka & Dashboard terpisah berbasis peran/penugasan (*Guru, Siswa, Waka Kurikulum, Kepala Sekolah, Admin*).
7. Manajemen Kelas Digital (`Draft` $\rightarrow$ `Buka Kelas` $\rightarrow$ `Auto-enroll siswa` dari rombel & fitur duplikasi materi antar kelas/semester).
8. Modul Materi Pembelajaran (teks, dokumen, video YouTube) + pelacakan progres baca/tonton siswa (`material_progress`).
9. Modul Tugas (pembuatan tugas, unggah jawaban siswa, penilaian & umpan balik guru, serta validasi rekap nilai).
10. Forum Diskusi per kelas dan komentar pada materi.

### Fase 3 — Evaluasi Lanjutan & Ekspansi Mobile
11. Modul Kuis Harian ringan di dalam LMS (pilihan ganda & esai).
12. Integrasi API penarikan nilai ujian dari CBTPGRI serta aktivasi endpoint pengiriman rekap nilai & keaktifan ke SIAP.
13. Pengembangan Aplikasi Mobile Siswa (Flutter untuk Android & iOS) menggunakan REST API `/api/v1/*` dan *Signed File URLs*.
