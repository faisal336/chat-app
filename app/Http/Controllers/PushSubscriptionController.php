<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|url|max:2048',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
            'content_encoding' => 'nullable|string|max:20',
        ]);

        $hash = PushSubscription::hashEndpoint($data['endpoint']);

        $sub = PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint_hash' => $hash,
            ],
            [
                'endpoint' => $data['endpoint'],
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
                'content_encoding' => $data['content_encoding'] ?? 'aesgcm',
                'user_agent' => $request->userAgent(),
                'last_used_at' => Carbon::now(),
            ]
        );

        return response()->json(['id' => $sub->id]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => 'required|string|max:2048',
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', PushSubscription::hashEndpoint($data['endpoint']))
            ->delete();

        return response()->json(['ok' => true]);
    }
}
