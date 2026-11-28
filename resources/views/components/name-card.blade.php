@props([
    'card' => [],
    'user' => null,
    'isPreview' => false,
])

@php
    $user = $user ?? auth()->user();
    $settings = $card['card_settings'] ?? [];
    $templates = \App\Http\Controllers\ProfileController::nameCardTemplates();

    $templateKey = $settings['template'] ?? 'classic';
    if (! isset($templates[$templateKey])) {
        $templateKey = 'classic';
    }
    $tpl = $templates[$templateKey];

    $isCustom = $templateKey === 'custom';
    $photoPos = $isCustom ? ($settings['photo_pos'] ?? 'top') : $tpl['photo_pos'];
    $photoShape = $isCustom ? ($settings['photo_shape'] ?? 'circle') : $tpl['photo_shape'];
    $accentColor = $isCustom ? ($settings['accent_color'] ?? '#0284c7') : $tpl['accent_color'];
    $bgColor = $isCustom ? ($settings['bg_color'] ?? '#ffffff') : $tpl['bg_color'];
    $animation = $isCustom ? ($settings['animation'] ?? 'none') : $tpl['animation'];

    $isDark = \App\Http\Controllers\ProfileController::isDarkHex($bgColor);

    $avatarScale = ((int) ($settings['avatar_scale'] ?? 100)) / 100;
    $avatarPosX = (int) ($settings['avatar_pos_x'] ?? 50);
    $avatarPosY = (int) ($settings['avatar_pos_y'] ?? 50);
    $avatarTx = ($avatarPosX - 50) * 0.6;
    $avatarTy = ($avatarPosY - 50) * 0.6;

    $photoSizeClass = $photoPos === 'top' ? 'is-lg' : 'is-sm';
    $photoRadius = $photoShape === 'rounded' ? '12px' : '50%';
@endphp

<div @if($isPreview) id="live-card-preview" @endif
    class="lms-namecard anim-{{ $animation }} {{ $isDark ? 'is-dark-contrast' : '' }} {{ $isPreview ? 'is-preview' : '' }}"
    style="background-color: {{ $bgColor }}; border-color: {{ $accentColor }}; --nc-accent: {{ $accentColor }};">

    <div class="lms-nc-anim-layer" aria-hidden="true">
        <span class="nc-bubble nc-b1"></span>
        <span class="nc-bubble nc-b2"></span>
        <span class="nc-bubble nc-b3"></span>
        <svg class="nc-wave-svg" viewBox="0 0 600 60" preserveAspectRatio="none">
            <path d="M0,30 C100,52 200,8 300,30 C400,52 500,8 600,30 L600,60 L0,60 Z" fill="currentColor"/>
        </svg>
    </div>

    <div class="lms-namecard-body">
        {{-- Header Section (Photo + Name + Status) --}}
        <div class="lms-namecard-header layout-{{ $photoPos }}" @if($isPreview) id="preview-header-layout" @endif>
            @if($photoPos !== 'none')
                <div class="lms-nc-photo-wrap {{ $photoSizeClass }} {{ $isPreview ? 'preview-photo-draggable' : '' }}"
                    @if($isPreview) id="preview-avatar-wrap" @endif
                    style="border-radius: {{ $photoRadius }}; border-color: {{ $accentColor }};">
                    @if(!empty($card['photo']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($card['photo']) }}"
                            alt="{{ $card['name'] ?? '' }}"
                            @if($isPreview) id="live-avatar-img" draggable="false" @endif
                            class="lms-nc-photo-img"
                            style="transform: translate({{ $avatarTx }}%, {{ $avatarTy }}%) scale({{ $avatarScale }});">
                    @else
                        @if($isPreview)
                            <img src="" alt="Preview" id="live-avatar-img" class="lms-nc-photo-img d-none" draggable="false">
                        @endif
                        <span @if($isPreview) id="live-avatar-initial" @endif class="text-white fw-bold {{ $photoPos === 'top' ? 'fs-2' : 'fs-4' }}">
                            {{ strtoupper(substr($card['name'] ?? ($user?->name ?? 'U'), 0, 1)) }}
                        </span>
                    @endif
                </div>
            @elseif($isPreview)
                <div class="lms-nc-photo-wrap is-lg d-none preview-photo-draggable" id="preview-avatar-wrap" style="border-radius: {{ $photoRadius }};">
                    @if(!empty($card['photo']))
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($card['photo']) }}" alt="Preview" id="live-avatar-img" class="lms-nc-photo-img" draggable="false">
                    @else
                        <img src="" alt="Preview" id="live-avatar-img" class="lms-nc-photo-img d-none" draggable="false">
                        <span id="live-avatar-initial" class="text-white fw-bold fs-2">{{ strtoupper(substr($card['name'] ?? ($user?->name ?? 'U'), 0, 1)) }}</span>
                    @endif
                </div>
            @endif

            <div class="min-w-0 flex-grow-1 {{ $photoPos === 'top' ? 'text-center' : 'text-start' }}">
                <div class="fw-bold lms-identity-name text-dark" @if(!$isPreview) id="lms-identity-name" @endif title="{{ $card['name'] ?? '' }}">
                    {{ $card['name'] ?? '' }}
                </div>
                <div class="text-primary" @if($isPreview) id="preview-status-label" @endif style="font-size: 0.72rem; color: {{ $accentColor }} !important;">
                    {{ $card['status'] ?? '' }}
                </div>
            </div>
        </div>

        {{-- Identity Details (Role-based) --}}
        <div class="d-flex flex-column gap-1 pt-2 mt-2 border-top border-dark border-opacity-10" style="font-size: 0.73rem; line-height: 1.35;">
            @if($user && $user->isSiswa())
                <div class="d-flex justify-content-between gap-2">
                    <span class="text-muted">NISN</span>
                    <span class="text-dark text-end text-truncate">{{ $card['id_number'] ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <span class="text-muted">Kelas</span>
                    <span class="text-dark text-end text-truncate">{{ $card['classroom'] ?? '-' }}</span>
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <span class="text-muted">Wali Kelas</span>
                    <span class="text-dark text-end text-truncate">{{ $card['wali_kelas'] ?? '-' }}</span>
                </div>
            @elseif($user && $user->isGuru())
                <div class="d-flex justify-content-between gap-2">
                    <span class="text-muted">NIGS / NIP</span>
                    <span class="text-dark text-end text-truncate">{{ $card['id_number'] ?? '-' }}</span>
                </div>
                @if(($card['classroom'] ?? '-') !== '-')
                    <div class="d-flex justify-content-between gap-2">
                        <span class="text-muted">Wali Kelas</span>
                        <span class="text-dark text-end text-truncate">{{ $card['classroom'] }}</span>
                    </div>
                @endif
            @else
                <div class="d-flex justify-content-between gap-2">
                    <span class="text-muted">Username</span>
                    <span class="text-dark text-end text-truncate">{{ $card['id_number'] ?? '-' }}</span>
                </div>
            @endif

            <div class="d-flex justify-content-between gap-2">
                <span class="text-muted">No. HP</span>
                <span class="text-dark text-end text-truncate">{{ $card['phone'] ?? '-' }}</span>
            </div>
            <div class="d-flex justify-content-between gap-2">
                <span class="text-muted">Email</span>
                <span class="text-dark text-end text-truncate" title="{{ $card['email'] ?? '-' }}">{{ $card['email'] ?? '-' }}</span>
            </div>
            <div class="d-flex justify-content-between gap-2">
                <span class="text-muted">Alamat</span>
                <span class="text-dark text-end text-truncate" title="{{ $card['address'] ?? '-' }}">{{ $card['address'] ?? '-' }}</span>
            </div>
        </div>
    </div>
</div>
