<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * "Clear chat history" — per-user soft-clear. Sets a watermark so the user no
 * longer sees messages with id <= watermark. The other participant and admins
 * still see everything (audit / restore intact).
 */
class ClearChatHistory
{
    public function __construct(private AuditService $audit) {}

    public function handle(User $user, Conversation $conversation): int
    {
        return DB::transaction(function () use ($user, $conversation) {
            $maxId = (int) Message::where('conversation_id', $conversation->id)->max('id');

            if ($maxId === 0) {
                return 0;
            }

            $participant = ConversationParticipant::where('conversation_id', $conversation->id)
                ->where('user_id', $user->id)
                ->first();

            if (! $participant) {
                return 0;
            }

            $previous = (int) ($participant->cleared_through_message_id ?? 0);
            $clearedCount = Message::where('conversation_id', $conversation->id)
                ->where('id', '>', $previous)
                ->where('id', '<=', $maxId)
                ->count();

            $participant->forceFill([
                'cleared_through_message_id' => $maxId,
                'cleared_at' => Carbon::now(),
                // Reading is implied — also bump read pointer so unread badge clears.
                'last_read_message_id' => $maxId,
                'last_read_at' => Carbon::now(),
            ])->save();

            $this->audit->log('chat.cleared_for_self', $conversation, [
                'cleared_through_message_id' => $maxId,
                'messages_hidden' => $clearedCount,
            ], $user->id);

            return $clearedCount;
        });
    }
}
