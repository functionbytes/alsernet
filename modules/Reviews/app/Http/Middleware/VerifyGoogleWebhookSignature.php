<?php

namespace Modules\Reviews\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verify that incoming webhook requests originate from Google Cloud Pub/Sub
 * by checking the Bearer token in the Authorization header.
 */
class VerifyGoogleWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('reviews.general.webhooks.google_verification_token');

        if (! $expectedToken) {
            return response()->json(['error' => 'Webhook not configured'], Response::HTTP_UNAUTHORIZED);
        }

        $authHeader = $request->header('Authorization', '');
        $token = str_starts_with($authHeader, 'Bearer ')
            ? substr($authHeader, 7)
            : '';

        if (! hash_equals($expectedToken, $token)) {
            return response()->json(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
