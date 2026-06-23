<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\LoginHistoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function logout(
        Request $request,
        LoginHistoryService $history,
        AuditService $audit,
    ): RedirectResponse {
        $user = $request->user();

        if ($user) {
            $audit->log('user.logout', $user);
            $history->markSignedOut($user->id, $request->session()->getId());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
