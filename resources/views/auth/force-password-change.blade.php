@extends('layouts.app')

@section('title', 'Wajib Ganti Kata Sandi')

@section('content')
<div class="row justify-content-center mt-4">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="h3 mb-2">Perbarui Kata Sandi Awal</h2>
                <p class="text-muted small mb-4">
                    Untuk keamanan akun Anda, silakan buat kata sandi baru (minimal 8 karakter) sebelum mengakses kelas dan materi pembelajaran.
                </p>

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('password.force-change.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Kata Sandi Baru</label>
                        <input type="password" name="password" class="form-control" required minlength="8" autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-semibold">
                        Simpan Kata Sandi &amp; Lanjutkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
