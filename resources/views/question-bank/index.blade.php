@extends('layouts.app')

@section('title', 'Bank Soal')

@section('content')
<div class="page-header d-print-none mb-4">
    <div class="row align-items-center g-2">
        <div class="col">
            <div class="page-pretitle">Evaluasi Pembelajaran</div>
            <h2 class="page-title">Bank Soal</h2>
        </div>
        <div class="col-auto d-flex flex-wrap gap-2">
            <div class="btn-group">
                <a href="{{ route('question-bank.template-word.download') }}" class="btn btn-outline-info">
                    <i class="ti ti-file-word me-1"></i> Template Word (.docx)
                </a>
                <a href="{{ route('question-bank.template.download') }}" class="btn btn-outline-success">
                    <i class="ti ti-file-spreadsheet me-1"></i> Template Excel (.xlsx)
                </a>
            </div>
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-import-qb">
                <i class="ti ti-upload me-1"></i> Impor Massal
            </button>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal-add-question">
                <i class="ti ti-plus me-1"></i> Tambah Soal
            </button>
        </div>
    </div>
</div>

<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Kelompok Tipe Soal">
        <a href="{{ route('question-bank.index', array_merge(request()->except('page'), ['type' => 'all'])) }}" class="lms-pill-tab {{ $activeType === 'all' ? 'active' : '' }}">
            <i class="ti ti-apps"></i>
            <span>Semua Tipe</span>
            <span class="badge bg-blue-lt rounded-pill">{{ $totalAllTypes }}</span>
        </a>
        @foreach(\App\Models\QuestionBank::TYPES as $typeKey => $typeLabel)
            <a href="{{ route('question-bank.index', array_merge(request()->except('page'), ['type' => $typeKey])) }}" class="lms-pill-tab {{ $activeType === $typeKey ? 'active' : '' }}">
                <span>{{ $typeLabel }}</span>
                <span class="badge bg-blue-lt rounded-pill">{{ (int) ($typeCounts[$typeKey] ?? 0) }}</span>
            </a>
        @endforeach
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('question-bank.index') }}" class="row g-2 align-items-center">
            <input type="hidden" name="type" value="{{ $activeType }}">
            <div class="col-md-2">
                <select name="subject_id" class="form-select form-select-sm">
                    <option value="">Semua Mapel</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" @selected((int) $filters['subject_id'] === $subject->id)>{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="grade_level" class="form-select form-select-sm">
                    <option value="">Semua Tingkat</option>
                    @foreach($gradeLevels as $gl)
                        <option value="{{ $gl }}" @selected((string) $filters['grade_level'] === (string) $gl)>Kelas {{ $gl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="department" class="form-select form-select-sm">
                    <option value="">Semua Jurusan</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <input type="text" name="category" value="{{ $filters['category'] }}" class="form-control form-control-sm" placeholder="Filter Bab/Kategori">
            </div>
            <div class="col-md-1">
                <select name="difficulty" class="form-select form-select-sm">
                    <option value="">Kesulitan</option>
                    <option value="mudah" @selected($filters['difficulty'] === 'mudah')>Mudah</option>
                    <option value="sedang" @selected($filters['difficulty'] === 'sedang')>Sedang</option>
                    <option value="sulit" @selected($filters['difficulty'] === 'sulit')>Sulit</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="visibility" class="form-select form-select-sm">
                    <option value="">Semua Visibilitas</option>
                    <option value="private" @selected($filters['visibility'] === 'private')>Privat</option>
                    <option value="shared" @selected($filters['visibility'] === 'shared')>Dibagikan</option>
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="vstack gap-4">
    @php $rowCounter = ($questions->firstItem() ?? 1) - 1; @endphp
    @forelse($questionGradeGroups as $gradeGrp)
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-indigo text-white fs-6 px-3 py-2">
                    {{ $gradeGrp['grade_level'] ? 'Kelas '.$gradeGrp['grade_level'] : 'Tingkat Umum / Lainnya' }}
                </span>
                <span class="text-muted small">{{ $gradeGrp['subjects']->count() }} Mapel &middot; {{ $gradeGrp['total_questions'] }} Soal</span>
            </div>
            <div class="vstack gap-3">
                @foreach($gradeGrp['subjects'] as $subjGrp)
                    <div class="card">
                        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h3 class="card-title mb-0">{{ $subjGrp['subject_name'] }}</h3>
                                <span class="badge bg-blue-lt">{{ $subjGrp['questions']->count() }} Soal</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table lms-table table-vcenter mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 56px;">No.</th>
                                        <th>Soal, Media &amp; Kisi-kisi</th>
                                        <th>Tipe &amp; Kesulitan</th>
                                        <th class="text-center">Poin / Kunci</th>
                                        <th class="text-center">Visibilitas</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($subjGrp['questions'] as $q)
                                        @php
                                            $isOwner = auth()->user()->isAdmin() || $q->teacher_id === auth()->id();
                                            $rowCounter++;
                                            $editPayload = [
                                                'id' => $q->id,
                                                'update_url' => route('question-bank.update', $q),
                                                'subject_id' => $q->subject_id,
                                                'grade_level' => $q->grade_level,
                                                'department' => $q->department,
                                                'category' => $q->category,
                                                'kisi_indicator' => $q->kisi_indicator,
                                                'difficulty' => $q->difficulty,
                                                'type' => $q->type,
                                                'question_text' => $q->question_text,
                                                'question_image_url' => $q->question_image ? route('question-bank.media', ['path' => $q->question_image]) : null,
                                                'options_json' => $q->options_json ?? [],
                                                'option_images_urls' => collect($q->option_images_json ?? [])->mapWithKeys(fn ($path, $k) => [$k => route('question-bank.media', ['path' => $path])])->all(),
                                                'correct_answer' => $q->correct_answer,
                                                'points' => $q->points,
                                                'is_shared' => (bool) $q->is_shared,
                                            ];
                                        @endphp
                                        <tr>
                                            <td class="text-center fw-bold text-muted">{{ $rowCounter }}</td>
                                            <td>
                                                <div class="fw-semibold text-dark small katex-render">{!! \Illuminate\Support\Str::limit(strip_tags((string) $q->question_text), 130) !!}</div>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    @if($q->department)
                                                        <span class="badge bg-purple-lt">{{ $q->department }}</span>
                                                    @endif
                                                    @if($q->question_image)
                                                        <span class="badge bg-cyan-lt"><i class="ti ti-photo me-1"></i>Gambar Soal</span>
                                                    @endif
                                                    @if(!empty($q->option_images_json))
                                                        <span class="badge bg-teal-lt"><i class="ti ti-photo-check me-1"></i>{{ count($q->option_images_json) }} Gambar Opsi</span>
                                                    @endif
                                                </div>
                                                @if($q->kisi_indicator)
                                                    <div class="text-primary small mt-1" style="font-size: 0.74rem;">Kisi-kisi: {{ $q->kisi_indicator }}</div>
                                                @endif
                                                @if($q->category)
                                                    <div class="text-muted" style="font-size: 0.72rem;">Bab: {{ $q->category }}</div>
                                                @endif
                                            </td>
                                            <td class="small">
                                                <span class="badge bg-azure-lt">{{ $q->typeLabel() }}</span>
                                                <span class="badge bg-warning-lt text-uppercase">{{ $q->difficulty }}</span>
                                            </td>
                                            <td class="text-center small">
                                                <div class="fw-semibold">{{ $q->points }} poin</div>
                                                @if($q->correct_answer)
                                                    <div class="text-muted" style="font-size: 0.72rem;">Kunci: <strong>{{ \Illuminate\Support\Str::limit($q->correct_answer, 24) }}</strong></div>
                                                @endif
                                                <div class="mt-1 d-flex justify-content-center gap-1 flex-wrap" style="font-size: 0.7rem;">
                                                    <span class="badge bg-indigo-lt">Dipakai {{ $q->usage_count }}x</span>
                                                    @if($q->accuracyPercent() !== null)
                                                        <span class="badge bg-azure-lt">Benar {{ $q->accuracyPercent() }}%</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $q->is_shared ? 'bg-success-lt' : 'bg-secondary-lt' }}">
                                                    {{ $q->is_shared ? 'Dibagikan' : 'Privat' }}
                                                </span>
                                                @if($q->teacher_id !== auth()->id())
                                                    <div class="text-muted mt-1" style="font-size: 0.7rem;">{{ $q->teacher?->name ?? '-' }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($isOwner)
                                                    <div class="d-inline-flex gap-1">
                                                        <a href="{{ route('question-bank.analysis', $q) }}" class="btn btn-sm btn-outline-info" title="Analisis &amp; Riwayat Butir Soal">
                                                            <i class="ti ti-chart-bar"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-primary js-edit-qb" data-question='@json($editPayload)' title="Edit Soal">
                                                            <i class="ti ti-pencil"></i>
                                                        </button>
                                                        <form action="{{ route('question-bank.toggle-share', $q) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm {{ $q->is_shared ? 'btn-outline-success' : 'btn-outline-secondary' }}" title="{{ $q->is_shared ? 'Jadikan Privat' : 'Bagikan ke Rekan Mapel' }}">
                                                                <i class="ti {{ $q->is_shared ? 'ti-world' : 'ti-lock' }}"></i>
                                                            </button>
                                                        </form>
                                                        <form action="{{ route('question-bank.destroy', $q) }}" method="POST" onsubmit="return confirm('Hapus soal ini dari Bank Soal?')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm btn-ghost-danger"><i class="ti ti-trash"></i></button>
                                                        </form>
                                                    </div>
                                                @else
                                                    <span class="text-muted small">Referensi</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5 small">
                <i class="ti ti-database-off fs-1 d-block mb-2 opacity-50"></i>
                Belum ada soal pada filter/tipe ini.
            </div>
        </div>
    @endforelse
</div>

@if($questions->hasPages())
    <div class="mt-3">{{ $questions->links() }}</div>
@endif

{{-- Modal Tambah Soal --}}
<div class="modal modal-blur fade" id="modal-add-question" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form action="{{ route('question-bank.store') }}" method="POST" enctype="multipart/form-data" class="js-qb-form">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Soal ke Bank Soal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-grid gap-3">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="subject_id" class="form-select">
                                @if(auth()->user()->isAdmin() || $subjects->count() > 1)
                                    <option value="">Semua Mapel Diampu</option>
                                @endif
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tingkat</label>
                            <select name="grade_level" class="form-select">
                                @if(auth()->user()->isAdmin() || count($gradeLevels) > 1)
                                    <option value="">Semua</option>
                                @endif
                                @foreach($gradeLevels as $gl)
                                    <option value="{{ $gl }}">Kelas {{ $gl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jurusan / Rombel</label>
                            <select name="department" class="form-select">
                                <option value="">Semua Jurusan Diampu</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Tipe Soal (Standar CBTPGRI)</label>
                            <select name="type" class="form-select js-qb-type">
                                <option value="multiple_choice">Pilihan Ganda (A-E)</option>
                                <option value="multiple_choice_complex">PG Kompleks (Multi Jawaban)</option>
                                <option value="matching">Menjodohkan</option>
                                <option value="short_answer">Isian Singkat</option>
                                <option value="essay">Uraian / Esai</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kesulitan</label>
                            <select name="difficulty" class="form-select">
                                <option value="mudah">Mudah</option>
                                <option value="sedang" selected>Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Poin</label>
                            <input type="number" step="0.5" name="points" value="10" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategori / Bab</label>
                            <input type="text" name="category" class="form-control" placeholder="Contoh: Bab 1 Teks LHO">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Kisi-Kisi / Indikator Soal (Opsional)</label>
                        <input type="text" name="kisi_indicator" class="form-control" placeholder="Contoh: Siswa mampu membedakan kalimat fakta dan opini">
                    </div>

                    <div>
                        <label class="form-label">Pertanyaan (Mendukung Format Paragraf Bahasa &amp; Rumus Matematika LaTeX `$...$`)</label>
                        <div class="btn-toolbar gap-1 mb-1 js-rte-toolbar">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" data-cmd="bold" title="Tebal"><strong>B</strong></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="italic" title="Miring"><em>I</em></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="underline" title="Garis Bawah"><u>U</u></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="insertUnorderedList" title="Daftar"><i class="ti ti-list"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="justifyLeft" title="Rata Kiri"><i class="ti ti-align-left"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="justifyCenter" title="Rata Tengah"><i class="ti ti-align-center"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="justifyFull" title="Rata Kanan-Kiri"><i class="ti ti-align-justified"></i></button>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\frac{a}{b}$">Pecahan</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$x^{2}$">Pangkat</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\sqrt{x}$">Akar</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\sum_{i=1}^{n} x_i$">Sigma</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\int_{a}^{b} f(x)\,dx$">Integral</button>
                            </div>
                        </div>
                        <div class="form-control js-rte-editor" contenteditable="true" style="min-height: 110px; max-height: 220px; overflow-y: auto;"></div>
                        <textarea name="question_text" rows="2" class="form-control d-none js-rte-input" required></textarea>
                    </div>

                    <div>
                        <label class="form-label">Gambar Soal (Opsional, Maks. 4MB)</label>
                        <input type="file" name="question_image" class="form-control" accept="image/*">
                    </div>

                    <div class="js-qb-options row g-2">
                        @foreach(['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd', 'E' => 'e'] as $letter => $lower)
                            <div class="col-md-6">
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text fw-bold">{{ $letter }}</span>
                                    <input type="text" name="option_{{ $lower }}" class="form-control" placeholder="Teks Opsi {{ $letter }}">
                                </div>
                                <input type="file" name="option_image_{{ $lower }}" class="form-control form-control-sm" accept="image/*" title="Gambar Opsi {{ $letter }} (opsional)">
                            </div>
                        @endforeach
                    </div>

                    <div class="js-qb-matching d-none">
                        <label class="form-label">Pasangan Menjodohkan (Format: Kiri=Kanan dipisah tanda |)</label>
                        <textarea name="matching_pairs" rows="2" class="form-control" placeholder="Krisis=Masalah lucu|Koda=Penutup|Orientasi=Latar cerita"></textarea>
                    </div>

                    <div class="js-qb-answer">
                        <label class="form-label">Kunci Jawaban</label>
                        <input type="text" name="correct_answer" class="form-control" placeholder="PG: A..E | PG Kompleks: A,C | Isian: teks jawaban">
                    </div>

                    <label class="form-check">
                        <input type="checkbox" name="is_shared" value="1" class="form-check-input">
                        <span class="form-check-label">Bagikan ke rekan guru mata pelajaran ini (default: Privat)</span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Simpan ke Bank Soal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Edit Soal --}}
<div class="modal modal-blur fade" id="modal-edit-question" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form id="form-edit-qb" action="" method="POST" enctype="multipart/form-data" class="js-qb-form">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Soal Bank Soal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-grid gap-3">
                    <div class="row g-2">
                        <div class="col-md-5">
                            <label class="form-label">Mata Pelajaran</label>
                            <select name="subject_id" class="form-select">
                                @if(auth()->user()->isAdmin() || $subjects->count() > 1)
                                    <option value="">Semua Mapel Diampu</option>
                                @endif
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Tingkat</label>
                            <select name="grade_level" class="form-select">
                                @if(auth()->user()->isAdmin() || count($gradeLevels) > 1)
                                    <option value="">Semua</option>
                                @endif
                                @foreach($gradeLevels as $gl)
                                    <option value="{{ $gl }}">Kelas {{ $gl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jurusan / Rombel</label>
                            <select name="department" class="form-select">
                                <option value="">Semua Jurusan Diampu</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Tipe Soal (Standar CBTPGRI)</label>
                            <select name="type" class="form-select js-qb-type">
                                <option value="multiple_choice">Pilihan Ganda (A-E)</option>
                                <option value="multiple_choice_complex">PG Kompleks (Multi Jawaban)</option>
                                <option value="matching">Menjodohkan</option>
                                <option value="short_answer">Isian Singkat</option>
                                <option value="essay">Uraian / Esai</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Kesulitan</label>
                            <select name="difficulty" class="form-select">
                                <option value="mudah">Mudah</option>
                                <option value="sedang">Sedang</option>
                                <option value="sulit">Sulit</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Poin</label>
                            <input type="number" step="0.5" name="points" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kategori / Bab</label>
                            <input type="text" name="category" class="form-control">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Kisi-Kisi / Indikator Soal</label>
                        <input type="text" name="kisi_indicator" class="form-control">
                    </div>

                    <div>
                        <label class="form-label">Pertanyaan (Rich Text &amp; LaTeX `$...$`)</label>
                        <div class="btn-toolbar gap-1 mb-1 js-rte-toolbar">
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" data-cmd="bold"><strong>B</strong></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="italic"><em>I</em></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="underline"><u>U</u></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="insertUnorderedList"><i class="ti ti-list"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="justifyLeft"><i class="ti ti-align-left"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="justifyCenter"><i class="ti ti-align-center"></i></button>
                                <button type="button" class="btn btn-outline-secondary" data-cmd="justifyFull"><i class="ti ti-align-justified"></i></button>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\frac{a}{b}$">Pecahan</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$x^{2}$">Pangkat</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\sqrt{x}$">Akar</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\sum_{i=1}^{n} x_i$">Sigma</button>
                                <button type="button" class="btn btn-outline-azure js-insert-math" data-math="$\int_{a}^{b} f(x)\,dx$">Integral</button>
                            </div>
                        </div>
                        <div class="form-control js-rte-editor" contenteditable="true" style="min-height: 110px; max-height: 220px; overflow-y: auto;"></div>
                        <textarea name="question_text" rows="2" class="form-control d-none js-rte-input" required></textarea>
                    </div>

                    <div>
                        <label class="form-label">Gambar Soal (Opsional)</label>
                        <div class="js-edit-qimg-preview mb-2 d-none">
                            <img src="" alt="Preview Gambar Soal" class="rounded border d-block mb-1" style="max-height: 90px;">
                            <label class="form-check form-check-inline small">
                                <input type="checkbox" name="remove_question_image" value="1" class="form-check-input">
                                <span class="form-check-label text-danger">Hapus gambar soal ini</span>
                            </label>
                        </div>
                        <input type="file" name="question_image" class="form-control" accept="image/*">
                    </div>

                    <div class="js-qb-options row g-2">
                        @foreach(['A' => 'a', 'B' => 'b', 'C' => 'c', 'D' => 'd', 'E' => 'e'] as $letter => $lower)
                            <div class="col-md-6">
                                <div class="input-group input-group-sm mb-1">
                                    <span class="input-group-text fw-bold">{{ $letter }}</span>
                                    <input type="text" name="option_{{ $lower }}" class="form-control" placeholder="Teks Opsi {{ $letter }}">
                                </div>
                                <div class="js-edit-optimg-{{ $lower }} mb-1 d-none">
                                    <img src="" alt="Gambar Opsi {{ $letter }}" class="rounded border me-2" style="max-height: 48px;">
                                    <label class="form-check form-check-inline small">
                                        <input type="checkbox" name="remove_option_image_{{ $lower }}" value="1" class="form-check-input">
                                        <span class="form-check-label text-danger">Hapus gambar</span>
                                    </label>
                                </div>
                                <input type="file" name="option_image_{{ $lower }}" class="form-control form-control-sm" accept="image/*">
                            </div>
                        @endforeach
                    </div>

                    <div class="js-qb-matching d-none">
                        <label class="form-label">Pasangan Menjodohkan (Format: Kiri=Kanan dipisah tanda |)</label>
                        <textarea name="matching_pairs" rows="2" class="form-control"></textarea>
                    </div>

                    <div class="js-qb-answer">
                        <label class="form-label">Kunci Jawaban</label>
                        <input type="text" name="correct_answer" class="form-control">
                    </div>

                    <label class="form-check">
                        <input type="checkbox" name="is_shared" value="1" class="form-check-input">
                        <span class="form-check-label">Bagikan ke rekan guru mata pelajaran ini</span>
                    </label>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Modal Impor Massal (Word .docx & Excel .xlsx) --}}
<div class="modal modal-blur fade" id="modal-import-qb" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('question-bank.import.preview') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Impor Soal Massal (Microsoft Word &amp; Excel)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body d-grid gap-3">
                    <div class="alert alert-info py-2 small mb-0">
                        Pilih konteks soal di bawah agar kolom file lebih ringkas dan seragam. Unduh:
                        <a href="{{ route('question-bank.template-word.download') }}" class="fw-bold">Template Word (.docx)</a> (mendukung gambar &amp; paragraf rapi) atau
                        <a href="{{ route('question-bank.template.download') }}" class="fw-bold">Template Excel (.xlsx)</a>.
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Format File</label>
                            <select name="file_format" class="form-select">
                                <option value="word">Microsoft Word (.docx)</option>
                                <option value="excel">Microsoft Excel (.xlsx / .xls)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Mata Pelajaran (Konteks)</label>
                            <select name="subject_id" class="form-select">
                                @if(auth()->user()->isAdmin() || $subjects->count() > 1)
                                    <option value="">Sesuai File / Mapel Diampu</option>
                                @endif
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tipe Soal (Konteks)</label>
                            <select name="type" class="form-select">
                                <option value="">Sesuai File / Campuran</option>
                                @foreach(\App\Models\QuestionBank::TYPES as $typeKey => $typeLabel)
                                    <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Tingkat Kelas</label>
                            <select name="grade_level" class="form-select">
                                @if(auth()->user()->isAdmin() || count($gradeLevels) > 1)
                                    <option value="">Sesuai File / Semua</option>
                                @endif
                                @foreach($gradeLevels as $gl)
                                    <option value="{{ $gl }}">Kelas {{ $gl }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Jurusan</label>
                            <select name="department" class="form-select">
                                <option value="">Semua Jurusan</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}">{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <label class="form-check mb-2">
                                <input type="checkbox" name="is_shared" value="1" class="form-check-input">
                                <span class="form-check-label small">Bagikan semua ke rekan mapel</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="form-label">Pilih File (.docx / .xlsx / .xls)</label>
                        <input type="file" name="import_file" class="form-control" accept=".docx,.xlsx,.xls" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-eye me-1"></i> Pratinjau Impor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const syncTypeVisibility = (form) => {
        const typeSelect = form.querySelector('.js-qb-type');
        const optionsBox = form.querySelector('.js-qb-options');
        const matchingBox = form.querySelector('.js-qb-matching');
        const answerBox = form.querySelector('.js-qb-answer');
        if (!typeSelect) return;
        const val = typeSelect.value;
        optionsBox?.classList.toggle('d-none', !['multiple_choice', 'multiple_choice_complex'].includes(val));
        matchingBox?.classList.toggle('d-none', val !== 'matching');
        answerBox?.classList.toggle('d-none', ['matching', 'essay'].includes(val));
    };

    document.querySelectorAll('.js-qb-form').forEach((form) => {
        const typeSelect = form.querySelector('.js-qb-type');
        typeSelect?.addEventListener('change', () => syncTypeVisibility(form));
        syncTypeVisibility(form);

        const editor = form.querySelector('.js-rte-editor');
        const hiddenInput = form.querySelector('.js-rte-input');
        const syncEditorToInput = () => {
            if (editor && hiddenInput) {
                hiddenInput.value = editor.innerHTML.trim();
            }
        };

        editor?.addEventListener('input', syncEditorToInput);
        form.addEventListener('submit', syncEditorToInput);

        form.querySelectorAll('.js-rte-toolbar button[data-cmd]').forEach((btn) => {
            btn.addEventListener('click', () => {
                editor?.focus();
                document.execCommand(btn.dataset.cmd, false, null);
                syncEditorToInput();
            });
        });

        form.querySelectorAll('.js-insert-math').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (!editor) return;
                editor.focus();
                document.execCommand('insertText', false, ' ' + btn.dataset.math + ' ');
                syncEditorToInput();
            });
        });
    });

    const editModalEl = document.getElementById('modal-edit-question');
    const editForm = document.getElementById('form-edit-qb');
    document.querySelectorAll('.js-edit-qb').forEach((btn) => {
        btn.addEventListener('click', () => {
            const data = JSON.parse(btn.dataset.question);
            editForm.action = data.update_url;
            editForm.querySelector('[name="subject_id"]').value = data.subject_id ?? '';
            editForm.querySelector('[name="grade_level"]').value = data.grade_level ?? '';
            editForm.querySelector('[name="department"]').value = data.department ?? '';
            editForm.querySelector('[name="category"]').value = data.category ?? '';
            editForm.querySelector('[name="kisi_indicator"]').value = data.kisi_indicator ?? '';
            editForm.querySelector('[name="difficulty"]').value = data.difficulty ?? 'sedang';
            editForm.querySelector('[name="type"]').value = data.type ?? 'multiple_choice';
            editForm.querySelector('[name="points"]').value = data.points ?? 10;
            editForm.querySelector('[name="correct_answer"]').value = data.correct_answer ?? '';
            editForm.querySelector('[name="is_shared"]').checked = Boolean(data.is_shared);

            const editor = editForm.querySelector('.js-rte-editor');
            const hiddenInput = editForm.querySelector('.js-rte-input');
            editor.innerHTML = data.question_text ?? '';
            hiddenInput.value = data.question_text ?? '';

            const qImgBox = editForm.querySelector('.js-edit-qimg-preview');
            if (data.question_image_url) {
                qImgBox.classList.remove('d-none');
                qImgBox.querySelector('img').src = data.question_image_url;
                qImgBox.querySelector('[name="remove_question_image"]').checked = false;
            } else {
                qImgBox.classList.add('d-none');
            }

            const opts = data.options_json || {};
            const optImgs = data.option_images_urls || {};
            ['A', 'B', 'C', 'D', 'E'].forEach((letter) => {
                const lower = letter.toLowerCase();
                const textInput = editForm.querySelector(`[name="option_${lower}"]`);
                if (textInput) textInput.value = opts[letter] ?? '';
                const imgBox = editForm.querySelector(`.js-edit-optimg-${lower}`);
                if (imgBox) {
                    if (optImgs[letter]) {
                        imgBox.classList.remove('d-none');
                        imgBox.querySelector('img').src = optImgs[letter];
                        imgBox.querySelector(`[name="remove_option_image_${lower}"]`).checked = false;
                    } else {
                        imgBox.classList.add('d-none');
                    }
                }
            });

            if (data.type === 'matching' && opts && typeof opts === 'object') {
                const pairs = Object.values(opts).map((p) => `${p.left}=${p.right}`).join('|');
                editForm.querySelector('[name="matching_pairs"]').value = pairs;
            } else {
                editForm.querySelector('[name="matching_pairs"]').value = '';
            }

            syncTypeVisibility(editForm);
            bootstrap.Modal.getOrCreateInstance(editModalEl).show();
        });
    });
});
</script>
@endpush
