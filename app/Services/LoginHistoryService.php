<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LoginHistoryService
{
    public function recordSuccess(User $user, Request $request): void
    {
        LoginHistory::create([
            'user_id' => $user->id,
            'username_attempted' => $user->username,
            'success' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        UserSession::updateOrCreate(
            ['user_id' => $user->id, 'session_id' => $request->session()->getId()],
            [
                'device_name' => $this->deviceName($request),
                'platform' => $this->platform($request),
                'browser' => $this->browser($request),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_seen_at' => Carbon::now(),
                'signed_out_at' => null,
            ]
        );
    }

    public function recordFailure(?User $user, string $usernameAttempted, string $reason, Request $request): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'username_attempted' => $usernameAttempted,
            'success' => false,
            'failure_reason' => $reason,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public function markSignedOut(int $userId, string $sessionId): void
    {
        UserSession::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->update(['signed_out_at' => Carbon::now()]);
    }

    private function deviceName(Request $request): string
    {
        $ua = $request->userAgent() ?? '';

        return match (true) {
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Android') => 'Android device',
            str_contains($ua, 'Mac') => 'Mac',
            str_contains($ua, 'Windows') => 'Windows PC',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'Unknown device',
        };
    }

    private function platform(Request $request): ?string
    {
        $ua = $request->userAgent() ?? '';

        return match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };
    }

    private function browser(Request $request): ?string
    {
        $ua = $request->userAgent() ?? '';

        return match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'Chrome/') && ! str_contains($ua, 'Edg/') => 'Chrome',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Safari/') && ! str_contains($ua, 'Chrome/') => 'Safari',
            default => null,
        };
    }
}
