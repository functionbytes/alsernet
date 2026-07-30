<?php

namespace Modules\HelpdeskSocial\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSocialApiCalls
{
    public function handle(Request $request, Closure $next): Response
    {
        $startedAt = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startedAt) * 1000, 2);

        Log::debug('Social API call', [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'platform' => $request->header('X-Social-Platform', 'unknown'),
        ]);

        return $response;
    }
}
