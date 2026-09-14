<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Auth\Http\Controllers\LockScreenController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Redirect to the lock screen when the session is flagged as locked.
 * Works with `auth.lock` route only (unlock endpoint excluded).
 */
class CheckSessionLock
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('auth.auth-policy.lock_screen.enabled', true)) {
            return $next($request);
        }

        if (! $request->user()) {
            return $next($request);
        }

        if (! $request->session()->has(LockScreenController::SESSION_KEY)) {
            return $next($request);
        }

        $allowed = ['auth.lock', 'auth.lock.unlock', 'auth.logout'];

        if (in_array($request->route()?->getName(), $allowed, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Sesión bloqueada. Desbloquea para continuar.',
                'redirect' => route('auth.lock'),
            ], 423);
        }

        return redirect()->route('auth.lock');
    }
}
