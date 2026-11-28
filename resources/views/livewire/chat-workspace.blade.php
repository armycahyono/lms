<div>
    <div class="card overflow-hidden" style="min-height: 580px;">
        <div class="row g-0" style="min-height: 580px;">
            {{-- Sidebar Daftar Ruang Chat --}}
            <div class="col-12 col-md-4 col-lg-3 border-end bg-light-subtle d-flex flex-column" style="max-height: 640px;">
                <div class="p-3 border-bottom bg-white">
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                        <span class="fw-bold">Percakapan</span>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-primary" wire:click="$set('showNewDirectModal', true)" title="Chat Pribadi Baru">
                                <i class="ti ti-message-plus me-1"></i> Chat
                            </button>
                            <button type="button" class="btn btn-outline-primary" wire:click="$set('showNewTopicModal', true)" title="Buat Ruang Diskusi Tema">
                                <i class="ti ti-hash me-1"></i> Tema
                            </button>
                        </div>
                    </div>

                    <input type="search" wire:model.live.debounce.250ms="search" class="form-control form-control-sm mb-2" placeholder="Cari guru, teman, kelas, atau tema...">

                    <div class="btn-group w-100" role="group" aria-label="Filter ruang chat">
                        @foreach(['all' => 'Semua', 'direct' => 'Pribadi', 'course' => 'Kelas', 'topic' => 'Tema'] as $key => $label)
                            <button type="button" wire:click="setFilter('{{ $key }}')" class="btn btn-sm {{ $roomFilter === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="list-group list-group-flush overflow-auto flex-grow-1">
                    @forelse($rooms as $item)
                        @php
                            $r = $item['room'];
                            $isActive = $activeRoom && $activeRoom->id === $r->id;
                        @endphp
                        <button type="button" wire:key="room-{{ $r->id }}" wire:click="selectRoom({{ $r->id }})"
                            class="list-group-item list-group-item-action text-start py-2 px-3 {{ $isActive ? 'active bg-blue-lt border-start border-4 border-primary' : '' }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="fw-semibold text-truncate small text-dark">{{ $item['display_title'] }}</div>
                                @if($item['unread_count'] > 0)
                                    <span class="badge bg-warning text-white rounded-pill">{{ $item['unread_count'] }}</span>
                                @endif
                            </div>
                            <div class="text-muted text-truncate" style="font-size: 0.72rem;">{{ $item['subtitle'] }}</div>
                            @if($r->latestMessage)
                                <div class="text-muted text-truncate mt-1" style="font-size: 0.75rem;">
                                    <span class="fw-medium">{{ $r->latestMessage->user?->name }}:</span>
                                    {{ $r->latestMessage->body ?: ($r->latestMessage->attachment_name ?? 'Lampiran') }}
                                </div>
                            @endif
                        </button>
                    @empty
                        <div class="p-4 text-center text-muted small">
                            Belum ada percakapan pada filter ini.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Area Percakapan --}}
            <div class="col-12 col-md-8 col-lg-9 d-flex flex-column bg-white" style="max-height: 640px;" wire:poll.visible.6s>
                @if($activeRoom && $activeRoomData)
                    @php
                        $canModerate = auth()->user()->isAdmin() || ($activeRoomData['my_role'] ?? 'member') === 'moderator';
                    @endphp
                    <div class="px-3 py-2 border-bottom d-flex flex-wrap justify-content-between align-items-center gap-2 bg-white">
                        <div class="min-w-0">
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="mb-0 h4 text-truncate">{{ $activeRoomData['display_title'] }}</h3>
                                <span class="badge bg-blue-lt text-uppercase" style="font-size: 0.65rem;">
                                    {{ $activeRoom->type === 'direct' ? 'Pribadi' : ($activeRoom->type === 'course' ? 'Kelas' : 'Tema') }}
                                </span>
                                @if($activeRoom->is_locked)
                                    <span class="badge bg-danger-lt" style="font-size: 0.65rem;"><i class="ti ti-lock me-1"></i>Dikunci</span>
                                @endif
                            </div>
                            <div class="text-muted small text-truncate">{{ $activeRoomData['subtitle'] }}</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" wire:click="$toggle('showRefPicker')" class="btn btn-sm btn-outline-primary">
                                <i class="ti ti-book-2 me-1"></i> Tanya Materi / Tugas
                            </button>
                            @if($canModerate && $activeRoom->type !== 'direct')
                                <button type="button" wire:click="toggleLock" class="btn btn-sm {{ $activeRoom->is_locked ? 'btn-warning' : 'btn-outline-secondary' }}" title="Kunci/Buka Ruang Chat">
                                    <i class="ti {{ $activeRoom->is_locked ? 'ti-lock-open' : 'ti-lock' }}"></i>
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($pinnedMessage)
                        <div class="px-3 py-2 bg-warning-lt border-bottom small d-flex justify-content-between align-items-center gap-2">
                            <div class="text-truncate">
                                <i class="ti ti-pin me-1"></i>
                                <strong>Disematkan:</strong> {{ $pinnedMessage->body ?: $pinnedMessage->attachment_name }}
                            </div>
                        </div>
                    @endif

                    @if($showRefPicker)
                        <div class="p-3 bg-light border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold small">Pilih Materi atau Tugas untuk Ditanyakan:</span>
                                <button type="button" wire:click="$set('showRefPicker', false)" class="btn-close btn-sm"></button>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="text-muted small fw-semibold mb-1">Materi</div>
                                    <div class="list-group list-group-flush border rounded bg-white" style="max-height: 150px; overflow-y: auto;">
                                        @forelse($referableMaterials as $mat)
                                            <button type="button" wire:click="attachReference('material', {{ $mat->id }})" class="list-group-item list-group-item-action py-1 px-2 small">
                                                <div class="fw-medium text-truncate">{{ $mat->title }}</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">{{ $mat->course?->classroom?->name }}</div>
                                            </button>
                                        @empty
                                            <div class="p-2 text-muted small">Belum ada materi.</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-muted small fw-semibold mb-1">Tugas</div>
                                    <div class="list-group list-group-flush border rounded bg-white" style="max-height: 150px; overflow-y: auto;">
                                        @forelse($referableAssignments as $asg)
                                            <button type="button" wire:click="attachReference('assignment', {{ $asg->id }})" class="list-group-item list-group-item-action py-1 px-2 small">
                                                <div class="fw-medium text-truncate">{{ $asg->title }}</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">{{ $asg->course?->classroom?->name }}</div>
                                            </button>
                                        @empty
                                            <div class="p-2 text-muted small">Belum ada tugas.</div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Daftar Pesan --}}
                    <div
                        class="flex-grow-1 overflow-auto p-3 bg-light-subtle d-flex flex-column gap-3"
                        wire:key="messages-box-{{ $activeRoom->id }}"
                        x-data="{ scrollToBottom() { $nextTick(() => { $el.scrollTop = $el.scrollHeight; }); } }"
                        x-init="scrollToBottom()"
                        x-effect="$wire.activeRoomId; scrollToBottom()"
                        x-on:chat-message-sent.window="scrollToBottom()"
                    >
                        @forelse($messages as $msg)
                            @php
                                $isMine = $msg->user_id === auth()->id();
                                $refModel = $msg->reference;
                                $refRoute = null;
                                $refKind = null;
                                if ($refModel instanceof \App\Models\Material) {
                                    $refKind = 'MATERI';
                                    $refRoute = route('courses.materials.show', [$refModel->course_id, $refModel->id]);
                                } elseif ($refModel instanceof \App\Models\Assignment) {
                                    $refKind = 'TUGAS';
                                    $refRoute = route('courses.assignments.show', [$refModel->course_id, $refModel->id]);
                                } elseif ($refModel instanceof \App\Models\Quiz) {
                                    $refKind = 'KUIS';
                                    $refRoute = route('courses.quizzes.show', [$refModel->course_id, $refModel->id]);
                                }
                            @endphp
                            <div wire:key="msg-{{ $msg->id }}" class="d-flex flex-column {{ $isMine ? 'align-items-end' : 'align-items-start' }}">
                                <div class="text-muted mb-1 px-1" style="font-size: 0.72rem;">
                                    <span class="fw-semibold">{{ $isMine ? 'Saya' : ($msg->user?->name ?? 'Pengguna') }}</span>
                                    @if(! $isMine && $msg->user?->role === 'guru')
                                        <span class="badge bg-blue-lt ms-1" style="font-size: 0.62rem;">Guru</span>
                                    @endif
                                    &middot; {{ $msg->created_at?->format('d M H:i') }}
                                </div>
                                <div class="rounded-3 px-3 py-2 shadow-sm {{ $isMine ? 'bg-primary text-white' : 'bg-white border text-dark' }}" style="max-width: 80%;">
                                    @if($msg->replyTo)
                                        <div class="rounded px-2 py-1 mb-2 small {{ $isMine ? 'bg-white bg-opacity-10 text-white' : 'bg-light text-muted border-start border-3 border-primary' }}">
                                            <div class="fw-semibold">{{ $msg->replyTo->user?->name }}</div>
                                            <div class="text-truncate">{{ \Illuminate\Support\Str::limit($msg->replyTo->body ?: 'Lampiran', 70) }}</div>
                                        </div>
                                    @endif

                                    @if($refModel && $refRoute)
                                        <a href="{{ $refRoute }}" class="d-block rounded p-2 mb-2 text-decoration-none {{ $isMine ? 'bg-white bg-opacity-15 text-white' : 'bg-blue-lt text-dark' }}">
                                            <div class="fw-bold" style="font-size: 0.68rem;">{{ $refKind }} TERKAIT</div>
                                            <div class="fw-semibold small">{{ $refModel->title }}</div>
                                        </a>
                                    @endif

                                    @if($msg->body)
                                        <div class="small" style="white-space: pre-wrap;">{{ $msg->body }}</div>
                                    @endif

                                    @if($msg->attachment_path)
                                        @php
                                            $ext = strtolower(pathinfo((string) $msg->attachment_path, PATHINFO_EXTENSION));
                                            $isImageAttachment = in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                        @endphp
                                        <div class="mt-2">
                                            @if($isImageAttachment)
                                                <a href="{{ route('chat.attachments.download', [$msg, 'inline' => 1]) }}" target="_blank" rel="noopener" class="d-inline-block">
                                                    <img src="{{ route('chat.attachments.download', [$msg, 'inline' => 1]) }}"
                                                        alt="{{ $msg->attachment_name ?: 'Lampiran gambar' }}"
                                                        class="img-fluid d-block rounded-2 border bg-white"
                                                        style="max-width: 260px; max-height: 220px; width: auto; height: auto;">
                                                </a>
                                                <div class="mt-1" style="max-width: 260px;">
                                                    <a href="{{ route('chat.attachments.download', $msg) }}" class="small d-inline-flex align-items-center gap-1 text-truncate mw-100 text-decoration-none {{ $isMine ? 'text-white-50' : 'text-muted' }}">
                                                        <i class="ti ti-download flex-shrink-0"></i>
                                                        <span class="text-truncate">{{ $msg->attachment_name ?: 'Unduh gambar' }}</span>
                                                    </a>
                                                </div>
                                            @else
                                                <a href="{{ route('chat.attachments.download', $msg) }}" class="btn btn-sm {{ $isMine ? 'btn-light' : 'btn-outline-primary' }}">
                                                    <i class="ti ti-paperclip me-1"></i> {{ $msg->attachment_name ?: 'Unduh Lampiran' }}
                                                </a>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <div class="d-flex gap-2 mt-1 px-1" style="font-size: 0.7rem;">
                                    <button type="button" wire:click="setReplyTo({{ $msg->id }})" class="btn btn-link btn-sm p-0 text-muted">Balas</button>
                                    @if($canModerate && $activeRoom->type !== 'direct')
                                        <button type="button" wire:click="togglePin({{ $msg->id }})" class="btn btn-link btn-sm p-0 text-muted">{{ $msg->is_pinned ? 'Lepas Pin' : 'Pin' }}</button>
                                    @endif
                                    @if($isMine || $canModerate)
                                        <button type="button" wire:click="deleteMessage({{ $msg->id }})" class="btn btn-link btn-sm p-0 text-danger">Hapus</button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="my-auto text-center text-muted py-5">
                                <i class="ti ti-messages fs-1 d-block mb-2 opacity-50"></i>
                                Belum ada pesan di ruang ini. Mulai percakapan sekarang!
                            </div>
                        @endforelse
                    </div>

                    {{-- Preview Reply / Referensi --}}
                    @if($replyMessage || $draftReference)
                        <div class="px-3 pt-2 bg-white border-top d-flex flex-column gap-1">
                            @if($replyMessage)
                                <div class="alert alert-secondary py-1 px-2 mb-0 d-flex justify-content-between align-items-center small">
                                    <span class="text-truncate">Membalas <strong>{{ $replyMessage->user?->name }}</strong>: {{ \Illuminate\Support\Str::limit($replyMessage->body ?: 'Lampiran', 60) }}</span>
                                    <button type="button" wire:click="clearReply" class="btn-close btn-sm"></button>
                                </div>
                            @endif
                            @if($draftReference)
                                <div class="alert alert-info py-1 px-2 mb-0 d-flex justify-content-between align-items-center small">
                                    <span><span class="badge bg-primary text-white me-1">{{ $draftReference['kind'] }}</span> {{ $draftReference['title'] }}</span>
                                    <button type="button" wire:click="clearReference" class="btn-close btn-sm"></button>
                                </div>
                            @endif
                        </div>
                    @endif

                    {{-- Form Kirim Pesan --}}
                    <div class="p-3 border-top bg-white">
                        @if($activeRoom->is_locked && ! $canModerate)
                            <div class="text-center text-muted small py-1">
                                <i class="ti ti-lock me-1"></i> Ruang chat ini sedang dikunci oleh guru/moderator.
                            </div>
                        @else
                            <form wire:submit="sendMessage" class="d-flex flex-column gap-2">
                                @error('messageBody') <div class="text-danger small">{{ $message }}</div> @enderror
                                @error('attachment') <div class="text-danger small">{{ $message }}</div> @enderror
                                <div class="d-flex gap-2 align-items-end">
                                    <label class="btn btn-outline-secondary btn-icon mb-0 flex-shrink-0" title="Lampirkan File/Gambar">
                                        <i class="ti ti-paperclip"></i>
                                        <input type="file" wire:model="attachment" class="d-none">
                                    </label>
                                    <div class="flex-grow-1" wire:ignore wire:key="composer-input-{{ $activeRoom->id }}">
                                        <textarea
                                            wire:model="messageBody"
                                            rows="2"
                                            class="form-control"
                                            style="resize: none; max-height: 160px; overflow-y: auto;"
                                            placeholder="Tulis pesan atau pertanyaan... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                                            x-data="{
                                                resize() {
                                                    $el.style.height = 'auto';
                                                    $el.style.height = Math.min($el.scrollHeight, 160) + 'px';
                                                }
                                            }"
                                            x-init="resize()"
                                            x-on:input="resize()"
                                            x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.sendMessage().then(() => { $el.value = ''; $el.style.height = 'auto'; }); }"
                                        ></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary flex-shrink-0" wire:loading.attr="disabled" wire:target="sendMessage,attachment">
                                        <i class="ti ti-send me-1"></i> Kirim
                                    </button>
                                </div>
                                @if($attachment)
                                    <div class="d-flex align-items-center justify-content-between gap-2 small text-muted bg-light rounded px-2 py-1">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            @if(str_starts_with((string) $attachment->getMimeType(), 'image/'))
                                                <img src="{{ $attachment->temporaryUrl() }}" alt="Pratinjau" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                                            @else
                                                <i class="ti ti-file"></i>
                                            @endif
                                            <span class="text-truncate">{{ $attachment->getClientOriginalName() }}</span>
                                        </div>
                                        <button type="button" wire:click="$set('attachment', null)" class="btn-close btn-sm" aria-label="Hapus lampiran"></button>
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                @else
                    <div class="my-auto text-center text-muted p-5">
                        <i class="ti ti-message-circle fs-1 d-block mb-2 opacity-50"></i>
                        Pilih percakapan di sebelah kiri atau klik <strong>+ Chat</strong> / <strong>+ Tema</strong> untuk memulai.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Chat Pribadi Baru --}}
    @if($showNewDirectModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.45);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Mulai Chat Pribadi</h5>
                        <button type="button" class="btn-close" wire:click="$set('showNewDirectModal', false)"></button>
                    </div>
                    <div class="modal-body">
                        <input type="search" wire:model.live.debounce.200ms="contactSearch" class="form-control mb-3" placeholder="Cari nama guru atau teman sekelas...">
                        @error('contactSearch') <div class="text-danger small mb-2">{{ $message }}</div> @enderror
                        <div class="list-group list-group-flush border rounded" style="max-height: 280px; overflow-y: auto;">
                            @forelse($contacts as $contact)
                                <button type="button" wire:click="startDirectChat({{ $contact->id }})" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-semibold small">{{ $contact->name }}</div>
                                        <div class="text-muted" style="font-size: 0.72rem;">{{ $contact->username }}</div>
                                    </div>
                                    <span class="badge {{ $contact->role === 'guru' ? 'bg-blue-lt' : 'bg-secondary-lt' }} text-uppercase">{{ $contact->role }}</span>
                                </button>
                            @empty
                                <div class="p-3 text-center text-muted small">Tidak ada kontak yang cocok.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Modal Buat Ruang Diskusi Tema --}}
    @if($showNewTopicModal)
        <div class="modal modal-blur fade show d-block" tabindex="-1" style="background: rgba(15, 23, 42, 0.45);">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form wire:submit="createTopicRoom">
                        <div class="modal-header">
                            <h5 class="modal-title">Buat Ruang Diskusi Tema</h5>
                            <button type="button" class="btn-close" wire:click="$set('showNewTopicModal', false)"></button>
                        </div>
                        <div class="modal-body d-grid gap-3">
                            <div>
                                <label class="form-label">Judul Tema Diskusi</label>
                                <input type="text" wire:model="topicTitle" class="form-control" placeholder="Contoh: Tanya Jawab Persiapan PAS" required>
                                @error('topicTitle') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                            </div>
                            <div>
                                <label class="form-label">Deskripsi (Opsional)</label>
                                <textarea wire:model="topicDescription" rows="2" class="form-control" placeholder="Jelaskan fokus topik diskusi..."></textarea>
                            </div>
                            <div>
                                <label class="form-label">Hubungkan dengan Kelas (Opsional)</label>
                                <select wire:model="topicCourseId" class="form-select">
                                    <option value="">-- Tidak terikat kelas tertentu --</option>
                                    @foreach($userCourses as $uc)
                                        <option value="{{ $uc->id }}">{{ $uc->title }}</option>
                                    @endforeach
                                </select>
                                <div class="form-hint">Jika dipilih, seluruh guru &amp; siswa kelas tersebut otomatis menjadi anggota.</div>
                            </div>
                            <div>
                                <label class="form-label">Undang Anggota Tambahan (Opsional)</label>
                                <div class="border rounded p-2" style="max-height: 140px; overflow-y: auto;">
                                    @foreach($contacts as $contact)
                                        <label class="form-check mb-1">
                                            <input type="checkbox" wire:model="topicMemberIds" value="{{ $contact->id }}" class="form-check-input">
                                            <span class="form-check-label small">{{ $contact->name }} ({{ strtoupper($contact->role) }})</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-link link-secondary" wire:click="$set('showNewTopicModal', false)">Batal</button>
                            <button type="submit" class="btn btn-primary">Buat Ruang Tema</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
