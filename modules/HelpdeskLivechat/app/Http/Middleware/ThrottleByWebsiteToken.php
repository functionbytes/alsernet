<?php

namespace Modules\HelpdeskLivechat\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-tenant rate limit keyed by `website_token` on the public widget API.
 *
 * Complements the per-IP `throttle:` middleware already applied per route:
 * IP throttling caps a single abusive client, but a distributed attacker (or a
 * spoofed Origin — see ValidateTrustedOrigin, which is NOT an authentication
 * control) can spread requests over many IPs against one store's token. This
 * middleware caps the aggregate volume a single token can generate.
 *
 * Limits are configurable per bucket in `helpdesklivechat.token_rate_limits`
 * and are deliberately generous (e.g. 300 conversations/hour per token) so
 * legitimate high-traffic stores are unaffected.
 *
 * When the request carries no website_token (body or X-Website-Token header)
 * the middleware is a no-op: endpoints that require the token reject those
 * requests anyway, and per-IP throttling still applies.
 */
class ThrottleByWebsiteToken
{
    public function handle(Request $request, Closure $next, string $bucket = 'default'): Response
    {
        $token = $request->input('website_token')
            ?? $request->header('X-Website-Token');

        if (! is_string($token) || $token === '') {
            return $next($request);
        }

        $limits = config("helpdesklivechat.token_rate_limits.{$bucket}")
            ?? config('helpdesklivechat.token_rate_limits.default', []);

        $maxAttempts = (int) ($limits['max_attempts'] ?? 300);
        $decaySeconds = (int) ($limits['decay_seconds'] ?? 3600);

        if ($maxAttempts <= 0) {
            // 0 (or negative) disables the bucket explicitly.
            return $next($request);
        }

        $key = 'helpdesklivechat:token-throttle:'.$bucket.':'.sha1($token);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'error' => 'Too many requests for this widget. Please retry later.',
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => $retryAfter,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);

        return $next($request);
    }
}
