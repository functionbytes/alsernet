<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Enforces 2FA challenge for users who have it enabled.
 *
 * After login, if the user has 2FA enabled and hasn't completed
 * the challenge in this session, redirect to the challenge page.
 */
class TwoFactorChallenge
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->session()->get('two_factor_passed')) {
            return $next($request);
        }

        // Allow access to the challenge page and auth routes
        if ($request->routeIs('two-factor.challenge', 'auth.logout')) {
            return $next($request);
        }

        return redirect()->route('two-factor.challenge');
    }
}
