<?php

namespace App\Actions\Chat;

use App\Models\ChatRequest;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Carbon;

class RejectChatRequest
{
    public function __construct(private AuditService $audit) {}

    public function handle(User $actor, ChatRequest $request): void
    {
        if ($request->recipient_id !== $actor->id) {
            throw new \RuntimeException('Only the recipient can reject this request.');
        }

        if ($request->status !== ChatRequest::STATUS_PENDING) {
            return;
        }

        $request->forceFill([
            'status' => ChatRequest::STATUS_REJECTED,
            'responded_at' => Carbon::now(),
        ])->save();

        $this->audit->log('chat_request.rejected', $request, [
            'requester_id' => $request->requester_id,
        ], $actor->id);
    }
}
