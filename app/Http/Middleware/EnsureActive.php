<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'username' => 'Your account is disabled. Contact an administrator.',
            ]);
        }

        if ($user->pin_must_change && ! $request->routeIs('pin.change', 'pin.change.store', 'logout')) {
            return redirect()->route('pin.change');
        }

        return $next($request);
    }
}
