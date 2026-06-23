<?php

namespace App\Actions\Chat;

use App\Models\Message;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class RestoreMessage
{
    public function __construct(private AuditService $audit) {}

    public function handle(User $admin, Message $message): void
    {
        if (! $message->isDeleted()) {
            return;
        }

        DB::transaction(function () use ($admin, $message) {
            $message->forceFill([
                'deleted_at' => null,
                'deleted_by' => null,
                'deletion_reason' => null,
            ])->save();

            $this->audit->log('message.restored', $message, [
                'conversation_id' => $message->conversation_id,
            ], $admin->id);
        });
    }
}
