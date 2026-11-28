@if($children->count() > 1)
    <div class="card mb-3 bg-blue-lt border-0">
        <div class="card-body py-2 d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="small fw-semibold text-primary">
                <i class="ti ti-users me-1"></i> Pilih Anak Aktif:
            </div>
            <div class="d-flex flex-wrap gap-2">
                @foreach($children as $child)
                    <a href="{{ request()->fullUrlWithQuery(['child_id' => $child->id]) }}"
                       class="btn btn-sm {{ $activeChild && $activeChild->id === $child->id ? 'btn-primary' : 'btn-outline-primary' }}">
                        {{ $child->name }}
                    </a>
                @endforeach
            </div>
        </div>
    </div>
@elseif($activeChild)
    <div class="mb-3 d-flex flex-wrap align-items-center gap-2 text-muted small">
        <span class="badge bg-blue-lt"><i class="ti ti-user-check me-1"></i>Anak: {{ $activeChild->name }}</span>
        @if($activeChild->classrooms->isNotEmpty())
            <span class="badge bg-secondary-lt">{{ $activeChild->classrooms->first()->name }}</span>
        @endif
        @if($activeYear)
            <span class="badge bg-azure-lt">TA {{ $activeYear->name }}</span>
        @endif
    </div>
@endif
