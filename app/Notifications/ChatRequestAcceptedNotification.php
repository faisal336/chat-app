<?php

namespace App\Notifications;

use App\Models\ChatRequest;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ChatRequestAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(public ChatRequest $request) {}

    public function via(User $notifiable): array
    {
        $channels = ['database'];

        if (($notifiable->settings?->notifications_enabled ?? true)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'chat_request_accepted',
            'request_id' => $this->request->id,
            'recipient_id' => $this->request->recipient_id,
            'recipient_name' => $this->request->recipient?->name,
            'conversation_id' => $this->request->conversation_id,
        ];
    }

    public function toWebpush(User $notifiable): array
    {
        return [
            'title' => ($this->request->recipient?->name ?? 'Someone').' accepted your chat request',
            'body' => 'You can now message each other.',
            'icon' => '/icons/icon-192.png',
            'tag' => 'chat-request-accepted-'.$this->request->id,
            'data' => [
                'url' => $this->request->conversation_id
                    ? URL::route('chat.index', ['c' => $this->request->conversation_id])
                    : URL::route('chat.index'),
                'conversation_id' => $this->request->conversation_id,
            ],
        ];
    }
}
