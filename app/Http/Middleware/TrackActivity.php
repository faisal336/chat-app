<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class TrackActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user) {
            $now = Carbon::now();

            if (! $user->last_active_at || $user->last_active_at->diffInSeconds($now) > 30) {
                $user->forceFill(['last_active_at' => $now])->saveQuietly();
            }

            $sessionId = $request->session()->getId();
            UserSession::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->whereNull('signed_out_at')
                ->update(['last_seen_at' => $now]);
        }

        return $response;
    }
}
