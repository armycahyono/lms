<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>Preview Header &amp; Sidebar Melayang — LMS SMK PGRI Kras</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            color: #0f172a;
        }
        h1, h2, h3, h4, .brand-font {
            font-family: 'Instrument Sans', 'Inter', sans-serif;
        }

        .preview-toolbar {
            background: #0f172a;
            color: #fff;
            padding: 0.75rem 1rem;
        }

        .demo-stage {
            min-height: calc(100vh - 64px);
            padding: 16px;
        }

        /* Shared Floating Header Base */
        .float-header {
            position: relative;
            width: 100%;
            min-height: 68px;
            background: #0284c7;
            color: #fff;
            border-radius: 18px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1.25rem;
            margin-bottom: 16px;
            --mx: 50%;
            --my: 50%;
        }

        .float-header-content {
            position: relative;
            z-index: 3;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        /* Cursor spotlight glow */
        .cursor-glow::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 2;
            pointer-events: none;
            background: radial-gradient(240px circle at var(--mx) var(--my), rgba(255, 255, 255, 0.28), transparent 70%);
            transition: opacity 0.2s ease;
        }

        /* SVG Wave layers */
        .wave-wrap {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            height: 36px;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .wave-svg {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 100%;
            animation: waveFlow 9s linear infinite;
        }

        .wave-svg.wave-2 {
            opacity: 0.55;
            animation: waveFlowReverse 13s linear infinite;
        }

        @keyframes waveFlow {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        @keyframes waveFlowReverse {
            0% { transform: translateX(-50%); }
            100% { transform: translateX(0); }
        }

        /* Layout body below full-width floating header */
        .demo-body {
            display: flex;
            gap: 16px;
            align-items: flex-start;
        }

        .float-sidebar {
            width: 250px;
            flex-shrink: 0;
            background: #ffffff;
            border-radius: 18px;
            padding: 1rem;
        }

        .demo-nav-link {
            display: flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.65rem 0.9rem;
            border-radius: 12px;
            color: #475569;
            font-weight: 500;
            text-decoration: none;
            margin-bottom: 4px;
        }

        .demo-nav-link.active {
            background: #e0f2fe;
            color: #0284c7;
            font-weight: 600;
        }

        .demo-main {
            flex: 1;
            min-width: 0;
        }

        /* Option 1 specific */
        .opt1-header {
            box-shadow: 0 10px 25px rgba(2, 132, 199, 0.25);
        }
        .opt1-sidebar {
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        /* Option 2 specific */
        .opt2-header {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            box-shadow: 0 12px 28px rgba(3, 105, 161, 0.28);
        }
        .opt2-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            pointer-events: none;
        }
        .opt2-sidebar {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
        }

        /* Option 3 specific */
        .opt3-header {
            border-radius: 20px;
            box-shadow: 0 4px 0 #0369a1, 0 10px 22px rgba(2, 132, 199, 0.2);
        }
        .opt3-sidebar {
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 0 #e2e8f0;
        }
        .opt3-card {
            border-radius: 18px;
            border: 2px solid #e2e8f0;
            box-shadow: 0 4px 0 #e2e8f0;
        }

        @media (max-width: 767.98px) {
            .demo-body {
                flex-direction: column;
            }
            .float-sidebar {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<div class="preview-toolbar d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <span class="fw-bold">Preview Header Penuh &amp; Sidebar Melayang</span>
        <span class="text-white-50 small ms-2">Gerakkan kursor di atas header biru untuk melihat efek gelombang &amp; cahaya</span>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-primary btn-sm opt-tab" data-target="opt-1">Opsi 1: SVG Wave + Glow</button>
        <button type="button" class="btn btn-outline-light btn-sm opt-tab" data-target="opt-2">Opsi 2: Canvas Riak Air</button>
        <button type="button" class="btn btn-outline-light btn-sm opt-tab" data-target="opt-3">Opsi 3: Playful Tactile</button>
        <a href="{{ route('dashboard') }}" class="btn btn-ghost-light btn-sm">Kembali</a>
    </div>
</div>

{{-- ================= OPSI 1 ================= --}}
<div id="opt-1" class="demo-stage opt-panel">
    <header class="float-header opt1-header cursor-glow js-cursor-header">
        <div class="wave-wrap">
            <svg class="wave-svg" viewBox="0 0 1200 60" preserveAspectRatio="none">
                <path d="M0,30 C150,55 300,5 450,30 C600,55 750,5 900,30 C1050,55 1125,15 1200,30 L1200,60 L0,60 Z" fill="rgba(255,255,255,0.16)"/>
            </svg>
            <svg class="wave-svg wave-2" viewBox="0 0 1200 60" preserveAspectRatio="none">
                <path d="M0,36 C180,10 320,56 500,34 C680,12 820,56 1000,32 C1100,18 1150,40 1200,36 L1200,60 L0,60 Z" fill="rgba(255,255,255,0.12)"/>
            </svg>
        </div>
        <div class="float-header-content">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-primary fw-bold px-2 py-1 rounded-3">LMS</span>
                <span class="fw-bold fs-4 brand-font">SMK PGRI Kras</span>
                <span class="badge bg-white bg-opacity-25 text-white ms-2 d-none d-sm-inline">Opsi 1 &middot; SVG Wave + Cursor Glow</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="position-relative"><i class="ti ti-bell fs-3"></i></span>
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-white text-primary fw-bold rounded-circle">B</span>
                    <span class="d-none d-md-inline fw-semibold small">Budi Santoso</span>
                </div>
            </div>
        </div>
    </header>

    <div class="demo-body">
        <aside class="float-sidebar opt1-sidebar">
            <a href="#" class="demo-nav-link active"><i class="ti ti-home"></i> Beranda</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-books"></i> Kelas Digital</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-clipboard-check"></i> Tugas</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-file-check"></i> Kuis</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-bell"></i> Notifikasi</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-user-circle"></i> Profil</a>
        </aside>
        <main class="demo-main">
            <div class="card p-4">
                <h3 class="mb-1">Opsi 1 — Floating Capsule + SVG Wave &amp; Cursor Glow</h3>
                <p class="text-muted mb-0">Header memanjang penuh tanpa terpotong sidebar, warna biru `#0284c7`, gelombang SVG bergerak halus, dan cahaya lembut mengikuti kursor.</p>
            </div>
        </main>
    </div>
</div>

{{-- ================= OPSI 2 ================= --}}
<div id="opt-2" class="demo-stage opt-panel d-none">
    <header class="float-header opt2-header cursor-glow js-cursor-header" id="ocean-header">
        <canvas class="opt2-canvas" id="ocean-canvas"></canvas>
        <div class="float-header-content">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-primary fw-bold px-2 py-1 rounded-3">LMS</span>
                <span class="fw-bold fs-4 brand-font">SMK PGRI Kras</span>
                <span class="badge bg-white bg-opacity-25 text-white ms-2 d-none d-sm-inline">Opsi 2 &middot; Canvas Riak Air</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span><i class="ti ti-bell fs-3"></i></span>
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-white text-primary fw-bold rounded-circle">B</span>
                    <span class="d-none d-md-inline fw-semibold small">Budi Santoso</span>
                </div>
            </div>
        </div>
    </header>

    <div class="demo-body">
        <aside class="float-sidebar opt2-sidebar">
            <a href="#" class="demo-nav-link active"><i class="ti ti-home"></i> Beranda</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-books"></i> Kelas Digital</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-clipboard-check"></i> Tugas</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-file-check"></i> Kuis</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-bell"></i> Notifikasi</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-user-circle"></i> Profil</a>
        </aside>
        <main class="demo-main">
            <div class="card p-4">
                <h3 class="mb-1">Opsi 2 — Ocean Wave Canvas + Efek Riak Kursor</h3>
                <p class="text-muted mb-0">Arahkan kursor ke atas header: gelombang air Canvas akan meninggi/beriak di titik kursor melintas, dipadukan sidebar kaca melayang.</p>
            </div>
        </main>
    </div>
</div>

{{-- ================= OPSI 3 ================= --}}
<div id="opt-3" class="demo-stage opt-panel d-none">
    <header class="float-header opt3-header cursor-glow js-cursor-header">
        <div class="wave-wrap">
            <svg class="wave-svg" viewBox="0 0 1200 60" preserveAspectRatio="none">
                <path d="M0,32 C160,55 320,8 480,32 C640,56 800,8 960,32 C1080,50 1140,20 1200,32 L1200,60 L0,60 Z" fill="rgba(255,255,255,0.18)"/>
            </svg>
            <svg class="wave-svg wave-2" viewBox="0 0 1200 60" preserveAspectRatio="none">
                <path d="M0,38 C200,14 350,54 550,34 C750,14 900,54 1100,32 L1200,38 L1200,60 L0,60 Z" fill="rgba(255,255,255,0.12)"/>
            </svg>
        </div>
        <div class="float-header-content">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-primary fw-bold px-2 py-1 rounded-3">LMS</span>
                <span class="fw-bold fs-4 brand-font">SMK PGRI Kras</span>
                <span class="badge bg-white bg-opacity-25 text-white ms-2 d-none d-sm-inline">Opsi 3 &middot; Playful Tactile</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span><i class="ti ti-bell fs-3"></i></span>
                <div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-sm bg-white text-primary fw-bold rounded-circle">B</span>
                    <span class="d-none d-md-inline fw-semibold small">Budi Santoso</span>
                </div>
            </div>
        </div>
    </header>

    <div class="demo-body">
        <aside class="float-sidebar opt3-sidebar">
            <a href="#" class="demo-nav-link active"><i class="ti ti-home"></i> Beranda</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-books"></i> Kelas Digital</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-clipboard-check"></i> Tugas</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-file-check"></i> Kuis</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-bell"></i> Notifikasi</a>
            <a href="#" class="demo-nav-link"><i class="ti ti-user-circle"></i> Profil</a>
        </aside>
        <main class="demo-main">
            <div class="card opt3-card p-4">
                <h3 class="mb-1">Opsi 3 — Playful Tactile Floating Bar</h3>
                <p class="text-muted mb-0">Header &amp; sidebar melayang dengan garis bayangan 3D taktil yang 100% serasi dengan tema Playful Interactive LMS.</p>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Switcher
    const tabs = document.querySelectorAll('.opt-tab');
    const panels = document.querySelectorAll('.opt-panel');
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
            if (tab.dataset.target === 'opt-2') {
                resizeCanvas();
            }
        });
    });

    // Cursor spotlight on all headers
    document.querySelectorAll('.js-cursor-header').forEach((header) => {
        header.addEventListener('mousemove', (e) => {
            const rect = header.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            header.style.setProperty('--mx', `${x}%`);
            header.style.setProperty('--my', `${y}%`);
        });
    });

    // Interactive Canvas Wave for Option 2
    const canvas = document.getElementById('ocean-canvas');
    const oceanHeader = document.getElementById('ocean-header');
    const ctx = canvas ? canvas.getContext('2d') : null;
    let mouseX = -999;
    let mouseActive = false;
    let phase = 0;

    function resizeCanvas() {
        if (!canvas || !oceanHeader) return;
        canvas.width = oceanHeader.clientWidth;
        canvas.height = oceanHeader.clientHeight;
    }

    if (oceanHeader && canvas && ctx) {
        window.addEventListener('resize', resizeCanvas);
        oceanHeader.addEventListener('mousemove', (e) => {
            const rect = oceanHeader.getBoundingClientRect();
            mouseX = e.clientX - rect.left;
            mouseActive = true;
        });
        oceanHeader.addEventListener('mouseleave', () => {
            mouseActive = false;
        });

        function drawWave() {
            if (canvas.width > 0 && canvas.height > 0) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const layers = [
                    { amp: 6, freq: 0.015, speed: 0.04, yBase: canvas.height * 0.65, color: 'rgba(255,255,255,0.16)' },
                    { amp: 4, freq: 0.022, speed: -0.03, yBase: canvas.height * 0.75, color: 'rgba(255,255,255,0.12)' }
                ];

                layers.forEach((layer, idx) => {
                    ctx.beginPath();
                    ctx.moveTo(0, canvas.height);
                    for (let x = 0; x <= canvas.width; x += 6) {
                        let ripple = 0;
                        if (mouseActive) {
                            const dist = Math.abs(x - mouseX);
                            if (dist < 140) {
                                ripple = Math.cos((dist / 140) * (Math.PI / 2)) * 10 * Math.sin(phase * 2 + idx);
                            }
                        }
                        const y = layer.yBase + Math.sin(x * layer.freq + phase * (idx === 0 ? 1 : -0.8)) * layer.amp + ripple;
                        ctx.lineTo(x, y);
                    }
                    ctx.lineTo(canvas.width, canvas.height);
                    ctx.closePath();
                    ctx.fillStyle = layer.color;
                    ctx.fill();
                });
            }
            phase += 0.04;
            requestAnimationFrame(drawWave);
        }
        resizeCanvas();
        drawWave();
    }
});
</script>
</body>
</html>
