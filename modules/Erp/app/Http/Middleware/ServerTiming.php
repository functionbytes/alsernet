<?php

namespace Modules\Erp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Añade `Server-Timing: app;dur=<ms>` a las respuestas de /api/erp/*.
 *
 * Sustituye a los Log::debug('=== TIEMPO ...') que había en cada acción:
 * el dato llega al cliente (DevTools, curl -i, APM) sin escribir en el log.
 */
class ServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);

        $response = $next($request);

        $response->headers->set(
            'Server-Timing',
            sprintf('app;dur=%.1f', (microtime(true) - $start) * 1000),
            false
        );

        return $response;
    }
}
