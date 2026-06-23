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

class MentionNotification extends Notification
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function via(User $notifiable): array
    {
        $channels = ['database'];

        $pref = NotificationPreference::where('user_id', $notifiable->id)
            ->where('event_type', NotificationPreference::EVENT_MENTION)
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
            'type' => 'mention',
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender?->name,
            'preview' => Str::limit($this->message->body ?? '', 80),
        ];
    }

    public function toWebpush(User $notifiable): array
    {
        return [
            'title' => ($this->message->sender?->name ?? 'Someone').' mentioned you',
            'body' => Str::limit($this->message->body ?? '', 100),
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/badge-72.png',
            'tag' => 'mention-'.$this->message->conversation_id,
            'data' => [
                'url' => URL::route('chat.index', ['c' => $this->message->conversation_id]),
                'conversation_id' => $this->message->conversation_id,
                'message_id' => $this->message->id,
            ],
        ];
    }
}
