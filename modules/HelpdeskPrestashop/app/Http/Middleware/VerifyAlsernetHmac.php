<?php

namespace Modules\HelpdeskPrestashop\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\HelpdeskPrestashop\Support\HmacSigner;
use Symfony\Component\HttpFoundation\Response;

class VerifyAlsernetHmac
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('helpdeskprestashop.webhook_secret', '');

        if ($secret === '') {
            return response()->json(['ok' => false, 'error' => 'not configured'], 503);
        }

        $timestamp = (int) $request->header('X-Alsernet-Timestamp', 0);
        $signature = (string) $request->header('X-Alsernet-Signature', '');
        $rawBody = $request->getContent();

        if (! HmacSigner::verify($secret, $timestamp, $rawBody, $signature)) {
            Log::warning('HelpdeskPrestashop: HMAC verification failed.', [
                'ip' => $request->ip(),
                'event' => $request->header('X-Alsernet-Event'),
            ]);

            return response()->json(['ok' => false, 'error' => 'invalid signature'], 401);
        }

        return $next($request);
    }
}
