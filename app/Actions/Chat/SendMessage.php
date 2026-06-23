<?php

namespace App\Actions\Chat;

use App\Models\Attachment;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\MentionNotification;
use App\Notifications\NewMessageNotification;
use App\Services\AuditService;
use App\Services\ChatPermissionService;
use App\Support\MentionParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SendMessage
{
    public function __construct(
        private AuditService $audit,
        private ChatPermissionService $perm,
    ) {}

    /**
     * @param  UploadedFile[]  $attachments
     */
    public function handle(
        User $sender,
        Conversation $conversation,
        ?string $body,
        array $attachments = [],
        ?int $replyToId = null,
    ): Message {
        $body = $body !== null ? trim($body) : null;

        if (! $body && empty($attachments)) {
            throw new \InvalidArgumentException('Message body or attachments required.');
        }

        if (! $this->perm->canSendMessage($sender, $conversation)) {
            $other = $conversation->otherParticipant($sender->id);
            $status = $other ? $this->perm->status($sender, $other) : 'denied';
            throw new \RuntimeException($this->perm->reason($status) ?: 'Cannot send to this user.');
        }

        $hasImageAttachment = false;
        foreach ($attachments as $file) {
            if ($file && str_starts_with($file->getMimeType() ?? '', 'image/')) {
                $hasImageAttachment = true;
                break;
            }
        }

        $type = match (true) {
            ! empty($attachments) && $hasImageAttachment && ! $body => Message::TYPE_IMAGE,
            ! empty($attachments) && ! $hasImageAttachment && ! $body => Message::TYPE_FILE,
            default => Message::TYPE_TEXT,
        };

        return DB::transaction(function () use ($sender, $conversation, $body, $attachments, $replyToId, $type) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'reply_to_id' => $replyToId,
                'type' => $type,
                'body' => $body,
            ]);

            foreach ($attachments as $file) {
                $this->storeAttachment($file, $message, $sender);
            }

            $conversation->forceFill([
                'last_message_id' => $message->id,
                'last_message_at' => Carbon::now(),
            ])->save();

            $this->audit->log('message.sent', $message, [
                'conversation_id' => $conversation->id,
                'has_attachments' => ! empty($attachments),
            ], $sender->id);

            $recipients = $conversation->users()
                ->where('users.id', '!=', $sender->id)
                ->get();

            // If a recipient was active in the last 90 seconds (i.e. their browser
            // is polling), mark the message delivered to them *immediately* so the
            // sender sees ✓✓ gray right away instead of waiting up to 3s for the
            // recipient's next poll to set delivered_at.
            $now = Carbon::now();
            $onlineRecipients = $recipients->filter(
                fn (User $u) => $u->last_active_at && $u->last_active_at->diffInSeconds($now) < 90
            );

            if ($onlineRecipients->isNotEmpty()) {
                $message->forceFill(['delivered_at' => $now])->save();

                \App\Models\MessageDelivery::insertOrIgnore(
                    $onlineRecipients->map(fn ($u) => [
                        'message_id' => $message->id,
                        'user_id' => $u->id,
                        'delivered_at' => $now,
                    ])->all()
                );
            }

            $loaded = $message->load(['sender', 'attachments']);
            Notification::send($recipients, new NewMessageNotification($loaded));

            // Mentions — only ping participants who were @-tagged, on top of the message notification.
            $mentionedUsernames = MentionParser::extractUsernames($body);
            if (! empty($mentionedUsernames)) {
                $mentionRecipients = $recipients->filter(
                    fn (User $u) => in_array(strtolower($u->username), $mentionedUsernames, true)
                );

                if ($mentionRecipients->isNotEmpty()) {
                    Notification::send($mentionRecipients, new MentionNotification($loaded));

                    $this->audit->log('message.mentioned', $loaded, [
                        'mentioned_user_ids' => $mentionRecipients->pluck('id')->all(),
                    ], $sender->id);
                }
            }

            return $message->load(['attachments', 'sender', 'replyTo']);
        });
    }

    private function storeAttachment(UploadedFile $file, Message $message, User $sender): Attachment
    {
        $extension = $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = 'attachments/'.date('Y/m').'/'.$filename;

        Storage::disk('public')->putFileAs(dirname($path), $file, basename($path));

        [$width, $height] = $this->imageDimensions($file);

        return Attachment::create([
            'message_id' => $message->id,
            'uploader_id' => $sender->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $path,
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize() ?? 0,
            'width' => $width,
            'height' => $height,
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
        ]);
    }

    private function imageDimensions(UploadedFile $file): array
    {
        if (! str_starts_with($file->getMimeType() ?? '', 'image/')) {
            return [null, null];
        }

        $info = @getimagesize($file->getRealPath());

        return $info ? [$info[0], $info[1]] : [null, null];
    }
}
