<?php

namespace Modules\Campaign\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rate limit para endpoints públicos de tracking (pixel, click, unsubscribe).
 * Usa Laravel RateLimiter (Redis/Memcached) con límite por IP.
 */
class TrackRateLimit
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'track:'.$request->ip();
        $max = (int) config('campaign.tracking.rate_limit', 60);
        $decay = 60; // 1 minuto

        $executed = RateLimiter::attempt($key, $max, function () {}, $decay);

        if (! $executed) {
            $retryAfter = RateLimiter::availableIn($key);

            return response('Too Many Requests', 429)
                ->header('Retry-After', $retryAfter)
                ->header('X-RateLimit-Limit', (string) $max)
                ->header('X-RateLimit-Remaining', '0')
                ->header('X-RateLimit-Reset', (string) now()->addSeconds($retryAfter)->timestamp);
        }

        $remaining = RateLimiter::remaining($key, $max);

        $response = $next($request);
        $response->headers->set('X-RateLimit-Limit', (string) $max);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $remaining));

        return $response;
    }
}
