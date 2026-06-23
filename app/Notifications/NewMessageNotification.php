<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class NewMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function via(User $notifiable): array
    {
        $channels = ['database'];

        $pref = NotificationPreference::where('user_id', $notifiable->id)
            ->where('event_type', NotificationPreference::EVENT_NEW_MESSAGE)
            ->first();

        $optedIn = ! $pref || $pref->enabled;
        $globalEnabled = $notifiable->settings?->notifications_enabled ?? true;

        if ($optedIn && $globalEnabled) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toArray(User $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name,
            'preview' => $this->preview(),
        ];
    }

    public function toWebpush(User $notifiable): array
    {
        return [
            'title' => $this->message->sender?->name ?? 'New message',
            'body' => $this->preview(),
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/badge-72.png',
            'tag' => 'conversation-'.$this->message->conversation_id,
            'data' => [
                'url' => URL::route('chat.index', ['c' => $this->message->conversation_id]),
                'conversation_id' => $this->message->conversation_id,
                'message_id' => $this->message->id,
            ],
        ];
    }

    private function preview(): string
    {
        if ($this->message->deleted_at) {
            return 'Message deleted';
        }

        return match ($this->message->type) {
            Message::TYPE_IMAGE => '📷 Photo',
            Message::TYPE_FILE => '📎 '.($this->message->attachments->first()?->original_name ?? 'File'),
            default => Str::limit($this->message->body ?? '', 80),
        };
    }
}
