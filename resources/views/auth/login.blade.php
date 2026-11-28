@extends('layouts.app')

@section('title', 'Masuk LMS')

@push('styles')
<style>
    .login-role-switch {
        background: #f1f5f9;
        padding: 5px;
        border-radius: 14px;
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }

    .login-role-btn {
        border: none;
        border-radius: 10px;
        padding: 0.6rem 0.75rem;
        font-weight: 600;
        font-size: 0.9rem;
        color: #64748b;
        background: transparent;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .login-role-btn.active {
        background: #ffffff;
        color: var(--lms-primary);
        box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center align-items-center py-4 py-md-5">
    <div class="col-sm-10 col-md-7 col-lg-5 col-xl-4">
        <div class="card overflow-hidden">
            <div style="height: 8px; background: var(--lms-gradient);"></div>
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="badge bg-primary text-white px-3 py-2 fs-5 fw-bold mb-2 rounded-3">LMS</span>
                    <h1 class="h2 mb-1">Portal Pembelajaran Digital</h1>
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
                        <label class="form-label" for="username">Username / NISN</label>
                        <input id="username" type="text" name="username" value="{{ old('username') }}" class="form-control" placeholder="Masukkan username atau NISN" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Kata Sandi</label>
                        <div class="input-group input-group-flat">
                            <input id="password" type="password" name="password" class="form-control" placeholder="Masukkan kata sandi" required>
                            <span class="input-group-text">
                                <a href="#" class="link-secondary toggle-password-btn" data-target="password" title="Lihat kata sandi" aria-label="Lihat kata sandi">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-check">
                            <input type="checkbox" name="remember" class="form-check-input">
                            <span class="form-check-label small">Ingat saya di perangkat ini</span>
                        </label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="ti ti-login me-1"></i> Masuk ke LMS
                    </button>
                </form>

                <hr class="my-4">
                <p class="text-muted text-center small fw-medium mb-0">
                    Asah Keterampilan – Teguhkan Keimanan – Raih Kesuksesan
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.toggle-password-btn').forEach((toggleBtn) => {
        toggleBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const input = document.getElementById(toggleBtn.dataset.target);
            const icon = toggleBtn.querySelector('i');
            if (!input || !icon) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.classList.toggle('ti-eye', !isPassword);
            icon.classList.toggle('ti-eye-off', isPassword);
        });
    });
});
</script>
@endpush
