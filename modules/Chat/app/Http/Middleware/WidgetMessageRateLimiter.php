<?php

namespace Modules\Chat\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WidgetMessageRateLimiter
{
    public function __construct(
        protected RateLimiter $limiter
    ) {}

    /**
     * Handle an incoming request.
     *
     * Limit: 10 messages per minute per customer
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $customerId = $request->input('customer_id') ?? $request->route('id');

        if (! $customerId) {
            return $next($request);
        }

        $key = 'widget-message:'.$customerId;

        if ($this->limiter->tooManyAttempts($key, 10)) {
            $seconds = $this->limiter->availableIn($key);

            return response()->json([
                'success' => false,
                'error' => 'Too many messages sent',
                'message' => 'You are sending messages too quickly. Please wait '.$seconds.' seconds before trying again.',
                'retry_after' => $seconds,
            ], 429);
        }

        $this->limiter->hit($key, 60);

        $response = $next($request);

        if ($response instanceof JsonResponse) {
            $response->header('X-RateLimit-Limit', 10);
            $response->header('X-RateLimit-Remaining', $this->limiter->remaining($key, 10));
        }

        return $response;
    }
}
