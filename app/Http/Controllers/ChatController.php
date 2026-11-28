<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatRoomMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        return view('chat.index', [
            'initialCourseId' => $request->integer('course_id') ?: null,
            'initialRefType' => $request->query('ref_type') ? (string) $request->query('ref_type') : null,
            'initialRefId' => $request->integer('ref_id') ?: null,
        ]);
    }

    public function downloadAttachment(Request $request, ChatMessage $chatMessage): StreamedResponse
    {
        $isMember = ChatRoomMember::where('chat_room_id', $chatMessage->chat_room_id)
            ->where('user_id', $request->user()->id)
            ->exists();

        abort_unless($isMember, 403);
        abort_unless($chatMessage->attachment_path && Storage::disk('local')->exists($chatMessage->attachment_path), 404);

        if ($request->boolean('inline')) {
            $ext = strtolower(pathinfo((string) $chatMessage->attachment_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
                return Storage::disk('local')->response($chatMessage->attachment_path);
            }
        }

        return Storage::disk('local')->download(
            $chatMessage->attachment_path,
            $chatMessage->attachment_name ?: basename($chatMessage->attachment_path)
        );
    }
}
