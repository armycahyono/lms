@extends('layouts.app')

@section('title', 'Profil')

@push('styles')
<style>
    .preview-photo-draggable {
        cursor: grab;
    }

    .preview-photo-draggable:active {
        cursor: grabbing;
    }

    .tpl-option-input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .tpl-option-card {
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 0.6rem 0.75rem;
        cursor: pointer;
        transition: border-color 0.15s ease, background-color 0.15s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .tpl-option-input:checked + .tpl-option-card {
        border-color: var(--lms-primary);
        background: var(--lms-primary-lt);
        color: var(--lms-primary);
    }
</style>
@endpush

@section('content')
@php
    $profile = $user->profile;
    $settings = is_array($profile?->card_settings) ? $profile->card_settings : [];
    $currentTemplate = $settings['template'] ?? 'classic';
    $photoPos = $settings['photo_pos'] ?? 'top';
    $photoShape = $settings['photo_shape'] ?? 'circle';
    $accentColor = $settings['accent_color'] ?? '#0284c7';
    $bgColor = $settings['bg_color'] ?? '#ffffff';
    $animation = $settings['animation'] ?? 'none';
    $avatarScale = (int) ($settings['avatar_scale'] ?? 100);
    $avatarPosX = (int) ($settings['avatar_pos_x'] ?? 50);
    $avatarPosY = (int) ($settings['avatar_pos_y'] ?? 50);
    $activeProfileTab = request('tab') === 'notepad' ? 'notepad' : 'profile';
@endphp

<div class="page-header d-print-none mb-4">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Akun Pengguna</div>
            <h2 class="page-title">Profil &amp; Kartu Nama</h2>
        </div>
    </div>
</div>

<div class="mb-3 overflow-auto">
    <div class="lms-pill-tabs" role="tablist" aria-label="Menu Profil">
        <button type="button" class="lms-pill-tab {{ $activeProfileTab === 'profile' ? 'active' : '' }} js-profile-tab" data-target="tab-profile" role="tab" aria-selected="{{ $activeProfileTab === 'profile' ? 'true' : 'false' }}">
            <i class="ti ti-id-badge-2"></i>
            <span>Profil &amp; Kartu Nama</span>
        </button>
        <button type="button" class="lms-pill-tab {{ $activeProfileTab === 'notepad' ? 'active' : '' }} js-profile-tab" data-target="tab-notepad" role="tab" aria-selected="{{ $activeProfileTab === 'notepad' ? 'true' : 'false' }}">
            <i class="ti ti-notes"></i>
            <span>Notepad Perbaikan</span>
        </button>
    </div>
</div>

<div class="tab-content">
<div class="tab-pane {{ $activeProfileTab === 'profile' ? 'active' : 'd-none' }}" id="tab-profile">
<div class="row row-cards">
    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-4">
                    @if($profile?->avatar_path)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($profile->avatar_path) }}" alt="{{ $user->name }}" class="avatar avatar-lg rounded-circle object-cover">
                    @else
                        <span class="avatar avatar-lg bg-primary text-white fw-bold rounded-circle">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </span>
                    @endif
                    <div>
                        <h3 class="mb-0">{{ $user->name }}</h3>
                        <div class="text-muted small text-uppercase">{{ $user->role }}</div>
                    </div>
                </div>

                <dl class="row mb-0 small">
                    <dt class="col-5 text-muted">Username / ID</dt>
                    <dd class="col-7"><code>{{ $user->username }}</code></dd>

                    <dt class="col-5 text-muted">Email</dt>
                    <dd class="col-7">{{ $user->email ?? '-' }}</dd>

                    <dt class="col-5 text-muted">No. HP</dt>
                    <dd class="col-7">{{ $profile?->phone ?? '-' }}</dd>

                    <dt class="col-5 text-muted">Alamat</dt>
                    <dd class="col-7">{{ $profile?->address ?? '-' }}</dd>

                    <dt class="col-5 text-muted">Tahun Ajaran</dt>
                    <dd class="col-7">{{ $activeYear ? $activeYear->name : '-' }}</dd>

                    @if($user->isSiswa() && $user->classrooms->isNotEmpty())
                        <dt class="col-5 text-muted">Kelas</dt>
                        <dd class="col-7">{{ $user->classrooms->pluck('name')->join(', ') }}</dd>
                    @endif
                </dl>

                <hr class="my-4">

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100">
                        <i class="ti ti-logout me-1"></i> Keluar dari LMS
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h3 class="card-title mb-0">Desain Kartu Nama</h3>
                <span class="badge bg-blue-lt">Pratinjau 1:1</span>
            </div>
            <div class="card-body">
                <form action="{{ route('profile.identity.update') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="avatar_pos_x" id="ctrl-avatar-x" value="{{ $avatarPosX }}">
                    <input type="hidden" name="avatar_pos_y" id="ctrl-avatar-y" value="{{ $avatarPosY }}">

                    <div class="row g-4 mb-4 align-items-start">
                        <div class="col-md-6 d-flex flex-column align-items-center">
                            <x-name-card :card="$navIdentityCard ?? []" :user="$user" :is-preview="true" />
                            <div class="text-muted text-center mt-2" style="font-size: 0.72rem;">
                                Seret foto di kartu untuk memosisikan, scroll/geser zoom untuk memperbesar.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Pilih Template Kartu Nama</label>
                                <div class="row g-2">
                                    @foreach($nameCardTemplates as $key => $tpl)
                                        <div class="col-6">
                                            <label class="w-100">
                                                <input type="radio" name="template" value="{{ $key }}"
                                                    class="tpl-option-input js-tpl-radio"
                                                    data-photo-pos="{{ $tpl['photo_pos'] }}"
                                                    data-photo-shape="{{ $tpl['photo_shape'] }}"
                                                    data-accent="{{ $tpl['accent_color'] }}"
                                                    data-bg="{{ $tpl['bg_color'] }}"
                                                    data-animation="{{ $tpl['animation'] }}"
                                                    @checked($currentTemplate === $key)>
                                                <span class="tpl-option-card">
                                                    <span class="rounded-circle" style="width: 14px; height: 14px; flex-shrink: 0; background: {{ $tpl['bg_color'] }}; border: 2px solid {{ $tpl['accent_color'] }};"></span>
                                                    <span class="text-truncate">{{ $tpl['label'] }}</span>
                                                </span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Custom Builder Panel --}}
                            <div id="custom-builder-panel" class="bg-light rounded p-3 mb-3 {{ $currentTemplate === 'custom' ? '' : 'd-none' }}">
                                <div class="small fw-bold text-primary mb-2">Racik Template Sendiri</div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Posisi Foto</label>
                                        <select name="photo_pos" id="ctrl-photo-pos" class="form-select form-select-sm js-custom-input">
                                            <option value="top" @selected($photoPos === 'top')>Atas Tengah</option>
                                            <option value="left" @selected($photoPos === 'left')>Samping Kiri</option>
                                            <option value="right" @selected($photoPos === 'right')>Samping Kanan</option>
                                            <option value="none" @selected($photoPos === 'none')>Tanpa Foto</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Bentuk Foto</label>
                                        <select name="photo_shape" id="ctrl-photo-shape" class="form-select form-select-sm js-custom-input">
                                            <option value="circle" @selected($photoShape === 'circle')>Lingkaran</option>
                                            <option value="rounded" @selected($photoShape === 'rounded')>Kotak Membulat</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Warna Aksen</label>
                                        <input type="color" name="accent_color" id="ctrl-accent-color" value="{{ $accentColor }}" class="form-control form-control-color w-100 js-custom-input">
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Warna Kartu</label>
                                        <input type="color" name="bg_color" id="ctrl-bg-color" value="{{ $bgColor }}" class="form-control form-control-color w-100 js-custom-input">
                                    </div>
                                </div>
                                <div>
                                    <label class="form-label small mb-1">Model Animasi Latar</label>
                                    <select name="animation" id="ctrl-animation" class="form-select form-select-sm js-custom-input">
                                        <option value="none" @selected($animation === 'none')>Polos (Tanpa Animasi)</option>
                                        <option value="wave" @selected($animation === 'wave')>Gelombang Air</option>
                                        <option value="shimmer" @selected($animation === 'shimmer')>Kilau Cahaya</option>
                                        <option value="aurora" @selected($animation === 'aurora')>Gradasi Bergerak</option>
                                        <option value="float" @selected($animation === 'float')>Gelembung Halus</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="avatar">Foto Profil</label>
                                <input type="file" id="avatar" name="avatar" class="form-control form-control-sm" accept="image/*">
                                @if($profile?->avatar_path)
                                    <label class="form-check mt-1">
                                        <input type="checkbox" name="remove_avatar" value="1" class="form-check-input">
                                        <span class="form-check-label small text-danger">Hapus foto saat ini</span>
                                    </label>
                                @endif
                            </div>

                            <div class="bg-light rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small fw-semibold">Zoom Foto Profil</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" id="reset-avatar-btn">Reset</button>
                                </div>
                                <input type="range" name="avatar_scale" id="ctrl-avatar-scale" min="100" max="220" value="{{ $avatarScale }}" class="form-range">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Kartu Nama
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Ubah Kata Sandi</h3>
            </div>
            <div class="card-body">
                <form action="{{ route('profile.password.update') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password">Kata Sandi Baru</label>
                        <input type="password" id="password" name="password" class="form-control" minlength="8" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" minlength="8" required>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i> Simpan Kata Sandi
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<div class="tab-pane {{ $activeProfileTab === 'notepad' ? 'active' : 'd-none' }}" id="tab-notepad">
    <div class="card">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h3 class="card-title mb-0"><i class="ti ti-notes me-1"></i> Notepad Catatan Perbaikan</h3>
                <div class="text-muted small">Simpan daftar bug, ide perbaikan UI/UX, atau catatan tindak lanjut pribadi Anda.</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-notepad-timestamp">
                    <i class="ti ti-clock-plus me-1"></i> Sisipkan Waktu
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-notepad-copy">
                    <i class="ti ti-copy me-1"></i> Salin Semua
                </button>
            </div>
        </div>
        <form action="{{ route('profile.notepad.update') }}" method="POST">
            @csrf
            <div class="card-body">
                <textarea name="notepad" id="profile-notepad-input" rows="14" class="form-control font-monospace" placeholder="Tulis catatan perbaikan di sini...&#10;&#10;Contoh:&#10;- [Perlu Perbaikan] Cek tampilan mobile halaman tugas&#10;- [Ide] Tambahkan ekspor rekap nilai per tingkat">{{ old('notepad', $profile?->notepad) }}</textarea>
                <div class="d-flex flex-wrap justify-content-between align-items-center mt-2 text-muted small">
                    <span id="notepad-char-count">0 karakter</span>
                    <span>
                        @if($profile?->updated_at)
                            Terakhir diperbarui: {{ $profile->updated_at->translatedFormat('d M Y, H:i') }}
                        @else
                            Belum pernah disimpan
                        @endif
                    </span>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy me-1"></i> Simpan Catatan
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
    const profileTabs = document.querySelectorAll('.js-profile-tab');
    const profilePanes = document.querySelectorAll('.tab-content .tab-pane');
    profileTabs.forEach((btn) => {
        btn.addEventListener('click', () => {
            profileTabs.forEach((t) => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            profilePanes.forEach((p) => {
                p.classList.remove('active');
                p.classList.add('d-none');
            });
            btn.classList.add('active');
            btn.setAttribute('aria-selected', 'true');
            const target = document.getElementById(btn.dataset.target);
            if (target) {
                target.classList.add('active');
                target.classList.remove('d-none');
            }
        });
    });

    const notepadInput = document.getElementById('profile-notepad-input');
    const charCountEl = document.getElementById('notepad-char-count');
    const timestampBtn = document.getElementById('btn-notepad-timestamp');
    const copyBtn = document.getElementById('btn-notepad-copy');

    const updateCharCount = () => {
        if (notepadInput && charCountEl) {
            charCountEl.textContent = `${notepadInput.value.length.toLocaleString('id-ID')} karakter`;
        }
    };
    updateCharCount();
    notepadInput?.addEventListener('input', updateCharCount);

    timestampBtn?.addEventListener('click', () => {
        if (!notepadInput) return;
        const now = new Date();
        const stamp = `[${now.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' })} ${now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })}] `;
        const start = notepadInput.selectionStart ?? notepadInput.value.length;
        const end = notepadInput.selectionEnd ?? start;
        const prefix = start > 0 && notepadInput.value[start - 1] !== '\n' ? '\n' : '';
        notepadInput.setRangeText(`${prefix}${stamp}`, start, end, 'end');
        notepadInput.focus();
        updateCharCount();
    });

    copyBtn?.addEventListener('click', async () => {
        if (!notepadInput) return;
        try {
            await navigator.clipboard.writeText(notepadInput.value);
            const orig = copyBtn.innerHTML;
            copyBtn.innerHTML = '<i class="ti ti-check me-1"></i> Tersalin';
            setTimeout(() => { copyBtn.innerHTML = orig; }, 1500);
        } catch (e) {}
    });
    const cardBox = document.getElementById('live-card-preview');
    const headerLayout = document.getElementById('preview-header-layout');
    const avatarWrap = document.getElementById('preview-avatar-wrap');
    const avatarInput = document.getElementById('avatar');
    const avatarImg = document.getElementById('live-avatar-img');
    const avatarInitial = document.getElementById('live-avatar-initial');

    const aScale = document.getElementById('ctrl-avatar-scale');
    const aX = document.getElementById('ctrl-avatar-x');
    const aY = document.getElementById('ctrl-avatar-y');

    const customPanel = document.getElementById('custom-builder-panel');
    const photoPosEl = document.getElementById('ctrl-photo-pos');
    const photoShapeEl = document.getElementById('ctrl-photo-shape');
    const accentEl = document.getElementById('ctrl-accent-color');
    const bgEl = document.getElementById('ctrl-bg-color');
    const animEl = document.getElementById('ctrl-animation');
    const statusLabel = document.getElementById('preview-status-label');

    const clamp = (val, min, max) => Math.max(min, Math.min(max, val));

    const isDarkHex = (hex) => {
        const m = /^#([0-9a-fA-F]{6})$/.exec(hex || '');
        if (!m) return false;
        const r = parseInt(m[1].substring(0, 2), 16);
        const g = parseInt(m[1].substring(2, 4), 16);
        const b = parseInt(m[1].substring(4, 6), 16);
        return (0.299 * r + 0.587 * g + 0.114 * b) / 255 < 0.55;
    };

    const renderPreview = (pos, shape, accent, bg, anim) => {
        if (!cardBox) return;
        cardBox.style.backgroundColor = bg;
        cardBox.style.borderColor = accent;
        cardBox.style.setProperty('--nc-accent', accent);
        cardBox.classList.toggle('is-dark-contrast', isDarkHex(bg));

        cardBox.classList.remove('anim-none', 'anim-wave', 'anim-shimmer', 'anim-aurora', 'anim-float');
        cardBox.classList.add(`anim-${anim || 'none'}`);

        if (statusLabel) {
            statusLabel.style.setProperty('color', accent, 'important');
        }

        if (headerLayout) {
            headerLayout.classList.remove('layout-top', 'layout-left', 'layout-right', 'layout-none');
            headerLayout.classList.add(`layout-${pos}`);
        }

        if (avatarWrap) {
            avatarWrap.classList.toggle('d-none', pos === 'none');
            avatarWrap.classList.toggle('is-lg', pos === 'top');
            avatarWrap.classList.toggle('is-sm', pos !== 'top');
            avatarWrap.style.borderRadius = shape === 'rounded' ? '12px' : '50%';
            avatarWrap.style.borderColor = accent;
        }
    };

    const applyAvatarTransform = () => {
        if (!avatarImg) return;
        const tx = (Number(aX.value) - 50) * 0.6;
        const ty = (Number(aY.value) - 50) * 0.6;
        avatarImg.style.transform = `translate(${tx}%, ${ty}%) scale(${Number(aScale.value) / 100})`;
    };

    document.querySelectorAll('.js-tpl-radio').forEach((radio) => {
        radio.addEventListener('change', () => {
            const isCustom = radio.value === 'custom';
            customPanel?.classList.toggle('d-none', !isCustom);
            if (isCustom) {
                renderPreview(photoPosEl.value, photoShapeEl.value, accentEl.value, bgEl.value, animEl.value);
            } else {
                renderPreview(radio.dataset.photoPos, radio.dataset.photoShape, radio.dataset.accent, radio.dataset.bg, radio.dataset.animation);
            }
        });
    });

    document.querySelectorAll('.js-custom-input').forEach((el) => {
        el.addEventListener('input', () => {
            renderPreview(photoPosEl.value, photoShapeEl.value, accentEl.value, bgEl.value, animEl.value);
        });
        el.addEventListener('change', () => {
            renderPreview(photoPosEl.value, photoShapeEl.value, accentEl.value, bgEl.value, animEl.value);
        });
    });

    // Direct drag & wheel zoom on avatar photo
    let dragging = false;
    let startX = 0;
    let startY = 0;
    let startPosX = 50;
    let startPosY = 50;

    if (avatarWrap) {
        avatarWrap.addEventListener('mousedown', (e) => {
            e.preventDefault();
            dragging = true;
            startX = e.clientX;
            startY = e.clientY;
            startPosX = Number(aX.value);
            startPosY = Number(aY.value);
        });
        window.addEventListener('mousemove', (e) => {
            if (!dragging) return;
            aX.value = clamp(Math.round(startPosX + (e.clientX - startX) * 1.4), 0, 100);
            aY.value = clamp(Math.round(startPosY + (e.clientY - startY) * 1.4), 0, 100);
            applyAvatarTransform();
        });
        window.addEventListener('mouseup', () => { dragging = false; });

        avatarWrap.addEventListener('wheel', (e) => {
            e.preventDefault();
            aScale.value = clamp(Number(aScale.value) + (e.deltaY < 0 ? 5 : -5), 100, 220);
            applyAvatarTransform();
        }, { passive: false });
    }

    aScale?.addEventListener('input', applyAvatarTransform);

    document.getElementById('reset-avatar-btn')?.addEventListener('click', () => {
        aScale.value = 100;
        aX.value = 50;
        aY.value = 50;
        applyAvatarTransform();
    });

    if (avatarInput && avatarImg) {
        avatarInput.addEventListener('change', (e) => {
            const file = e.target.files?.[0];
            if (!file) return;
            avatarImg.src = URL.createObjectURL(file);
            avatarImg.classList.remove('d-none');
            if (avatarInitial) avatarInitial.classList.add('d-none');
            applyAvatarTransform();
        });
    }
});
</script>
@endpush
