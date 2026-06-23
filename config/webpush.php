<?php

return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        'public_key' => env('VAPID_PUBLIC_KEY'),
        'private_key' => env('VAPID_PRIVATE_KEY'),
    ],

    'ttl' => 60 * 60 * 24, // 24h

    'urgency' => 'normal',
];
