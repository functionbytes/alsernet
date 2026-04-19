<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add X-RateLimit-* headers to API responses using our AuthRateLimiter scope.
 * Applied to Sanctum-authenticated routes to expose quota state to clients.
 */
class AddRateLimitHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $key = $this->resolveKey($request);
        $max = 60;

        $response->headers->set('X-RateLimit-Limit', (string) $max);
        $response->headers->set('X-RateLimit-Remaining', (string) max(0, $max - RateLimiter::attempts($key)));

        if (RateLimiter::tooManyAttempts($key, $max)) {
            $response->headers->set('X-RateLimit-Reset', (string) (time() + RateLimiter::availableIn($key)));
            $response->headers->set('Retry-After', (string) RateLimiter::availableIn($key));
        }

        return $response;
    }

    private function resolveKey(Request $request): string
    {
        $user = $request->user();

        return 'api:'.($user?->id ?: $request->ip());
    }
}
