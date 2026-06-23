<?php

namespace App\Services;

use App\Models\ChatRequest;
use App\Models\Conversation;
use App\Models\User;
use App\Models\UserBlock;

class ChatPermissionService
{
    public const ALLOWED = 'allowed';
    public const BLOCKED_BY_ME = 'blocked_by_me';
    public const BLOCKED_BY_THEM = 'blocked_by_them';
    public const REQUEST_PENDING = 'request_pending';      // I have sent, waiting on them
    public const INVITED = 'invited';                       // They sent me a request, I can accept
    public const NEEDS_REQUEST = 'needs_request';           // Nothing yet — UI should offer "send request"
    public const TARGET_INACTIVE = 'target_inactive';

    /**
     * Decide whether $actor can chat with $target. Returns one of the constants.
     * Admins bypass the request flow but are still subject to blocks (so even an
     * admin can't message someone who has explicitly blocked them — except that
     * super admins can; we honor block from the perspective of the spec).
     */
    public function status(User $actor, User $target): string
    {
        if ($actor->id === $target->id) {
            return self::NEEDS_REQUEST; // Can't chat with self
        }

        if (! $target->isActive()) {
            return self::TARGET_INACTIVE;
        }

        if ($actor->hasBlocked($target)) {
            return self::BLOCKED_BY_ME;
        }

        if ($actor->isBlockedBy($target) && ! $actor->isSuperAdmin()) {
            return self::BLOCKED_BY_THEM;
        }

        // Admins skip the request flow entirely.
        if ($actor->isAdmin()) {
            return self::ALLOWED;
        }

        // If a private conversation between these two already exists, they're
        // past the request gate — even if no chat_requests row records consent
        // (e.g. an admin sent the first message, which bypasses requests).
        // The recipient must be able to reply.
        $hasConversation = Conversation::query()
            ->between($actor->id, $target->id)
            ->exists();

        if ($hasConversation) {
            return self::ALLOWED;
        }

        // Either side already has an accepted request → would have created a
        // conversation, but check anyway for completeness.
        $accepted = ChatRequest::query()
            ->between($actor->id, $target->id)
            ->where('status', ChatRequest::STATUS_ACCEPTED)
            ->exists();

        if ($accepted) {
            return self::ALLOWED;
        }

        $incoming = ChatRequest::query()
            ->where('requester_id', $target->id)
            ->where('recipient_id', $actor->id)
            ->where('status', ChatRequest::STATUS_PENDING)
            ->exists();

        if ($incoming) {
            return self::INVITED;
        }

        $outgoing = ChatRequest::query()
            ->where('requester_id', $actor->id)
            ->where('recipient_id', $target->id)
            ->where('status', ChatRequest::STATUS_PENDING)
            ->exists();

        if ($outgoing) {
            return self::REQUEST_PENDING;
        }

        return self::NEEDS_REQUEST;
    }

    public function canSendMessage(User $actor, Conversation $conversation): bool
    {
        // 1:1 only for now — the other participant is the gate.
        $other = $conversation->otherParticipant($actor->id);

        if (! $other) {
            return false;
        }

        return $this->status($actor, $other) === self::ALLOWED;
    }

    public function reason(string $status): string
    {
        return match ($status) {
            self::ALLOWED => '',
            self::BLOCKED_BY_ME => 'You blocked this user. Unblock to send messages.',
            self::BLOCKED_BY_THEM => 'Messages cannot be delivered.',
            self::REQUEST_PENDING => 'Waiting for them to accept your chat request.',
            self::INVITED => 'Accept their chat request to start messaging.',
            self::NEEDS_REQUEST => 'Send a chat request first.',
            self::TARGET_INACTIVE => 'This user is not available.',
            default => 'Cannot send to this user.',
        };
    }
}
