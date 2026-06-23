<?php

namespace App\Actions\Chat;

use App\Models\ChatRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Notifications\ChatRequestAcceptedNotification;
use App\Services\AuditService;
use App\Services\ChatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AcceptChatRequest
{
    public function __construct(
        private ChatService $chats,
        private AuditService $audit,
    ) {}

    public function handle(User $acceptor, ChatRequest $request): Conversation
    {
        if ($request->recipient_id !== $acceptor->id) {
            throw new \RuntimeException('Only the recipient can accept this request.');
        }

        if ($request->status !== ChatRequest::STATUS_PENDING) {
            // Already responded — return whatever conversation is attached, or create one.
            return $request->conversation
                ?? $this->chats->privateConversationBetween($acceptor, $request->requester);
        }

        return DB::transaction(function () use ($acceptor, $request) {
            $conversation = $this->chats->privateConversationBetween($acceptor, $request->requester);

            $request->forceFill([
                'status' => ChatRequest::STATUS_ACCEPTED,
                'responded_at' => Carbon::now(),
                'conversation_id' => $conversation->id,
            ])->save();

            $this->audit->log('chat_request.accepted', $request, [
                'conversation_id' => $conversation->id,
            ], $acceptor->id);

            $request->requester->notify(new ChatRequestAcceptedNotification($request->fresh()));

            return $conversation;
        });
    }
}
