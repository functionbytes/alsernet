<?php

namespace Modules\Chat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Rate limits by user role (requests per minute)
     */
    protected array $limits = [
        'chats' => 1000,
        'agent' => 300,
        'guest' => 60,
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $tier = 'default'): Response
    {
        $key = $this->resolveRequestKey($request);
        $maxAttempts = $this->getMaxAttempts($request, $tier);

        $response = RateLimiter::attempt(
            $key,
            $maxAttempts,
            function () use ($next, $request) {
                return $next($request);
            },
            60 // 1 minute decay
        );

        if (! $response) {
            return response()->json([
                'message' => 'Too many requests. Please slow down.',
                'retry_after' => RateLimiter::availableIn($key),
            ], 429);
        }

        // Add rate limit headers
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => RateLimiter::remaining($key, $maxAttempts),
            'X-RateLimit-Reset' => now()->addSeconds(60)->timestamp,
        ]);

        // Log API usage
        $this->logApiUsage($request, $key);

        return $response;
    }

    /**
     * Resolve the request key for rate limiting
     */
    protected function resolveRequestKey(Request $request): string
    {
        if ($user = $request->user()) {
            return 'api:user:'.$user->id;
        }

        // Fall back to IP for guests
        return 'api:ip:'.$request->ip();
    }

    /**
     * Get max attempts based on user role
     */
    protected function getMaxAttempts(Request $request, string $tier): int
    {
        if ($tier !== 'default') {
            return match ($tier) {
                'high' => 1000,
                'medium' => 300,
                'low' => 60,
                default => 100,
            };
        }

        $user = $request->user();

        if (! $user) {
            return $this->limits['guest'];
        }

        // Check if user is chats
        if ($user->hasRole('administrator') || $user->hasRole('super-admin')) {
            return $this->limits['chats'];
        }

        return $this->limits['agent'];
    }

    /**
     * Log API usage for analytics
     */
    protected function logApiUsage(Request $request, string $key): void
    {
        $date = now()->format('Y-m-d');
        $hour = now()->format('H');

        // Increment daily counter
        Cache::increment("api:usage:{$key}:{$date}");

        // Increment hourly counter
        Cache::increment("api:usage:{$key}:{$date}:{$hour}");

        // Store endpoint usage
        $endpoint = $request->path();
        Cache::increment("api:endpoint:{$endpoint}:{$date}");
    }
}
