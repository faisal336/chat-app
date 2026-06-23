<?php

namespace App\Actions\Chat;

use App\Models\ChatRequest;
use App\Models\User;
use App\Notifications\ChatRequestReceivedNotification;
use App\Services\AuditService;
use App\Services\ChatPermissionService;

class SendChatRequest
{
    public function __construct(
        private AuditService $audit,
        private ChatPermissionService $perm,
    ) {}

    public function handle(User $requester, User $recipient, ?string $message = null): ChatRequest
    {
        if ($requester->id === $recipient->id) {
            throw new \InvalidArgumentException('Cannot send a chat request to yourself.');
        }

        if ($requester->hasBlocked($recipient) || $requester->isBlockedBy($recipient)) {
            throw new \RuntimeException('Cannot send a chat request to this user.');
        }

        if (! $recipient->isActive()) {
            throw new \RuntimeException('Recipient is not available.');
        }

        $existing = ChatRequest::where('requester_id', $requester->id)
            ->where('recipient_id', $recipient->id)
            ->first();

        if ($existing) {
            if ($existing->status === ChatRequest::STATUS_PENDING) {
                return $existing;
            }
            if ($existing->status === ChatRequest::STATUS_ACCEPTED) {
                return $existing;
            }

            // Previously rejected/cancelled — reopen as a fresh pending.
            $existing->forceFill([
                'status' => ChatRequest::STATUS_PENDING,
                'message' => $message,
                'responded_at' => null,
            ])->save();

            $this->audit->log('chat_request.reopened', $existing);
            $recipient->notify(new ChatRequestReceivedNotification($existing));

            return $existing;
        }

        // Auto-accept if there's already an *incoming* pending from the recipient.
        // (Both wanted to chat — no need to keep the new one waiting.)
        $incoming = ChatRequest::where('requester_id', $recipient->id)
            ->where('recipient_id', $requester->id)
            ->where('status', ChatRequest::STATUS_PENDING)
            ->first();

        if ($incoming) {
            app(AcceptChatRequest::class)->handle($requester, $incoming);

            return $incoming->refresh();
        }

        $request = ChatRequest::create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
            'status' => ChatRequest::STATUS_PENDING,
            'message' => $message,
        ]);

        $this->audit->log('chat_request.sent', $request, ['recipient_id' => $recipient->id]);
        $recipient->notify(new ChatRequestReceivedNotification($request));

        return $request;
    }
}
