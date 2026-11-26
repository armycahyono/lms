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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
    @livewireStyles
</head>
<body class="bg-light">
    <div class="page">
        @auth
        <header class="navbar navbar-expand-md d-print-none bg-white border-bottom sticky-top">
            <div class="container-xl">
                <a href="{{ route('dashboard') }}" class="navbar-brand d-flex align-items-center gap-2 text-decoration-none">
                    <span class="badge bg-primary text-white px-2 py-1 fw-bold">LMS</span>
                    <span class="fw-bold text-dark">SMK PGRI Kras</span>
                </a>

                <div class="navbar-nav flex-row me-auto ms-3 gap-1 d-none d-md-flex">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active fw-semibold text-primary' : '' }}">
                        <i class="ti ti-home me-1"></i> Beranda
                    </a>
                    <a href="{{ route('courses.index') }}" class="nav-link {{ request()->routeIs('courses.*') ? 'active fw-semibold text-primary' : '' }}">
                        <i class="ti ti-books me-1"></i> Kelas Digital
                    </a>
                </div>

                <div class="navbar-nav flex-row order-md-last align-items-center gap-2">
                    @if(auth()->user()->isGuru() || auth()->user()->isAdmin())
                        <a href="{{ rtrim((string) config('services.siap.url'), '/') }}/dashboard" class="btn btn-sm btn-outline-primary fw-semibold d-inline-flex align-items-center gap-1">
                            <i class="ti ti-arrow-left"></i>
                            <span class="d-none d-sm-inline">KEMBALI KE SIAP</span>
                        </a>
                    @endif

                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-1" data-bs-toggle="dropdown">
                            <span class="avatar avatar-sm bg-primary text-white fw-bold">
                                {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                            </span>
                            <div class="d-none d-md-block ps-2">
                                <div class="fw-semibold text-dark small">{{ auth()->user()->name }}</div>
                                <div class="text-muted text-uppercase" style="font-size: 0.7rem;">{{ auth()->user()->role }}</div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="{{ route('courses.index') }}" class="dropdown-item d-md-none">
                                <i class="ti ti-books me-2"></i> Kelas Digital
                            </a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="ti ti-logout me-2"></i> Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        @endauth

        <div class="page-wrapper">
            <div class="page-body">
                <div class="container-xl">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible" role="alert">
                            {{ session('status') }}
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible" role="alert">
                            {{ $errors->first() }}
                            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta20/dist/js/tabler.min.js"></script>
    @livewireScripts
</body>
</html>
