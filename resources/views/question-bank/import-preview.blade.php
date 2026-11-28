@extends('layouts.app')

@section('title', 'Pratinjau Impor Bank Soal')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">
                <a href="{{ route('question-bank.index') }}" class="text-decoration-none">Bank Soal</a>
            </div>
            <h2 class="page-title">Pratinjau Impor Massal Bank Soal ({{ strtoupper($importFormat ?? 'EXCEL') }})</h2>
        </div>
        <div class="col-auto">
            <a href="{{ route('question-bank.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i> Batal &amp; Kembali
            </a>
        </div>
    </div>
</div>

@if(!empty($parsed['errors']))
    <div class="alert alert-danger mb-4">
        <h4 class="alert-title">Ditemukan {{ count($parsed['errors']) }} kesalahan pada file impor</h4>
        <ul class="mb-0 ps-3 small">
            @foreach($parsed['errors'] as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if(!empty($parsed['duplicates']))
    <div class="alert alert-warning mb-4">
        <h4 class="alert-title">{{ count($parsed['duplicates']) }} soal duplikat terdeteksi dan akan dilewati</h4>
        <ul class="mb-0 ps-3 small">
            @foreach($parsed['duplicates'] as $dup)
                <li>{{ $dup }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Soal Siap Diimpor ({{ count($parsed['rows']) }})</h3>
        @if(empty($parsed['errors']) && !empty($parsed['rows']))
            <form action="{{ route('question-bank.import.confirm') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-check me-1"></i> Konfirmasi &amp; Simpan {{ count($parsed['rows']) }} Soal
                </button>
            </form>
        @endif
    </div>
    <div class="table-responsive">
        <table class="table lms-table align-middle mb-0">
            <thead>
                <tr>
                    <th>Mapel, Tingkat &amp; Jurusan</th>
                    <th>Tipe</th>
                    <th>Pertanyaan, Gambar &amp; Kisi-kisi</th>
                    <th>Kunci</th>
                    <th class="text-center">Visibilitas</th>
                </tr>
            </thead>
            <tbody>
                @forelse($parsed['rows'] as $row)
                    <tr>
                        <td class="small">
                            <span class="badge bg-blue-lt">{{ $row['subject_name'] }}</span>
                            @if(!empty($row['grade_level']))
                                <span class="badge bg-secondary-lt">Kelas {{ $row['grade_level'] }}</span>
                            @endif
                            @if(!empty($row['department']))
                                <span class="badge bg-purple-lt">{{ $row['department'] }}</span>
                            @endif
                        </td>
                        <td><span class="badge bg-azure-lt">{{ $row['type_label'] }}</span></td>
                        <td>
                            <div class="fw-semibold small katex-render">{!! $row['question_text'] !!}</div>
                            @if(!empty($row['question_image']))
                                <div class="mt-1">
                                    <img src="{{ route('question-bank.media', ['path' => $row['question_image']]) }}" alt="Gambar Soal" class="rounded border" style="max-height: 70px;">
                                </div>
                            @endif
                            @if(!empty($row['option_images_json']))
                                <div class="mt-1 d-flex flex-wrap gap-1">
                                    @foreach($row['option_images_json'] as $optLetter => $optPath)
                                        <span class="badge bg-teal-lt">Gbr Opsi {{ $optLetter }}</span>
                                    @endforeach
                                </div>
                            @endif
                            @if(!empty($row['kisi_indicator']))
                                <div class="text-muted small">Kisi-kisi: {{ $row['kisi_indicator'] }}</div>
                            @endif
                        </td>
                        <td class="small">{{ \Illuminate\Support\Str::limit((string) ($row['correct_answer'] ?? '-'), 40) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $row['is_shared'] ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                {{ $row['is_shared'] ? 'Dibagikan' : 'Privat' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada soal valid untuk diimpor.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
