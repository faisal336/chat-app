<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next, ?string $level = null): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $ok = $level === 'super' ? $user->isSuperAdmin() : $user->isAdmin();

        if (! $ok) {
            abort(403, 'Admin privileges required.');
        }

        return $next($request);
    }
}
