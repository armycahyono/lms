<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>Preview 3 Konsep Desain — LMS SMK PGRI Kras</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
        }
        h1, h2, h3, h4, .brand-font {
            font-family: 'Instrument Sans', 'Inter', sans-serif;
        }
        .concept-switcher {
            position: sticky;
            top: 0;
            z-index: 1050;
            background: rgba(15, 23, 42, 0.94);
            backdrop-filter: blur(10px);
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        /* Konsep 1: Vibrant Tech */
        .v1-hero {
            background: linear-gradient(135deg, #1d4ed8 0%, #4f46e5 55%, #0ea5e9 100%);
            border-radius: 20px;
            color: #fff;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.25);
        }
        .v1-card {
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05);
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }
        .v1-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.09);
        }
        .v1-banner-blue { background: linear-gradient(135deg, #2563eb, #38bdf8); }
        .v1-banner-emerald { background: linear-gradient(135deg, #059669, #34d399); }
        .v1-banner-orange { background: linear-gradient(135deg, #ea580c, #fb923c); }

        /* Konsep 2: Clean Bento + Dark Mode */
        .v2-scope {
            border-radius: 20px;
            padding: 1.5rem;
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .v2-scope.is-dark {
            background: #0b1120;
            color: #e2e8f0;
            border-color: #1e293b;
        }
        .v2-bento {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }
        .v2-scope.is-dark .v2-bento {
            background: #111827;
            border-color: #1f2937;
            color: #f1f5f9;
        }
        .v2-scope.is-dark .text-muted {
            color: #94a3b8 !important;
        }
        .v2-scope.is-dark .bg-light {
            background-color: #1e293b !important;
            color: #e2e8f0 !important;
        }

        /* Konsep 3: Playful Interactive */
        .v3-hero {
            background: linear-gradient(120deg, #0284c7 0%, #10b981 100%);
            border-radius: 22px;
            color: #fff;
        }
        .v3-card {
            border-radius: 18px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 5px 0 #cbd5e1;
        }
        .v3-btn-3d {
            border-radius: 12px;
            font-weight: 700;
            box-shadow: 0 4px 0 #1e40af;
        }
        .v3-step-dot {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
    </style>
</head>
<body>

{{-- Bar Pemilih Konsep --}}
<div class="concept-switcher py-3 px-3 px-md-4 mb-4">
    <div class="container-xl d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <div class="fw-bold">Preview Konsep Desain LMS SMK PGRI Kras</div>
            <div class="small text-white-50">Klik salah satu konsep untuk membandingkan tampilan</div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary btn-sm concept-tab" data-target="concept-1">
                1. Vibrant Tech (EdTech)
            </button>
            <button type="button" class="btn btn-outline-light btn-sm concept-tab" data-target="concept-2">
                2. Clean Bento + Dark Mode
            </button>
            <button type="button" class="btn btn-outline-light btn-sm concept-tab" data-target="concept-3">
                3. Playful Interactive
            </button>
            <a href="{{ route('dashboard') }}" class="btn btn-ghost-light btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Kembali ke LMS
            </a>
        </div>
    </div>
</div>

<div class="container-xl pb-5">

    {{-- ================= KONSEP 1: VIBRANT TECH ================= --}}
    <div id="concept-1" class="concept-panel">
        <div class="alert alert-info mb-4">
            <strong>Konsep 1 — Vibrant Tech / EdTech Modern:</strong> Gradasi biru-indigo yang energik, kartu membulat dengan efek hover, chip kategori per jurusan/mapel, dan streak belajar untuk memicu semangat anak SMK.
        </div>

        <div class="v1-hero p-4 p-md-5 mb-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-25 rounded-pill px-3 py-1 small fw-semibold mb-3">
                        <i class="ti ti-flame text-warning"></i> 5 Hari Belajar Beruntun!
                    </div>
                    <h1 class="mb-2 text-white">Siap lanjut belajar, Budi Santoso?</h1>
                    <p class="text-white-50 mb-3">Kelas XI TKJ 1 &middot; Kamu sudah menyelesaikan 7 dari 10 materi minggu ini.</p>
                    <div class="progress bg-white bg-opacity-25 mb-2" style="height: 10px; max-width: 420px; border-radius: 999px;">
                        <div class="progress-bar bg-white" style="width: 70%; border-radius: 999px;"></div>
                    </div>
                    <div class="small fw-medium">Progres Minggu Ini: 70%</div>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="#" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-3">
                        <i class="ti ti-player-play me-1"></i> Lanjutkan: Konfigurasi MikroTik
                    </a>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card v1-card p-3">
                    <div class="text-muted small">Tugas Mendesak</div>
                    <div class="h2 mb-0 text-warning">2 Tugas</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card v1-card p-3">
                    <div class="text-muted small">Kuis Tersedia</div>
                    <div class="h2 mb-0 text-primary">1 Kuis</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card v1-card p-3">
                    <div class="text-muted small">Materi Selesai</div>
                    <div class="h2 mb-0 text-success">14 Materi</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card v1-card p-3">
                    <div class="text-muted small">Kelas Aktif</div>
                    <div class="h2 mb-0">6 Kelas</div>
                </div>
            </div>
        </div>

        <h3 class="mb-3">Kelas Digital Kamu</h3>
        <div class="row g-3">
            <div class="col-md-4">
                <div class="card v1-card overflow-hidden h-100">
                    <div class="v1-banner-blue p-3 text-white d-flex justify-content-between align-items-center">
                        <span class="badge bg-white text-primary fw-bold">TKJ</span>
                        <i class="ti ti-router fs-1 opacity-75"></i>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-1">Administrasi Infrastruktur Jaringan</h4>
                        <div class="text-muted small mb-3">Pak Ahmad Fauzi &middot; XI TKJ 1</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Progres</span><span class="fw-bold text-primary">75%</span>
                        </div>
                        <div class="progress progress-sm mb-3"><div class="progress-bar bg-primary" style="width:75%"></div></div>
                        <a href="#" class="btn btn-primary w-100 rounded-3">Masuk Kelas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card v1-card overflow-hidden h-100">
                    <div class="v1-banner-emerald p-3 text-white d-flex justify-content-between align-items-center">
                        <span class="badge bg-white text-success fw-bold">RPL</span>
                        <i class="ti ti-code fs-1 opacity-75"></i>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-1">Pemrograman Web &amp; Perangkat Bergerak</h4>
                        <div class="text-muted small mb-3">Bu Siti Aminah &middot; XI RPL 1</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Progres</span><span class="fw-bold text-success">50%</span>
                        </div>
                        <div class="progress progress-sm mb-3"><div class="progress-bar bg-success" style="width:50%"></div></div>
                        <a href="#" class="btn btn-outline-primary w-100 rounded-3">Masuk Kelas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card v1-card overflow-hidden h-100">
                    <div class="v1-banner-orange p-3 text-white d-flex justify-content-between align-items-center">
                        <span class="badge bg-white text-warning fw-bold">PKK</span>
                        <i class="ti ti-bulb fs-1 opacity-75"></i>
                    </div>
                    <div class="card-body">
                        <h4 class="mb-1">Produk Kreatif &amp; Kewirausahaan</h4>
                        <div class="text-muted small mb-3">Pak Hendra &middot; XI TKJ 1</div>
                        <div class="d-flex justify-content-between small mb-1">
                            <span>Progres</span><span class="fw-bold text-warning">30%</span>
                        </div>
                        <div class="progress progress-sm mb-3"><div class="progress-bar bg-warning" style="width:30%"></div></div>
                        <a href="#" class="btn btn-outline-primary w-100 rounded-3">Masuk Kelas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KONSEP 2: CLEAN BENTO + DARK MODE ================= --}}
    <div id="concept-2" class="concept-panel d-none">
        <div class="alert alert-info mb-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <strong>Konsep 2 — Clean Bento &amp; Minimalis + Dark Mode:</strong> Rapi seperti Notion/Linear, fokus tinggi, dan nyaman dipakai malam hari lewat tombol mode gelap.
            </div>
            <button type="button" class="btn btn-dark btn-sm" id="toggle-bento-dark">
                <i class="ti ti-moon me-1"></i> Coba Mode Gelap / Terang
            </button>
        </div>

        <div class="v2-scope" id="v2-scope">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                <div>
                    <div class="text-muted small">Tahun Ajaran 2026/2027 &middot; XI TKJ 1</div>
                    <h2 class="mb-0">Halo, Budi Santoso</h2>
                </div>
                <span class="badge bg-blue-lt px-3 py-2">Asah Keterampilan – Teguhkan Keimanan – Raih Kesuksesan</span>
            </div>

            <div class="row g-3">
                <div class="col-lg-5">
                    <div class="v2-bento p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="text-muted small mb-1">LANJUTKAN BELAJAR</div>
                            <h3 class="mb-2">Konfigurasi VLAN &amp; Trunking</h3>
                            <p class="text-muted small mb-3">Administrasi Infrastruktur Jaringan &middot; Modul 3</p>
                        </div>
                        <div>
                            <div class="d-flex justify-content-between small mb-1">
                                <span class="text-muted">Progres Keseluruhan</span>
                                <span class="fw-bold">70%</span>
                            </div>
                            <div class="progress progress-sm mb-3"><div class="progress-bar bg-primary" style="width:70%"></div></div>
                            <a href="#" class="btn btn-primary w-100">Buka Materi</a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="v2-bento p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted small fw-semibold">TENGGAT TERDEKAT</span>
                            <span class="badge bg-warning-lt">2 Aktif</span>
                        </div>
                        <div class="bg-light rounded p-3 mb-2">
                            <div class="fw-semibold small">Laporan Praktik Subnetting</div>
                            <div class="text-danger" style="font-size:0.75rem;">Besok, 23:59</div>
                        </div>
                        <div class="bg-light rounded p-3">
                            <div class="fw-semibold small">Rancangan Mockup Landing Page</div>
                            <div class="text-muted" style="font-size:0.75rem;">3 hari lagi</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="v2-bento p-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="text-muted small fw-semibold mb-2">KUIS TERSEDIA</div>
                            <div class="fw-bold">Ujian Harian Jaringan</div>
                            <div class="text-muted small">30 Menit &middot; 20 Soal</div>
                        </div>
                        <a href="#" class="btn btn-outline-primary w-100 mt-3">Mulai Kuis</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= KONSEP 3: PLAYFUL INTERACTIVE ================= --}}
    <div id="concept-3" class="concept-panel d-none">
        <div class="alert alert-info mb-4">
            <strong>Konsep 3 — Playful Interactive (Ala Duolingo / Quizizz):</strong> Alur belajar berbentuk misi/step yang jelas, tombol 3D yang empuk di-tap di HP, dan nuansa belajar yang seru.
        </div>

        <div class="v3-hero p-4 p-md-5 mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <span class="badge bg-white text-success fw-bold mb-2">LEVEL 4 &middot; PEJUANG KOMPETENSI</span>
                    <h1 class="mb-1 text-white">Ayo Selesaikan Misi Hari Ini!</h1>
                    <p class="mb-0 text-white-50">Tinggal 1 materi lagi untuk menuntaskan Bab Jaringan Dasar.</p>
                </div>
                <a href="#" class="btn btn-light text-primary fw-bold px-4 py-2 rounded-pill">
                    <i class="ti ti-rocket me-1"></i> Gas Belajar!
                </a>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <div class="card v3-card p-4">
                    <h3 class="mb-3">Jalur Belajar: Administrasi Jaringan</h3>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-success-lt">
                            <div class="d-flex align-items-center gap-3">
                                <span class="v3-step-dot bg-success text-white"><i class="ti ti-check"></i></span>
                                <div>
                                    <div class="fw-bold text-dark">1. Pengenalan IP Address</div>
                                    <div class="small text-muted">Selesai dibaca</div>
                                </div>
                            </div>
                            <span class="badge bg-success text-white">+20 XP</span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-blue-lt border border-primary">
                            <div class="d-flex align-items-center gap-3">
                                <span class="v3-step-dot bg-primary text-white">2</span>
                                <div>
                                    <div class="fw-bold text-dark">2. Praktik Subnetting CIDR</div>
                                    <div class="small text-primary fw-medium">Sedang dipelajari</div>
                                </div>
                            </div>
                            <a href="#" class="btn btn-primary btn-sm v3-btn-3d">Lanjut</a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light">
                            <div class="d-flex align-items-center gap-3">
                                <span class="v3-step-dot bg-secondary-lt text-muted">3</span>
                                <div>
                                    <div class="fw-bold text-muted">3. Kuis Tantangan Subnetting</div>
                                    <div class="small text-muted">15 Soal &middot; 20 Menit</div>
                                </div>
                            </div>
                            <span class="badge bg-warning-lt">Tantangan</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card v3-card p-4 h-100">
                    <h3 class="mb-3">Pencapaian Kamu</h3>
                    <div class="row g-2 text-center">
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-warning-lt">
                                <i class="ti ti-trophy fs-1 text-warning d-block mb-1"></i>
                                <div class="fw-bold">Rajin Tugas</div>
                                <div class="small text-muted">5 tugas tepat waktu</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 rounded-3 bg-success-lt">
                                <i class="ti ti-bolt fs-1 text-success d-block mb-1"></i>
                                <div class="fw-bold">Cepat Tanggap</div>
                                <div class="small text-muted">Skor kuis &ge; 85</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.concept-tab');
    const panels = document.querySelectorAll('.concept-panel');

    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            tabs.forEach((t) => {
                t.classList.remove('btn-primary');
                t.classList.add('btn-outline-light');
            });
            tab.classList.remove('btn-outline-light');
            tab.classList.add('btn-primary');

            panels.forEach((p) => p.classList.add('d-none'));
            document.getElementById(tab.dataset.target)?.classList.remove('d-none');
        });
    });

    const toggleDark = document.getElementById('toggle-bento-dark');
    const scope = document.getElementById('v2-scope');
    if (toggleDark && scope) {
        toggleDark.addEventListener('click', () => {
            scope.classList.toggle('is-dark');
        });
    }
});
</script>
</body>
</html>
