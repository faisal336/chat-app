<?php

namespace App\Notifications;

use App\Models\ChatRequest;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class ChatRequestReceivedNotification extends Notification
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
            'type' => 'chat_request',
            'request_id' => $this->request->id,
            'requester_id' => $this->request->requester_id,
            'requester_name' => $this->request->requester?->name,
            'message' => Str::limit($this->request->message ?? '', 120),
        ];
    }

    public function toWebpush(User $notifiable): array
    {
        return [
            'title' => ($this->request->requester?->name ?? 'Someone').' wants to chat',
            'body' => $this->request->message
                ? Str::limit($this->request->message, 100)
                : 'Open the app to accept or reject.',
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/badge-72.png',
            'tag' => 'chat-request-'.$this->request->id,
            'data' => [
                'url' => URL::route('chat.index', ['view' => 'requests']),
                'request_id' => $this->request->id,
            ],
        ];
    }
}
