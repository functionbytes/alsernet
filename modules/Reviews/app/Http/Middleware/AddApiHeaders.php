<?php

namespace Modules\Reviews\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AddApiHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add API version header
        $response->headers->set('X-API-Version', '1.0');

        // Add unique request ID for tracking
        $requestId = Str::uuid()->toString();
        $response->headers->set('X-Request-ID', $requestId);

        // Add Cache-Control headers for API responses
        if ($request->is('api/*')) {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        // Always add rate limit reset timestamp so clients can anticipate the window
        $response->headers->set('X-RateLimit-Reset', (string) now()->addMinute()->timestamp);

        return $response;
    }
}
