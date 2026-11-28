@props(['guide' => null])

@if($guide)
    <div class="offcanvas offcanvas-end" tabindex="-1" id="lms-page-guide-drawer" aria-labelledby="lms-page-guide-title" style="width: min(410px, 92vw);">
        <div class="offcanvas-header border-bottom bg-light-subtle">
            <div class="d-flex align-items-center gap-2">
                <span class="avatar avatar-sm rounded-circle bg-primary-lt text-primary">
                    <i class="ti ti-book-2"></i>
                </span>
                <div>
                    <div class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 0.06em;">Panduan Halaman</div>
                    <h5 class="offcanvas-title mb-0 fw-bold" id="lms-page-guide-title">{{ $guide['title'] }}</h5>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>

        <div class="offcanvas-body d-flex flex-column gap-4">
            @if(!empty($guide['summary']))
                <div class="p-3 rounded-3 bg-blue-lt text-dark small lh-base">
                    {{ $guide['summary'] }}
                </div>
            @endif

            @if(!empty($guide['steps']))
                <div>
                    <div class="text-muted text-uppercase fw-bold mb-3" style="font-size: 0.7rem; letter-spacing: 0.06em;">
                        Langkah Penggunaan
                    </div>
                    <div class="d-flex flex-column gap-3">
                        @foreach($guide['steps'] as $idx => $step)
                            <div class="d-flex gap-3 align-items-start">
                                <span class="badge bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 26px; height: 26px; font-size: 0.75rem;">
                                    {{ $idx + 1 }}
                                </span>
                                <div>
                                    <div class="fw-semibold text-dark small mb-1">{{ $step['title'] }}</div>
                                    <div class="text-muted small lh-base">{{ $step['desc'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if(!empty($guide['tips']))
                <div class="p-3 rounded-3 border bg-light-subtle">
                    <div class="fw-semibold text-dark small mb-2 d-flex align-items-center gap-1">
                        <i class="ti ti-bulb text-warning"></i>
                        <span>Catatan Penting</span>
                    </div>
                    <ul class="mb-0 ps-3 small text-muted d-flex flex-column gap-1">
                        @foreach($guide['tips'] as $tip)
                            <li>{{ $tip }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
@endif
