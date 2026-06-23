<?php

namespace App\Notifications\Channels;

use App\Jobs\SendPushJob;
use Illuminate\Notifications\Notification;

class WebPushChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebpush')) {
            return;
        }

        $payload = $notification->toWebpush($notifiable);

        if (! is_array($payload) || empty($payload)) {
            return;
        }

        SendPushJob::dispatch($notifiable->getKey(), $payload);
    }
}
