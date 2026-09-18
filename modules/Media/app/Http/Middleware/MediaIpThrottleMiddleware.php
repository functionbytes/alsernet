<?php

namespace Modules\Media\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class MediaIpThrottleMiddleware
{
    public function handle(Request $request, Closure $next, int $maxAttempts = 120, int $decayMinutes = 1): Response
    {
        $key = 'media:ip:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'message' => 'Demasiadas solicitudes desde esta IP',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
