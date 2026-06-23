<?php

namespace App\Actions\Chat;

use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteMessage
{
    public function __construct(private AuditService $audit) {}

    public function handle(User $actor, Message $message, ?string $reason = null): void
    {
        if ($message->isDeleted()) {
            return;
        }

        DB::transaction(function () use ($actor, $message, $reason) {
            $message->forceFill([
                'deleted_at' => Carbon::now(),
                'deleted_by' => $actor->id,
                'deletion_reason' => $reason,
            ])->save();

            $this->audit->log('message.deleted', $message, [
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'by_self' => $actor->id === $message->sender_id,
                'reason' => $reason,
            ], $actor->id);
        });
    }
}
