<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\PushService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $backoff = 30;

    public function __construct(public int $userId, public array $payload) {}

    public function handle(PushService $push): void
    {
        $user = User::find($this->userId);

        if (! $user) {
            return;
        }

        if ($user->settings && ! $user->settings->notifications_enabled) {
            return;
        }

        $push->sendToUser($user, $this->payload);
    }
}
