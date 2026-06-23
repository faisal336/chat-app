<?php

namespace App\Notifications;

use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TestPushNotification extends Notification
{
    use Queueable;

    public function via(User $notifiable): array
    {
        // Test push always goes via WebPush regardless of preferences —
        // the point is to verify the pipeline end-to-end.
        return ['database', WebPushChannel::class];
    }

    public function toArray(User $notifiable): array
    {
        return [
            'type' => 'test_push',
            'message' => 'Test push notification sent at '.now()->toDateTimeString(),
        ];
    }

    public function toWebpush(User $notifiable): array
    {
        return [
            'title' => 'Test notification',
            'body' => 'If you see this, push notifications are working end-to-end.',
            'icon' => '/icons/icon-192.png',
            'badge' => '/icons/badge-72.png',
            'tag' => 'test-push',
            'data' => ['url' => route('settings')],
        ];
    }
}
