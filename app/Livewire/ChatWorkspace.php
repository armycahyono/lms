<?php

namespace App\Livewire;

use App\Models\AcademicYear;
use App\Models\Assignment;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\ChatRoomMember;
use App\Models\ClassroomStudent;
use App\Models\Course;
use App\Models\Material;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatWorkspace extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $activeRoomId = null;

    #[Locked]
    public ?int $replyToId = null;

    #[Locked]
    public ?string $referenceType = null;

    #[Locked]
    public ?int $referenceId = null;

    public string $roomFilter = 'all';

    public string $search = '';

    public string $messageBody = '';

    public $attachment = null;

    public bool $showNewDirectModal = false;

    public bool $showNewTopicModal = false;

    public bool $showRefPicker = false;

    public string $contactSearch = '';

    public string $topicTitle = '';

    public string $topicDescription = '';

    public ?int $topicCourseId = null;

    /** @var array<int, int> */
    public array $topicMemberIds = [];

    public function mount(?int $initialCourseId = null, ?string $initialRefType = null, ?int $initialRefId = null): void
    {
        $this->syncCourseRoomsForUser();

        if ($initialCourseId) {
            $courseRoom = ChatRoom::where('type', 'course')
                ->where('course_id', $initialCourseId)
                ->whereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
                ->first();

            if ($courseRoom) {
                $this->selectRoom($courseRoom->id);
            }
        }

        if (! $this->activeRoomId) {
            $firstRoom = ChatRoom::whereHas('members', fn ($q) => $q->where('user_id', auth()->id()))
                ->orderByDesc('last_message_at')
                ->orderByDesc('id')
                ->first();

            if ($firstRoom) {
                $this->selectRoom($firstRoom->id);
            }
        }

        if ($initialRefType && $initialRefId) {
            $this->attachReference($initialRefType, $initialRefId);
        }
    }

    public function setFilter(string $filter): void
    {
        if (in_array($filter, ['all', 'direct', 'course', 'topic'], true)) {
            $this->roomFilter = $filter;
        }
    }

    public function selectRoom(int $roomId): void
    {
        $membership = ChatRoomMember::where('chat_room_id', $roomId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $membership) {
            return;
        }

        $this->activeRoomId = $roomId;
        $this->replyToId = null;
        $this->showRefPicker = false;

        $membership->update(['last_read_at' => now()]);
    }

    public function setReplyTo(int $messageId): void
    {
        if (! $this->activeRoomId) {
            return;
        }

        $exists = ChatMessage::where('id', $messageId)
            ->where('chat_room_id', $this->activeRoomId)
            ->exists();

        if ($exists) {
            $this->replyToId = $messageId;
        }
    }

    public function clearReply(): void
    {
        $this->replyToId = null;
    }

    public function attachReference(string $kind, int $id): void
    {
        $user = auth()->user();
        $model = match ($kind) {
            'material' => Material::with('course')->find($id),
            'assignment' => Assignment::with('course')->find($id),
            'quiz' => Quiz::with('course')->find($id),
            default => null,
        };

        if (! $model || ! $model->course || ! Gate::forUser($user)->allows('view', $model->course)) {
            return;
        }

        $this->referenceType = $model::class;
        $this->referenceId = $model->id;
        $this->showRefPicker = false;
    }

    public function clearReference(): void
    {
        $this->referenceType = null;
        $this->referenceId = null;
    }

    public function sendMessage(): void
    {
        $this->resetValidation();
        $user = auth()->user();
        if (! $this->activeRoomId) {
            return;
        }

        $rateKey = 'chat-send:'.$user->id;
        if (RateLimiter::tooManyAttempts($rateKey, 25)) {
            $this->addError('messageBody', 'Terlalu banyak pesan dalam waktu singkat. Tunggu beberapa detik.');

            return;
        }
        RateLimiter::hit($rateKey, 60);

        $room = ChatRoom::whereKey($this->activeRoomId)
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->first();

        if (! $room) {
            return;
        }

        $membership = ChatRoomMember::where('chat_room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        $isModerator = $user->isAdmin() || $membership?->role === 'moderator';
        if ($room->is_locked && ! $isModerator) {
            $this->addError('messageBody', 'Ruang chat ini sedang dikunci oleh guru/moderator.');

            return;
        }

        $this->validate([
            'messageBody' => ['nullable', 'string', 'max:3000'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,zip'],
        ]);

        $trimmedBody = trim($this->messageBody);
        if ($trimmedBody === '' && ! $this->attachment) {
            $this->addError('messageBody', 'Tulis pesan atau lampirkan file terlebih dahulu.');

            return;
        }

        $attachmentPath = null;
        $attachmentName = null;
        if ($this->attachment) {
            $attachmentName = $this->attachment->getClientOriginalName();
            $attachmentPath = $this->attachment->store("chat/{$room->id}", 'local');
        }

        DB::transaction(function () use ($room, $user, $trimmedBody, $attachmentPath, $attachmentName): void {
            ChatMessage::create([
                'chat_room_id' => $room->id,
                'user_id' => $user->id,
                'reply_to_id' => $this->replyToId,
                'reference_type' => $this->referenceType,
                'reference_id' => $this->referenceId,
                'body' => $trimmedBody !== '' ? $trimmedBody : null,
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachmentName,
            ]);

            $room->update(['last_message_at' => now()]);
            ChatRoomMember::where('chat_room_id', $room->id)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);
        });

        $this->messageBody = '';
        $this->attachment = null;
        $this->replyToId = null;
        $this->clearReference();
        $this->dispatch('chat-message-sent');
    }

    public function startDirectChat(int $targetUserId): void
    {
        $this->resetValidation();
        $user = auth()->user();
        if ($targetUserId === $user->id) {
            return;
        }

        $allowedIds = $this->allowedContacts($user)->pluck('id');
        if (! $allowedIds->contains($targetUserId)) {
            $this->addError('contactSearch', 'Anda tidak diizinkan memulai chat pribadi dengan pengguna tersebut.');

            return;
        }

        $directKey = ChatRoom::directKeyFor($user->id, $targetUserId);
        $room = DB::transaction(function () use ($directKey, $user, $targetUserId): ChatRoom {
            $room = ChatRoom::firstOrCreate(
                ['direct_key' => $directKey],
                [
                    'academic_year_id' => AcademicYear::selectedFor($user)?->id,
                    'type' => 'direct',
                    'created_by' => $user->id,
                    'last_message_at' => now(),
                ]
            );

            ChatRoomMember::firstOrCreate(
                ['chat_room_id' => $room->id, 'user_id' => $user->id],
                ['role' => 'member', 'last_read_at' => now()]
            );
            ChatRoomMember::firstOrCreate(
                ['chat_room_id' => $room->id, 'user_id' => $targetUserId],
                ['role' => 'member']
            );

            return $room;
        });

        $this->showNewDirectModal = false;
        $this->contactSearch = '';
        $this->selectRoom($room->id);
    }

    public function createTopicRoom(): void
    {
        $this->resetValidation();
        $this->topicCourseId = $this->topicCourseId ?: null;
        $user = auth()->user();

        $validated = $this->validate([
            'topicTitle' => ['required', 'string', 'min:3', 'max:120'],
            'topicDescription' => ['nullable', 'string', 'max:500'],
            'topicCourseId' => ['nullable', 'integer', 'exists:courses,id'],
            'topicMemberIds' => ['nullable', 'array'],
            'topicMemberIds.*' => ['integer', 'exists:users,id'],
        ]);

        $memberIds = collect($validated['topicMemberIds'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->intersect($this->allowedContacts($user)->pluck('id'));

        if (! empty($validated['topicCourseId'])) {
            $course = Course::with(['teachers:id', 'students:id'])->findOrFail($validated['topicCourseId']);
            abort_unless(Gate::forUser($user)->allows('view', $course), 403);
            $memberIds = $memberIds
                ->merge($course->teachers->pluck('id'))
                ->merge($course->students->pluck('id'));
        }

        $memberIds = $memberIds->push($user->id)->unique()->values();

        $room = DB::transaction(function () use ($user, $validated, $memberIds): ChatRoom {
            $room = ChatRoom::create([
                'academic_year_id' => AcademicYear::selectedFor($user)?->id,
                'type' => 'topic',
                'course_id' => $validated['topicCourseId'] ?? null,
                'title' => trim($validated['topicTitle']),
                'description' => $validated['topicDescription'] ? trim($validated['topicDescription']) : null,
                'created_by' => $user->id,
                'last_message_at' => now(),
            ]);

            foreach ($memberIds as $memberId) {
                ChatRoomMember::create([
                    'chat_room_id' => $room->id,
                    'user_id' => $memberId,
                    'role' => $memberId === $user->id ? 'moderator' : 'member',
                    'last_read_at' => $memberId === $user->id ? now() : null,
                ]);
            }

            return $room;
        });

        $this->showNewTopicModal = false;
        $this->topicTitle = '';
        $this->topicDescription = '';
        $this->topicCourseId = null;
        $this->topicMemberIds = [];
        $this->selectRoom($room->id);
    }

    public function toggleLock(): void
    {
        $user = auth()->user();
        if (! $this->activeRoomId) {
            return;
        }

        $room = ChatRoom::find($this->activeRoomId);
        if (! $room || $room->type === 'direct') {
            return;
        }

        $membership = ChatRoomMember::where('chat_room_id', $room->id)
            ->where('user_id', $user->id)
            ->first();

        abort_unless($user->isAdmin() || $membership?->role === 'moderator', 403);

        $room->update(['is_locked' => ! $room->is_locked]);
    }

    public function togglePin(int $messageId): void
    {
        $user = auth()->user();
        if (! $this->activeRoomId) {
            return;
        }

        $membership = ChatRoomMember::where('chat_room_id', $this->activeRoomId)
            ->where('user_id', $user->id)
            ->first();

        abort_unless($user->isAdmin() || $membership?->role === 'moderator', 403);

        $message = ChatMessage::where('id', $messageId)
            ->where('chat_room_id', $this->activeRoomId)
            ->first();

        if (! $message) {
            return;
        }

        $message->update(['is_pinned' => ! $message->is_pinned]);
    }

    public function deleteMessage(int $messageId): void
    {
        $user = auth()->user();
        if (! $this->activeRoomId) {
            return;
        }

        $membership = ChatRoomMember::where('chat_room_id', $this->activeRoomId)
            ->where('user_id', $user->id)
            ->first();

        $message = ChatMessage::where('id', $messageId)
            ->where('chat_room_id', $this->activeRoomId)
            ->first();

        if (! $message) {
            return;
        }

        $canDelete = $message->user_id === $user->id
            || $user->isAdmin()
            || $membership?->role === 'moderator';

        abort_unless($canDelete, 403);

        $message->delete();
    }

    public function render(): View
    {
        $user = auth()->user();

        $rawRooms = ChatRoom::query()
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->when($this->roomFilter !== 'all', fn ($q) => $q->where('type', $this->roomFilter))
            ->with([
                'course.classroom',
                'course.subject',
                'members.user',
                'latestMessage.user',
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $unreadByRoom = $this->countUnreadMessagesByRoom($rawRooms, $user->id);

        if ($this->activeRoomId && ($unreadByRoom[$this->activeRoomId] ?? 0) > 0) {
            ChatRoomMember::where('chat_room_id', $this->activeRoomId)
                ->where('user_id', $user->id)
                ->update(['last_read_at' => now()]);
            $unreadByRoom[$this->activeRoomId] = 0;
        }

        $rooms = $rawRooms
            ->map(fn (ChatRoom $room): array => $this->formatRoomItem($room, $user, (int) ($unreadByRoom[$room->id] ?? 0)))
            ->filter(function (array $item): bool {
                $q = mb_strtolower(trim($this->search));
                if ($q === '') {
                    return true;
                }

                return str_contains(mb_strtolower($item['display_title']), $q)
                    || str_contains(mb_strtolower($item['subtitle']), $q);
            })
            ->values();

        $activeRoomData = $rooms->firstWhere('room.id', $this->activeRoomId)
            ?? ($this->activeRoomId
                ? $this->resolveActiveRoomFallback($user)
                : null);

        $activeRoom = $activeRoomData['room'] ?? null;
        $messages = $activeRoom
            ? ChatMessage::query()
                ->where('chat_room_id', $activeRoom->id)
                ->with(['user', 'replyTo.user', 'reference'])
                ->latest('id')
                ->limit(120)
                ->get()
                ->reverse()
                ->values()
            : collect();

        $pinnedMessage = $activeRoom
            ? ChatMessage::where('chat_room_id', $activeRoom->id)->where('is_pinned', true)->latest('id')->first()
            : null;
        $replyMessage = $this->replyToId ? $messages->firstWhere('id', $this->replyToId) : null;
        $draftReference = $this->resolveReferencePreview();

        $userCourses = $this->userCourses($user);
        $referableMaterials = collect();
        $referableAssignments = collect();
        if ($this->showRefPicker) {
            $courseIds = $activeRoom?->course_id
                ? [$activeRoom->course_id]
                : $userCourses->pluck('id')->all();

            $referableMaterials = Material::whereIn('course_id', $courseIds)
                ->where('is_published', true)
                ->with('course.classroom')
                ->latest('id')
                ->limit(25)
                ->get();

            $referableAssignments = Assignment::whereIn('course_id', $courseIds)
                ->where('is_published', true)
                ->with('course.classroom')
                ->latest('id')
                ->limit(25)
                ->get();
        }

        $contacts = ($this->showNewDirectModal || $this->showNewTopicModal)
            ? $this->allowedContacts($user)
                ->filter(function (User $u): bool {
                    $q = mb_strtolower(trim($this->contactSearch));

                    return $q === ''
                        || str_contains(mb_strtolower($u->name), $q)
                        || str_contains(mb_strtolower($u->username), $q);
                })
                ->take(40)
                ->values()
            : collect();

        return view('livewire.chat-workspace', [
            'rooms' => $rooms,
            'activeRoomData' => $activeRoomData,
            'activeRoom' => $activeRoom,
            'messages' => $messages,
            'pinnedMessage' => $pinnedMessage,
            'replyMessage' => $replyMessage,
            'draftReference' => $draftReference,
            'contacts' => $contacts,
            'userCourses' => $userCourses,
            'referableMaterials' => $referableMaterials,
            'referableAssignments' => $referableAssignments,
        ]);
    }

    private function resolveActiveRoomFallback(User $user): ?array
    {
        $room = ChatRoom::whereKey($this->activeRoomId)
            ->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->with(['course.classroom', 'course.subject', 'members.user'])
            ->first();

        if (! $room) {
            return null;
        }

        return $this->formatRoomItem($room, $user, 0);
    }

    /**
     * @return array{room: ChatRoom, display_title: string, subtitle: string, unread_count: int, other_user: ?User, my_role: string}
     */
    private function formatRoomItem(ChatRoom $room, User $user, int $unreadCount): array
    {
        $myMember = $room->members->firstWhere('user_id', $user->id);
        $otherMember = $room->type === 'direct'
            ? $room->members->firstWhere('user_id', '!=', $user->id)?->user
            : null;

        $displayTitle = match ($room->type) {
            'direct' => $otherMember?->name ?? 'Percakapan Pribadi',
            'course' => $room->course?->title ?? $room->title ?? 'Grup Kelas',
            default => $room->title ?? 'Ruang Diskusi',
        };

        $subtitle = match ($room->type) {
            'direct' => $otherMember ? strtoupper($otherMember->role) : '-',
            'course' => ($room->course?->subject?->name ?? 'Mapel').' · '.($room->course?->classroom?->name ?? ''),
            default => $room->members->count().' anggota',
        };

        return [
            'room' => $room,
            'display_title' => $displayTitle,
            'subtitle' => $subtitle,
            'unread_count' => $unreadCount,
            'other_user' => $otherMember,
            'my_role' => $myMember?->role ?? 'member',
        ];
    }

    /**
     * @param  Collection<int, ChatRoom>  $rooms
     * @return array<int, int>
     */
    private function countUnreadMessagesByRoom(Collection $rooms, int $userId): array
    {
        if ($rooms->isEmpty()) {
            return [];
        }

        return DB::table('chat_messages as m')
            ->join('chat_room_members as crm', function ($join) use ($userId): void {
                $join->on('crm.chat_room_id', '=', 'm.chat_room_id')
                    ->where('crm.user_id', '=', $userId);
            })
            ->whereIn('m.chat_room_id', $rooms->pluck('id'))
            ->whereNull('m.deleted_at')
            ->where('m.user_id', '!=', $userId)
            ->where(function ($q): void {
                $q->whereNull('crm.last_read_at')
                    ->orWhereColumn('m.created_at', '>', 'crm.last_read_at');
            })
            ->groupBy('m.chat_room_id')
            ->selectRaw('m.chat_room_id, COUNT(*) as unread_total')
            ->pluck('unread_total', 'chat_room_id')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }

    private function resolveReferencePreview(): ?array
    {
        if (! $this->referenceType || ! $this->referenceId) {
            return null;
        }

        $model = match ($this->referenceType) {
            Material::class => Material::find($this->referenceId),
            Assignment::class => Assignment::find($this->referenceId),
            Quiz::class => Quiz::find($this->referenceId),
            default => null,
        };

        if (! $model) {
            return null;
        }

        $kind = match ($this->referenceType) {
            Material::class => 'MATERI',
            Assignment::class => 'TUGAS',
            Quiz::class => 'KUIS',
            default => 'REFERENSI',
        };

        return [
            'kind' => $kind,
            'title' => $model->title,
        ];
    }

    private function syncCourseRoomsForUser(): void
    {
        $user = auth()->user();
        $courses = $this->userCourses($user);
        $yearId = AcademicYear::selectedFor($user)?->id;

        foreach ($courses as $course) {
            $room = ChatRoom::firstOrCreate(
                ['type' => 'course', 'course_id' => $course->id],
                [
                    'academic_year_id' => $course->academic_year_id ?? $yearId,
                    'title' => $course->title,
                    'created_by' => $user->id,
                    'last_message_at' => $course->updated_at ?? now(),
                ]
            );

            $role = ($user->isAdmin() || $user->isGuru()) ? 'moderator' : 'member';
            ChatRoomMember::firstOrCreate(
                ['chat_room_id' => $room->id, 'user_id' => $user->id],
                ['role' => $role]
            );
        }
    }

    /**
     * @return Collection<int, Course>
     */
    private function userCourses(User $user): Collection
    {
        $yearId = AcademicYear::selectedFor($user)?->id;

        if ($user->isSiswa()) {
            return $user->enrolledCourses()
                ->where('courses.status', 'active')
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom'])
                ->get();
        }

        if ($user->isGuru()) {
            return $user->teachingCourses()
                ->forAcademicYear($yearId)
                ->with(['subject', 'classroom'])
                ->get();
        }

        return Course::forAcademicYear($yearId)
            ->where('status', 'active')
            ->with(['subject', 'classroom'])
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function allowedContacts(User $user): Collection
    {
        if ($user->isGuru() || $user->isAdmin()) {
            return User::where('id', '!=', $user->id)
                ->where('is_active', true)
                ->whereIn('role', ['guru', 'siswa', 'admin'])
                ->orderBy('role')
                ->orderBy('name')
                ->get(['id', 'name', 'username', 'role']);
        }

        // Siswa: hanya guru pengampu / wali kelas & teman satu kelas/rombel
        $courses = $this->userCourses($user);
        $courseIds = $courses->pluck('id');
        $classroomIds = $courses->pluck('classroom_id')->filter()->unique();

        $teacherIds = DB::table('course_teachers')->whereIn('course_id', $courseIds)->pluck('teacher_id');
        $classmateIds = DB::table('course_enrollments')->whereIn('course_id', $courseIds)->pluck('student_id');
        $rombelMateIds = ClassroomStudent::whereIn('classroom_id', $classroomIds)->pluck('student_user_id');

        $allowedIds = $teacherIds->merge($classmateIds)->merge($rombelMateIds)
            ->unique()
            ->reject(fn ($id) => (int) $id === $user->id)
            ->values();

        if ($allowedIds->isEmpty()) {
            return collect();
        }

        return User::whereIn('id', $allowedIds)
            ->where('is_active', true)
            ->orderByRaw("CASE WHEN role = 'guru' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'role']);
    }
}
