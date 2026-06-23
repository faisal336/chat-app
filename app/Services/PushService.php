<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushService
{
    public function sendToUser(User $user, array $payload): void
    {
        $subscriptions = $user->pushSubscriptions;

        if ($subscriptions->isEmpty()) {
            return;
        }

        $auth = [
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ];

        if (! $auth['VAPID']['publicKey'] || ! $auth['VAPID']['privateKey']) {
            Log::warning('VAPID keys not configured; skipping push.');

            return;
        }

        try {
            $webPush = new WebPush($auth, [
                'TTL' => config('webpush.ttl'),
                'urgency' => config('webpush.urgency'),
            ]);

            foreach ($subscriptions as $sub) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys' => [
                            'p256dh' => $sub->public_key,
                            'auth' => $sub->auth_token,
                        ],
                        'contentEncoding' => $sub->content_encoding,
                    ]),
                    json_encode($payload)
                );
            }

            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();

                if ($report->isSuccess()) {
                    PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))
                        ->update(['last_used_at' => Carbon::now()]);
                } elseif ($report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint_hash', PushSubscription::hashEndpoint($endpoint))->delete();
                } else {
                    Log::warning('WebPush failed', [
                        'endpoint_hash' => substr(PushSubscription::hashEndpoint($endpoint), 0, 10),
                        'reason' => $report->getReason(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('WebPush exception: '.$e->getMessage());
        }
    }
}
