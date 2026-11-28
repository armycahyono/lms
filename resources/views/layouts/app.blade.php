<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <title>@yield('title', 'LMS') — {{ config('app.name') }}</title>
    <meta name="theme-color" content="#195b9e"/>
    <meta name="mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-title" content="LMS PGRI Kras"/>
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Sans:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
    <style>
        :root {
            --tblr-primary: #0284c7;
            --tblr-primary-rgb: 2, 132, 199;
            --tblr-success: #10b981;
            --tblr-warning: #f59e0b;
            --tblr-font-sans-serif: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --lms-bg: #f1f5f9;
            --lms-surface: #ffffff;
            --lms-border: #e2e8f0;
            --lms-primary: #0284c7;
            --lms-primary-lt: #e0f2fe;
            --lms-gradient: linear-gradient(120deg, #0284c7 0%, #10b981 100%);
            --lms-sidebar-width: 256px;
        }

        body {
            font-family: var(--tblr-font-sans-serif);
            background-color: var(--lms-bg);
            color: #0f172a;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, .page-title, .card-title, .navbar-brand {
            font-family: 'Instrument Sans', 'Inter', sans-serif;
        }

        .card {
            border-radius: 18px;
            border: 2px solid var(--lms-border);
            box-shadow: 0 4px 0 #e2e8f0;
        }

        .btn {
            border-radius: 12px;
            font-weight: 600;
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .btn-primary {
            box-shadow: 0 3px 0 #0369a1;
        }

        .btn-primary:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #0369a1;
        }

        .btn-success {
            box-shadow: 0 3px 0 #047857;
        }

        .btn-success:active {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #047857;
        }

        .lms-playful-hero {
            background: var(--lms-gradient);
            border-radius: 22px;
            color: #ffffff;
            border: none;
            box-shadow: 0 8px 24px rgba(2, 132, 199, 0.22);
        }

        .lms-step-dot {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .lms-floating-header-wrap {
            position: sticky;
            top: 6px;
            z-index: 1035;
            padding: 6px 16px 0;
        }

        .lms-floating-header {
            position: relative;
            width: 100%;
            min-height: 62px;
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 24px rgba(3, 105, 161, 0.22);
            display: flex;
            align-items: center;
            padding: 0.5rem 1.25rem;
            --mx: 50%;
            --my: 50%;
        }

        .lms-header-bg {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            overflow: hidden;
            pointer-events: none;
            z-index: 1;
        }

        .lms-header-bg::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: 2;
            background: radial-gradient(260px circle at var(--mx) var(--my), rgba(255, 255, 255, 0.28), transparent 70%);
        }

        .lms-ocean-canvas {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }

        .lms-header-content {
            position: relative;
            z-index: 3;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .lms-header-parallax {
            transition: transform 0.14s ease-out;
            will-change: transform;
        }

        .lms-header-icon-btn {
            width: 38px;
            height: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ffffff !important;
            background: transparent !important;
            border: none !important;
            box-shadow: none !important;
            text-decoration: none !important;
            font-size: 1.35rem;
            transition: transform 0.16s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.15s ease;
            will-change: transform;
        }

        .lms-header-icon-btn:hover,
        .lms-header-icon-btn:focus {
            color: #ffffff !important;
            background: transparent !important;
            border: none !important;
            text-decoration: none !important;
        }

        .lms-header-icon-btn.is-near i {
            animation: lmsBellSwing 0.55s ease-in-out;
        }

        .lms-bell-badge {
            position: absolute;
            top: 2px;
            right: 0;
            z-index: 2;
            min-width: 17px;
            height: 17px;
            padding: 0 4px;
            border-radius: 999px;
            font-size: 0.65rem;
            font-weight: 700;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: #f59e0b;
            color: #ffffff;
            border: 2px solid #0284c7;
            pointer-events: none;
        }

        @keyframes lmsBellSwing {
            0% { transform: rotate(0deg); }
            25% { transform: rotate(16deg); }
            50% { transform: rotate(-14deg); }
            75% { transform: rotate(8deg); }
            100% { transform: rotate(0deg); }
        }

        .lms-header-clock-card,
        .lms-header-ay-card {
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            backdrop-filter: blur(8px);
            border-radius: 12px;
            padding: 0.35rem 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: #ffffff;
            line-height: 1.15;
            min-height: 44px;
        }

        .lms-header-ay-btn {
            cursor: pointer;
            text-align: left;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .lms-header-ay-btn:hover,
        .lms-header-ay-btn[aria-expanded="true"] {
            background: rgba(255, 255, 255, 0.22);
            border-color: rgba(255, 255, 255, 0.38);
        }

        .lms-header-ay-menu {
            min-width: 225px;
            padding: 0.4rem;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 16px 36px rgba(15, 23, 42, 0.14);
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(12px);
            margin-top: 0.5rem !important;
        }

        .lms-header-ay-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.5rem 0.7rem;
            border-radius: 9px;
            font-size: 0.84rem;
            font-weight: 500;
            color: #1e293b;
            transition: background-color 0.12s ease, color 0.12s ease;
        }

        .lms-header-ay-item:hover {
            background-color: #f1f5f9;
            color: #0f172a;
        }

        .lms-header-ay-item.is-selected {
            background-color: #e0f2fe;
            color: #0369a1;
            font-weight: 700;
        }

        .lms-header-clock-time {
            font-family: 'Instrument Sans', 'Inter', sans-serif;
            font-variant-numeric: tabular-nums;
            font-size: 1.15rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            border-left: 1px solid rgba(255, 255, 255, 0.25);
            padding-left: 0.75rem;
        }

        .lms-left-rail {
            width: var(--lms-sidebar-width);
            position: fixed;
            top: 80px;
            bottom: 14px;
            left: 16px;
            z-index: 1030;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .lms-identity-name {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            overflow-wrap: anywhere;
            font-size: 0.92rem;
            line-height: 1.25;
        }

        .lms-page-body {
            margin-top: 0 !important;
            padding-top: 0.75rem !important;
        }

        .lms-toast-container {
            position: fixed;
            top: 80px;
            right: 16px;
            z-index: 1060;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: min(360px, calc(100vw - 24px));
        }

        .lms-toast {
            position: relative;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(12px);
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            animation: lmsToastIn 0.25s cubic-bezier(0.22, 1, 0.36, 1);
            transition: opacity 0.25s ease, transform 0.25s ease;
        }

        .lms-toast.is-hiding {
            opacity: 0;
            transform: translateY(-8px) scale(0.97);
        }

        .lms-toast-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            width: 100%;
            transform-origin: left;
            animation: lmsToastProgress 4.5s linear forwards;
        }

        @keyframes lmsToastIn {
            from { opacity: 0; transform: translateY(-10px) scale(0.96); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        @keyframes lmsToastProgress {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        /* Modern Standard Components (Segmented Pill Tabs & Row Cards) */
        .lms-pill-tabs {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 5px;
            background: #e2e8f0;
            border-radius: 14px;
        }

        .lms-pill-tab {
            border: none;
            background: transparent;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 0.45rem 0.9rem;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            text-decoration: none !important;
            transition: background-color 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        }

        .lms-pill-tab:hover {
            color: #0f172a;
        }

        .lms-pill-tab.active {
            background: #ffffff;
            color: var(--lms-primary);
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
        }

        .lms-row-card {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.85rem 1rem;
            transition: border-color 0.15s ease, transform 0.15s ease;
        }

        .lms-row-card:hover {
            border-color: #bae6fd;
            transform: translateY(-1px);
        }

        .lms-metric-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem 0.65rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.76rem;
            font-weight: 500;
        }

        .lms-namecard {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            border: 1.5px solid #0284c7;
            box-shadow: none;
            flex-shrink: 0;
            width: 100%;
            --nc-accent: #0284c7;
        }

        .lms-namecard.is-preview {
            width: 256px;
            min-width: 256px;
            max-width: 100%;
            user-select: none;
        }

        .lms-nc-anim-layer {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            overflow: hidden;
        }

        .nc-wave-svg,
        .nc-bubble {
            display: none;
        }

        /* Animasi: Gelombang Air */
        .lms-namecard.anim-wave .nc-wave-svg {
            display: block;
            position: absolute;
            bottom: 0;
            left: 0;
            width: 200%;
            height: 46px;
            color: var(--nc-accent);
            opacity: 0.14;
            animation: ncWaveMove 7s linear infinite;
        }

        @keyframes ncWaveMove {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }

        /* Animasi: Kilau Cahaya */
        .lms-namecard.anim-shimmer .lms-nc-anim-layer::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -80%;
            width: 60%;
            height: 200%;
            background: linear-gradient(105deg, transparent 20%, rgba(255, 255, 255, 0.45) 50%, transparent 80%);
            transform: rotate(15deg);
            animation: ncShimmer 4.5s ease-in-out infinite;
        }

        @keyframes ncShimmer {
            0% { left: -80%; }
            60%, 100% { left: 140%; }
        }

        /* Animasi: Gradasi Bergerak */
        .lms-namecard.anim-aurora .lms-nc-anim-layer {
            background: radial-gradient(circle at 20% 20%, color-mix(in srgb, var(--nc-accent) 22%, transparent), transparent 60%),
                        radial-gradient(circle at 80% 80%, color-mix(in srgb, var(--nc-accent) 18%, transparent), transparent 60%);
            background-size: 160% 160%;
            animation: ncAurora 7s ease-in-out infinite alternate;
        }

        @keyframes ncAurora {
            0% { background-position: 0% 0%; }
            100% { background-position: 100% 100%; }
        }

        /* Animasi: Gelembung Halus */
        .lms-namecard.anim-float .nc-bubble {
            display: block;
            position: absolute;
            border-radius: 50%;
            background: var(--nc-accent);
            opacity: 0.12;
            animation: ncFloat 6s ease-in-out infinite;
        }

        .lms-namecard.anim-float .nc-b1 { width: 42px; height: 42px; top: 12px; right: 14px; animation-delay: 0s; }
        .lms-namecard.anim-float .nc-b2 { width: 26px; height: 26px; bottom: 28px; left: 16px; animation-delay: 1.5s; }
        .lms-namecard.anim-float .nc-b3 { width: 18px; height: 18px; bottom: 54px; right: 44px; animation-delay: 3s; }

        @keyframes ncFloat {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-10px) scale(1.08); }
        }

        .lms-namecard-body {
            position: relative;
            z-index: 2;
            padding: 1rem;
        }

        .lms-namecard-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .lms-namecard-header.layout-top {
            flex-direction: column;
            gap: 0.45rem;
        }

        .lms-namecard-header.layout-right {
            flex-direction: row-reverse;
        }

        .lms-namecard-header.layout-none {
            justify-content: center;
            padding-top: 0.25rem;
        }

        .lms-nc-photo-wrap {
            overflow: hidden;
            border: 2.5px solid #0284c7;
            box-shadow: none;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--lms-primary);
            flex-shrink: 0;
        }

        .lms-nc-photo-wrap.is-lg {
            width: 64px;
            height: 64px;
        }

        .lms-nc-photo-wrap.is-sm {
            width: 48px;
            height: 48px;
        }

        .lms-nc-photo-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .lms-namecard.is-dark-contrast {
            color: #ffffff;
        }

        .lms-namecard.is-dark-contrast .text-dark {
            color: #ffffff !important;
        }

        .lms-namecard.is-dark-contrast .text-muted {
            color: rgba(255, 255, 255, 0.75) !important;
        }

        .lms-namecard.is-dark-contrast .border-dark {
            border-color: rgba(255, 255, 255, 0.2) !important;
        }

        .lms-sidebar {
            flex: 1;
            min-height: 0;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.95);
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .lms-sidebar-nav {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .lms-nav-link {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.42rem 0.75rem;
            border-radius: 10px;
            color: #475569;
            font-weight: 500;
            font-size: 0.875rem;
            text-decoration: none !important;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .lms-nav-link:hover,
        .lms-nav-link:focus {
            background-color: #f1f5f9;
            color: #0f172a;
            text-decoration: none !important;
        }

        .lms-nav-link.active {
            background-color: var(--lms-primary-lt);
            color: var(--lms-primary);
            font-weight: 600;
        }

        .lms-nav-link i {
            font-size: 1.2rem;
        }

        @media (min-width: 768px) {
            .lms-main-wrapper {
                margin-left: calc(var(--lms-sidebar-width) + 24px);
            }
        }

        @media (max-width: 767.98px) {
            .lms-floating-header-wrap {
                padding: 6px 12px 0;
                top: 6px;
            }
        }

        .lms-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1035;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--lms-border);
            padding-bottom: env(safe-area-inset-bottom);
            display: flex;
            justify-content: space-around;
            align-items: stretch;
            height: calc(64px + env(safe-area-inset-bottom));
        }

        .lms-bottom-item {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2px;
            text-decoration: none;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 500;
            position: relative;
            padding: 6px 2px;
        }

        .lms-bottom-item.active {
            color: var(--lms-primary);
            font-weight: 600;
        }

        .lms-bottom-item i {
            font-size: 1.35rem;
            line-height: 1;
        }

        .lms-bottom-badge {
            position: absolute;
            top: 6px;
            right: calc(50% - 18px);
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: #ea580c;
            color: #fff;
            font-size: 0.625rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        @media (max-width: 767.98px) {
            .lms-page-body {
                padding-bottom: calc(5.5rem + env(safe-area-inset-bottom)) !important;
            }
        }
    </style>
    @stack('styles')
    @livewireStyles
</head>
<body>
    <div class="page">
        @auth
            @php
                $currentRouteName = request()->route()?->getName() ?? '';
                $currentGuide = null;
                foreach (config('guides', []) as $pattern => $guideData) {
                    if (\Illuminate\Support\Str::is($pattern, $currentRouteName)) {
                        $currentGuide = $guideData;
                        break;
                    }
                }
            @endphp
            {{-- Full-Width Floating Header --}}
            <div class="lms-floating-header-wrap d-print-none">
                <header class="lms-floating-header" id="lms-floating-header">
                    <div class="lms-header-bg">
                        <canvas class="lms-ocean-canvas" id="lms-ocean-canvas"></canvas>
                    </div>

                    <div class="lms-header-content">
                        <div class="d-flex align-items-center gap-3 lms-header-parallax" id="lms-header-title-wrap">
                            <a href="{{ route('dashboard') }}" class="d-flex align-items-center gap-2 text-decoration-none text-white">
                                <span class="badge bg-white text-primary px-2 py-1 fw-bold rounded-3">LMS</span>
                                <span class="fw-bold fs-4 brand-font">SMK PGRI Kras</span>
                            </a>
                            <span class="d-none d-md-inline-flex align-items-center text-white-50 small border-start border-white border-opacity-25 ps-3">
                                <span class="fw-medium text-white">@yield('title', 'Beranda')</span>
                            </span>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            @if(auth()->user()->isGuru() || auth()->user()->isAdmin())
                                <a href="{{ rtrim((string) config('services.siap.url'), '/') }}/dashboard" class="btn btn-sm bg-white bg-opacity-25 text-white border-0 d-inline-flex d-md-none align-items-center gap-1">
                                    <i class="ti ti-arrow-left"></i>
                                    <span>SIAP</span>
                                </a>
                            @endif

                            <a href="{{ route('quizzes.index') }}" class="lms-header-icon-btn d-md-none position-relative" title="Kuis" aria-label="Kuis">
                                <i class="ti ti-file-check"></i>
                            </a>

                            <a href="{{ route('schedule.index') }}" class="lms-header-icon-btn d-md-none position-relative" title="Jadwal & Kalender" aria-label="Jadwal & Kalender">
                                <i class="ti ti-calendar-event"></i>
                            </a>

                            @if($currentGuide)
                                <button type="button" class="btn btn-sm rounded-pill d-inline-flex align-items-center gap-1 px-2 px-md-3 text-white" style="background: rgba(255, 255, 255, 0.16); border: 1px solid rgba(255, 255, 255, 0.28); backdrop-filter: blur(8px); color: #ffffff !important;" data-bs-toggle="offcanvas" data-bs-target="#lms-page-guide-drawer" title="Panduan Halaman" aria-label="Panduan Halaman">
                                    <i class="ti ti-help-circle"></i>
                                    <span class="d-none d-md-inline fw-medium">Panduan</span>
                                </button>
                            @endif

                            <a href="{{ route('notifications.index') }}" class="lms-header-icon-btn d-none d-md-inline-flex position-relative me-1" title="Notifikasi" aria-label="Notifikasi">
                                <i class="ti ti-bell"></i>
                                @if(($navUnreadNotificationsCount ?? 0) > 0)
                                    <span class="lms-bell-badge">{{ $navUnreadNotificationsCount > 99 ? '99+' : $navUnreadNotificationsCount }}</span>
                                @endif
                            </a>

                            @if((auth()->user()->isGuru() || auth()->user()->isAdmin()) && ($navAcademicYears ?? collect())->isNotEmpty())
                                <div class="dropdown d-none d-lg-inline-flex">
                                    <button type="button" class="lms-header-ay-card lms-header-ay-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Pilih Tahun Ajaran">
                                        <div class="d-flex flex-column align-items-start">
                                            <span class="text-white-50" style="font-size: 0.68rem;">Tahun Ajaran</span>
                                            <span class="fw-bold d-inline-flex align-items-center gap-1" style="font-size: 0.85rem;">
                                                {{ $navSelectedYear?->name ?? 'Pilih Tahun' }}
                                                @if(! empty($navSelectedYear) && ! $navSelectedYear->is_active)
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.58rem;">ARSIP</span>
                                                @endif
                                            </span>
                                        </div>
                                        <i class="ti ti-chevron-down opacity-75" style="font-size: 0.95rem;"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end lms-header-ay-menu">
                                        <div class="px-2 py-1 text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.06em;">
                                            Pilih Tahun Ajaran
                                        </div>
                                        @foreach($navAcademicYears as $ay)
                                            @php $isSelectedYear = ($navSelectedYear?->id ?? null) === $ay->id; @endphp
                                            <form action="{{ route('academic-year.switch') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="academic_year_id" value="{{ $ay->id }}">
                                                <button type="submit" class="dropdown-item lms-header-ay-item {{ $isSelectedYear ? 'is-selected' : '' }}">
                                                    <span>{{ $ay->name }}</span>
                                                    @if($ay->is_active)
                                                        <span class="badge bg-success-lt text-success" style="font-size: 0.65rem;">Aktif</span>
                                                    @endif
                                                    @if($isSelectedYear)
                                                        <i class="ti ti-check text-primary ms-auto"></i>
                                                    @endif
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif(!empty($navSelectedYear))
                                <div class="lms-header-ay-card d-none d-lg-inline-flex flex-column align-items-start justify-content-center gap-0">
                                    <span class="text-white-50" style="font-size: 0.68rem;">Tahun Ajaran</span>
                                    <span class="fw-bold" style="font-size: 0.82rem;">{{ $navSelectedYear->name }}</span>
                                </div>
                            @endif

                            <div class="lms-header-clock-card d-none d-md-inline-flex" aria-label="Hari, tanggal, dan jam">
                                <div>
                                    <div class="fw-semibold" style="font-size: 0.72rem;" id="lms-clock-day">{{ now()->locale('id')->translatedFormat('l') }}</div>
                                    <div class="text-white-50" style="font-size: 0.7rem;" id="lms-clock-date">{{ now()->locale('id')->translatedFormat('d M y') }}</div>
                                </div>
                                <div class="lms-header-clock-time" id="lms-clock-time">{{ now()->format('H:i:s') }}</div>
                            </div>
                        </div>
                    </div>
                </header>
            </div>

            {{-- Left Rail: Separate Name Card + Floating Sidebar --}}
            <div class="lms-left-rail d-none d-md-flex d-print-none">
                @if(!empty($navIdentityCard))
                    <x-name-card :card="$navIdentityCard" :user="auth()->user()" />
                @endif

                <aside class="lms-sidebar">
                    <nav class="lms-sidebar-nav p-2 d-flex flex-column" style="gap: 2px;" aria-label="Navigasi Utama">
                        <a href="{{ route('dashboard') }}" class="lms-nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="ti ti-home"></i>
                            <span>Beranda</span>
                        </a>
                        @if(auth()->user()->isOrangTua())
                            <a href="{{ route('parent.index') }}" class="lms-nav-link {{ request()->routeIs('parent.index') ? 'active' : '' }}">
                                <i class="ti ti-home"></i>
                                <span>Beranda</span>
                            </a>
                            <a href="{{ route('parent.attendance') }}" class="lms-nav-link {{ request()->routeIs('parent.attendance') ? 'active' : '' }}">
                                <i class="ti ti-calendar-check"></i>
                                <span>Presensi Sekolah</span>
                            </a>
                            <a href="{{ route('parent.learning') }}" class="lms-nav-link {{ request()->routeIs('parent.learning') ? 'active' : '' }}">
                                <i class="ti ti-book-2"></i>
                                <span>Belajar &amp; Nilai</span>
                            </a>
                            <a href="{{ route('parent.records') }}" class="lms-nav-link {{ request()->routeIs('parent.records') ? 'active' : '' }}">
                                <i class="ti ti-clipboard-heart"></i>
                                <span>Visitasi &amp; Pembinaan</span>
                            </a>
                            <a href="{{ route('parent.communications') }}" class="lms-nav-link {{ request()->routeIs('parent.communications') ? 'active' : '' }}">
                                <i class="ti ti-message-2"></i>
                                <span>Komunikasi</span>
                            </a>
                            <a href="{{ route('parent.profile') }}" class="lms-nav-link {{ request()->routeIs('parent.profile') ? 'active' : '' }}">
                                <i class="ti ti-users"></i>
                                <span>Profil &amp; Data Anak</span>
                            </a>
                        @else
                        <a href="{{ route('courses.index') }}" class="lms-nav-link {{ request()->routeIs('courses.*') ? 'active' : '' }}">
                            <i class="ti ti-books"></i>
                            <span>Kelas Digital</span>
                        </a>
                        <a href="{{ route('tasks.index') }}" class="lms-nav-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                            <i class="ti ti-clipboard-check"></i>
                            <span class="flex-grow-1">Tugas</span>
                            @if(($navPendingTasksCount ?? 0) > 0)
                                <span class="badge bg-warning text-white rounded-pill">{{ $navPendingTasksCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('exams.index') }}" class="lms-nav-link {{ request()->routeIs('exams.*', 'quizzes.*') ? 'active' : '' }}">
                            <i class="ti ti-device-desktop-check"></i>
                            <span>Kuis &amp; Ujian</span>
                        </a>
                        @if(auth()->user()->isGuru() || auth()->user()->isAdmin())
                            <a href="{{ route('question-bank.index') }}" class="lms-nav-link {{ request()->routeIs('question-bank.*') ? 'active' : '' }}">
                                <i class="ti ti-database"></i>
                                <span>Bank Soal</span>
                            </a>
                            <a href="{{ route('teaching-plans.index') }}" class="lms-nav-link {{ request()->routeIs('teaching-plans.*') ? 'active' : '' }}">
                                <i class="ti ti-notebook"></i>
                                <span>Perangkat Ajar</span>
                            </a>
                        @endif
                        <a href="{{ route('projects.index') }}" class="lms-nav-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                            <i class="ti ti-sparkles"></i>
                            <span>Projek / Kokurikuler</span>
                        </a>
                        <a href="{{ route('chat.index') }}" class="lms-nav-link {{ request()->routeIs('chat.*') ? 'active' : '' }}">
                            <i class="ti ti-messages"></i>
                            <span>Ruang Chat</span>
                        </a>
                        <a href="{{ route('schedule.index') }}" class="lms-nav-link {{ request()->routeIs('schedule.*') ? 'active' : '' }}">
                            <i class="ti ti-calendar-event"></i>
                            <span>Jadwal &amp; Kalender</span>
                        </a>
                        <a href="{{ route('progress.index') }}" class="lms-nav-link {{ request()->routeIs('progress.*') ? 'active' : '' }}">
                            <i class="ti ti-chart-line"></i>
                            <span>Progres Belajar</span>
                        </a>
                        @if($navCanMonitor ?? false)
                            <a href="{{ route('monitoring.index') }}" class="lms-nav-link {{ request()->routeIs('monitoring.*') ? 'active' : '' }}">
                                <i class="ti ti-eye-check"></i>
                                <span>Monitoring</span>
                            </a>
                        @endif
                        @if(($navCanMonitor ?? false) || auth()->user()->isGuru())
                            <a href="{{ route('reports.index') }}" class="lms-nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                                <i class="ti ti-file-analytics"></i>
                                <span>Laporan</span>
                            </a>
                            <a href="{{ route('directory.index') }}" class="lms-nav-link {{ request()->routeIs('directory.*') ? 'active' : '' }}">
                                <i class="ti ti-address-book"></i>
                                <span>Data Guru &amp; Siswa</span>
                            </a>
                        @endif
                        @if($navCanHomeroomAnalysis ?? false)
                            <a href="{{ route('homeroom.index') }}" class="lms-nav-link {{ request()->routeIs('homeroom.*') ? 'active' : '' }}">
                                <i class="ti ti-user-heart"></i>
                                <span>Analisa Wali Kelas</span>
                            </a>
                        @endif
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.settings.index') }}" class="lms-nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                <i class="ti ti-settings"></i>
                                <span>Pengaturan Sistem</span>
                            </a>
                            <a href="{{ route('admin.backups.index') }}" class="lms-nav-link {{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">
                                <i class="ti ti-database-export"></i>
                                <span>Backup Data</span>
                            </a>
                        @endif
                        <a href="{{ route('notifications.index') }}" class="lms-nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                            <i class="ti ti-bell"></i>
                            <span class="flex-grow-1">Notifikasi</span>
                            @if(($navUnreadNotificationsCount ?? 0) > 0)
                                <span class="badge bg-primary text-white rounded-pill">{{ $navUnreadNotificationsCount }}</span>
                            @endif
                        </a>
                        <a href="{{ route('profile.show') }}" class="lms-nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                            <i class="ti ti-user-circle"></i>
                            <span>Profil</span>
                        </a>
                        @endif
                    </nav>

                    @if(auth()->user()->isGuru() || auth()->user()->isAdmin())
                        <div class="p-3 border-top flex-shrink-0 bg-white bg-opacity-75">
                            <a href="{{ rtrim((string) config('services.siap.url'), '/') }}/dashboard" class="btn btn-outline-primary w-100 d-inline-flex align-items-center justify-content-center gap-2">
                                <i class="ti ti-arrow-left"></i>
                                <span>Kembali ke SIAP</span>
                            </a>
                        </div>
                    @endif
                </aside>
            </div>

            <x-page-guide :guide="$currentGuide" />
        @endauth

        <div class="page-wrapper @auth lms-main-wrapper @endauth">
            <div class="page-body lms-page-body">
                <div class="container-xl">
                    @if (session('status') || $errors->any())
                        <div class="lms-toast-container" id="lms-toast-container" aria-live="polite">
                            @if (session('status'))
                                <div class="lms-toast" role="status">
                                    <span class="avatar avatar-sm rounded-circle bg-success-lt text-success flex-shrink-0">
                                        <i class="ti ti-check"></i>
                                    </span>
                                    <div class="flex-grow-1 small fw-medium text-dark">{{ session('status') }}</div>
                                    <button type="button" class="btn-close btn-sm js-toast-close" aria-label="Tutup"></button>
                                    <div class="lms-toast-progress bg-success"></div>
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="lms-toast" role="alert">
                                    <span class="avatar avatar-sm rounded-circle bg-danger-lt text-danger flex-shrink-0">
                                        <i class="ti ti-alert-triangle"></i>
                                    </span>
                                    <div class="flex-grow-1 small fw-medium text-dark">{{ $errors->first() }}</div>
                                    <button type="button" class="btn-close btn-sm js-toast-close" aria-label="Tutup"></button>
                                    <div class="lms-toast-progress bg-danger"></div>
                                </div>
                            @endif
                        </div>
                    @endif

                    @auth
                        @if((auth()->user()->isGuru() || auth()->user()->isAdmin()) && ! empty($navSelectedYear) && ! $navSelectedYear->is_active)
                            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 p-3 mb-4 shadow-sm border-0" role="status" style="background-color: #fefce8; border-left: 4px solid #eab308 !important;">
                                <div class="d-flex align-items-center">
                                    <i class="ti ti-history fs-1 text-warning me-3"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Mode Arsip: Anda sedang melihat data Tahun Ajaran {{ $navSelectedYear->name }} ({{ $navSelectedYear->semester }})</div>
                                        <div class="text-muted small">Data kelas, siswa, tugas, kuis, dan rekap yang tampil disesuaikan dengan periode tahun ajaran ini.</div>
                                    </div>
                                </div>
                                @if(! empty($navActiveYear))
                                    <form action="{{ route('academic-year.switch') }}" method="POST" class="flex-shrink-0">
                                        @csrf
                                        <input type="hidden" name="academic_year_id" value="{{ $navActiveYear->id }}">
                                        <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold shadow-sm">
                                            <i class="ti ti-rotate-2 me-1"></i> Kembali ke Tahun Aktif ({{ $navActiveYear->name }})
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    @endauth

                    @yield('content')
                </div>
            </div>
        </div>

        @auth
            {{-- Mobile Bottom Navigation (5 Menu Utama) --}}
            <nav class="lms-bottom-nav d-md-none d-print-none" aria-label="Navigasi Mobile">
                <a href="{{ route('dashboard') }}" class="lms-bottom-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="ti ti-home"></i>
                    <span>Beranda</span>
                </a>
                <a href="{{ route('courses.index') }}" class="lms-bottom-item {{ request()->routeIs('courses.*') ? 'active' : '' }}">
                    <i class="ti ti-books"></i>
                    <span>Kelas</span>
                </a>
                <a href="{{ route('tasks.index') }}" class="lms-bottom-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                    <i class="ti ti-clipboard-check"></i>
                    <span>Tugas</span>
                    @if(($navPendingTasksCount ?? 0) > 0)
                        <span class="lms-bottom-badge">{{ $navPendingTasksCount > 99 ? '99+' : $navPendingTasksCount }}</span>
                    @endif
                </a>
                <a href="{{ route('notifications.index') }}" class="lms-bottom-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                    <i class="ti ti-bell"></i>
                    <span>Notifikasi</span>
                    @if(($navUnreadNotificationsCount ?? 0) > 0)
                        <span class="lms-bottom-badge">{{ $navUnreadNotificationsCount > 99 ? '99+' : $navUnreadNotificationsCount }}</span>
                    @endif
                </a>
                <a href="{{ route('profile.show') }}" class="lms-bottom-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                    <i class="ti ti-user-circle"></i>
                    <span>Profil</span>
                </a>
            </nav>
        @endauth
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const header = document.getElementById('lms-floating-header');
        const canvas = document.getElementById('lms-ocean-canvas');
        const titleWrap = document.getElementById('lms-header-title-wrap');
        if (!header || !canvas) return;

        const ctx = canvas.getContext('2d');
        let mouseX = -999;
        let mouseActive = false;
        let phase = 0;

        const resizeCanvas = () => {
            canvas.width = header.clientWidth;
            canvas.height = header.clientHeight;
        };

        window.addEventListener('resize', resizeCanvas);

        header.addEventListener('mousemove', (e) => {
            const rect = header.getBoundingClientRect();
            const relX = e.clientX - rect.left;
            const relY = e.clientY - rect.top;
            const pctX = (relX / rect.width) * 100;
            const pctY = (relY / rect.height) * 100;

            header.style.setProperty('--mx', `${pctX}%`);
            header.style.setProperty('--my', `${pctY}%`);

            mouseX = relX;
            mouseActive = true;

            if (titleWrap) {
                const offsetX = ((relX / rect.width) - 0.5) * 14;
                const offsetY = ((relY / rect.height) - 0.5) * 6;
                titleWrap.style.transform = `translate3d(${offsetX.toFixed(2)}px, ${offsetY.toFixed(2)}px, 0)`;
            }

            header.querySelectorAll('.lms-header-icon-btn').forEach((iconBtn) => {
                const btnRect = iconBtn.getBoundingClientRect();
                const cx = btnRect.left + btnRect.width / 2;
                const cy = btnRect.top + btnRect.height / 2;
                const dx = e.clientX - cx;
                const dy = e.clientY - cy;
                const dist = Math.hypot(dx, dy);

                if (dist < 110) {
                    const strength = (1 - dist / 110);
                    const moveX = (dx / 110) * 10 * strength;
                    const moveY = (dy / 110) * 8 * strength;
                    const scale = 1 + strength * 0.18;
                    iconBtn.style.transform = `translate3d(${moveX.toFixed(2)}px, ${moveY.toFixed(2)}px, 0) scale(${scale.toFixed(3)})`;
                    iconBtn.classList.add('is-near');
                } else {
                    iconBtn.style.transform = 'translate3d(0, 0, 0) scale(1)';
                    iconBtn.classList.remove('is-near');
                }
            });
        });

        header.addEventListener('mouseleave', () => {
            mouseActive = false;
            if (titleWrap) {
                titleWrap.style.transform = 'translate3d(0, 0, 0)';
            }
            header.querySelectorAll('.lms-header-icon-btn').forEach((iconBtn) => {
                iconBtn.style.transform = 'translate3d(0, 0, 0) scale(1)';
                iconBtn.classList.remove('is-near');
            });
        });

        const drawWave = () => {
            if (ctx && canvas.width > 0 && canvas.height > 0) {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                const layers = [
                    { amp: 6, freq: 0.015, yBase: canvas.height * 0.65, color: 'rgba(255,255,255,0.16)', dir: 1 },
                    { amp: 4, freq: 0.022, yBase: canvas.height * 0.76, color: 'rgba(255,255,255,0.12)', dir: -0.8 }
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
                        const y = layer.yBase + Math.sin(x * layer.freq + phase * layer.dir) * layer.amp + ripple;
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
        };

        resizeCanvas();
        drawWave();

        // Live Indonesian Day, Date & Digital Clock (HH:MM)
        const dayEl = document.getElementById('lms-clock-day');
        const dateEl = document.getElementById('lms-clock-date');
        const timeEl = document.getElementById('lms-clock-time');

        const updateClock = () => {
            if (!dayEl || !dateEl || !timeEl) return;
            const now = new Date();
            dayEl.textContent = new Intl.DateTimeFormat('id-ID', { weekday: 'long' }).format(now);
            dateEl.textContent = new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short', year: '2-digit' }).format(now);
            const hh = String(now.getHours()).padStart(2, '0');
            const mm = String(now.getMinutes()).padStart(2, '0');
            const ss = String(now.getSeconds()).padStart(2, '0');
            timeEl.textContent = `${hh}:${mm}:${ss}`;
        };

        updateClock();
        setInterval(updateClock, 1000);

        // Auto-dismiss modern floating toasts
        document.querySelectorAll('.lms-toast').forEach((toast) => {
            const dismiss = () => {
                toast.classList.add('is-hiding');
                setTimeout(() => toast.remove(), 250);
            };
            toast.querySelector('.js-toast-close')?.addEventListener('click', dismiss);
            setTimeout(dismiss, 4500);
        });

        // Enable Bootstrap tooltips globally for micro-help icons
        if (window.bootstrap?.Tooltip) {
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
                new window.bootstrap.Tooltip(el);
            });
        }

        // Render KaTeX math formulas on question/stimulus containers
        if (typeof window.renderMathInElement === 'function') {
            document.querySelectorAll('.katex-render').forEach((el) => {
                window.renderMathInElement(el, {
                    delimiters: [
                        { left: '$$', right: '$$', display: true },
                        { left: '$', right: '$', display: false },
                    ],
                    throwOnError: false,
                });
            });
        }
    });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js" onload="document.querySelectorAll('.katex-render').forEach((el) => renderMathInElement(el, { delimiters: [{ left: '$$', right: '$$', display: true }, { left: '$', right: '$', display: false }], throwOnError: false }));"></script>
    @stack('scripts')
    @livewireScripts
</body>
</html>
