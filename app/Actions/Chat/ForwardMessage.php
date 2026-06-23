<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Message;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\AuditService;
use App\Services\ChatPermissionService;
use App\Services\ChatService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class ForwardMessage
{
    public function __construct(
        private ChatService $chats,
        private AuditService $audit,
        private ChatPermissionService $perm,
    ) {}

    public function handle(User $forwarder, Message $original, User $target): Message
    {
        if ($original->isDeleted()) {
            throw new \RuntimeException('Cannot forward a deleted message.');
        }

        if ($target->id === $forwarder->id) {
            throw new \InvalidArgumentException('Cannot forward to yourself.');
        }

        $status = $this->perm->status($forwarder, $target);
        if ($status !== ChatPermissionService::ALLOWED) {
            throw new \RuntimeException($this->perm->reason($status) ?: 'Cannot forward to this user.');
        }

        $conversation = $this->chats->privateConversationBetween($forwarder, $target);

        return DB::transaction(function () use ($forwarder, $original, $target, $conversation) {
            $original->loadMissing('attachments', 'sender');

            $forwarded = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $forwarder->id,
                'type' => $original->type,
                'body' => $original->body,
                'metadata' => [
                    'forwarded_from_message_id' => $original->id,
                    'forwarded_from_user_id' => $original->sender_id,
                    'forwarded_from_user_name' => $original->sender?->name,
                ],
            ]);

            // Clone attachments as new rows pointing to the same stored file.
            // Files stay shared on disk — safe because we never physically delete.
            foreach ($original->attachments as $att) {
                Attachment::create([
                    'message_id' => $forwarded->id,
                    'uploader_id' => $forwarder->id,
                    'original_name' => $att->original_name,
                    'stored_path' => $att->stored_path,
                    'mime_type' => $att->mime_type,
                    'size' => $att->size,
                    'width' => $att->width,
                    'height' => $att->height,
                    'checksum' => $att->checksum,
                ]);
            }

            $conversation->forceFill([
                'last_message_id' => $forwarded->id,
                'last_message_at' => Carbon::now(),
            ])->save();

            $this->audit->log('message.forwarded', $forwarded, [
                'original_message_id' => $original->id,
                'target_user_id' => $target->id,
            ], $forwarder->id);

            $loaded = $forwarded->load(['sender', 'attachments']);
            Notification::send($target, new NewMessageNotification($loaded));

            return $loaded;
        });
    }
}
