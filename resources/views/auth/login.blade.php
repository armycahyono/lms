@extends('layouts.app')

@section('title', 'Masuk LMS')

@section('content')
<div class="row justify-content-center mt-5">
    <div class="col-md-5 col-lg-4">
        <div class="card card-md shadow-sm">
            <div class="card-body">
                <div class="text-center mb-4">
                    <span class="badge bg-primary text-white px-3 py-2 fs-5 fw-bold mb-2">LMS</span>
                    <h2 class="h3 mb-1">Portal Pembelajaran Digital</h2>
                    <p class="text-muted small mb-0">SMK PGRI Kras</p>
                </div>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login') }}" method="POST" autocomplete="off">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">NISN / Username</label>
                        <input type="text" name="username" value="{{ old('username') }}" class="form-control" placeholder="Masukkan NISN Anda" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi</label>
                        <input type="password" name="password" class="form-control" placeholder="Kata sandi" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input">
                            <span class="form-check-label small">Ingat saya di perangkat ini</span>
                        </label>
                    </div>
                    <div class="form-footer">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold">
                            <i class="ti ti-login me-1"></i> Masuk sebagai Siswa
                        </button>
                    </div>
                </form>

                <hr class="my-4">
                <div class="text-center small text-muted">
                    Guru &amp; Pegawai silakan masuk melalui aplikasi
                    <a href="{{ rtrim((string) config('services.siap.url'), '/') }}" class="fw-semibold">SIAP</a>
                    lalu klik tombol <strong>BUKA LMS</strong>.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
