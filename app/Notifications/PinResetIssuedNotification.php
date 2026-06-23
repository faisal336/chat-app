<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PinResetIssuedNotification extends Notification
{
    use Queueable;

    public function via(User $notifiable): array
    {
        return ['database', WebPushChannel::class];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'pin_reset_issued',
            'message' => 'An administrator issued a temporary PIN. Sign in to set a new PIN.',
        ];
    }

    public function toWebpush(User $notifiable): array
    {
        return [
            'title' => 'Temporary PIN issued',
            'body' => 'Sign in with the temporary PIN provided to set a new one.',
            'icon' => '/icons/icon-192.png',
            'tag' => 'pin-reset',
            'data' => ['url' => route('login')],
        ];
    }
}
