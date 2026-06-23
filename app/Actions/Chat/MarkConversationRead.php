<?php

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\MessageDelivery;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MarkConversationRead
{
    /**
     * Mark messages in a conversation as delivered, and optionally as read.
     *
     * @param  bool  $markRead  true  = ✓✓ blue (tab is visible — actual viewing)
     *                          false = ✓✓ gray (tab hidden / chat open but not focused)
     */
    public function handle(User $user, Conversation $conversation, bool $markRead = true): void
    {
        $lastMessage = Message::where('conversation_id', $conversation->id)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        if (! $lastMessage) {
            return;
        }

        DB::transaction(function () use ($user, $conversation, $lastMessage, $markRead) {
            $now = Carbon::now();

            // --- Delivery first: always set delivered_at when the recipient's
            //     browser has the message, even if their tab is hidden.
            $undeliveredIds = Message::query()
                ->where('conversation_id', $conversation->id)
                ->where('sender_id', '!=', $user->id)
                ->whereNull('delivered_at')
                ->pluck('id');

            if ($undeliveredIds->isNotEmpty()) {
                MessageDelivery::insertOrIgnore(
                    $undeliveredIds->map(fn ($id) => [
                        'message_id' => $id,
                        'user_id' => $user->id,
                        'delivered_at' => $now,
                    ])->all()
                );

                Message::whereIn('id', $undeliveredIds)
                    ->whereNull('delivered_at')
                    ->update(['delivered_at' => $now]);
            }

            // --- Read: only when the tab is actually visible (caller's choice).
            if ($markRead) {
                $unreadIds = Message::query()
                    ->where('conversation_id', $conversation->id)
                    ->where('sender_id', '!=', $user->id)
                    ->whereNotExists(function ($q) use ($user) {
                        $q->from('message_reads')
                          ->whereColumn('message_reads.message_id', 'messages.id')
                          ->where('message_reads.user_id', $user->id);
                    })
                    ->pluck('id');

                if ($unreadIds->isNotEmpty()) {
                    MessageRead::insert(
                        $unreadIds->map(fn ($id) => [
                            'message_id' => $id,
                            'user_id' => $user->id,
                            'read_at' => $now,
                        ])->all()
                    );

                    Message::whereIn('id', $unreadIds)
                        ->whereNull('read_at')
                        ->update(['read_at' => $now]);
                }

                ConversationParticipant::where('conversation_id', $conversation->id)
                    ->where('user_id', $user->id)
                    ->update([
                        'last_read_message_id' => $lastMessage->id,
                        'last_read_at' => $now,
                    ]);
            }
        });
    }
}
