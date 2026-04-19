<?php

namespace Modules\Optimize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Applies aggressive `Cache-Control` headers on public GET responses so
 * Google (and other proxies) can cache pages for repeat visitors. This is
 * the largest savings item on PageSpeed ("Tiempos de vida de caché
 * eficientes" → ~8.6 MB).
 *
 * Only applied to:
 *  - GET / HEAD
 *  - 200 OK
 *  - Guest requests (not authenticated)
 *  - text/html (the middleware chain skips assets)
 */
class CacheControlHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldApply($request, $response)) {
            return $response;
        }

        $ttl = (int) Setting::get('optimize.cache_control_ttl', '3600');
        $ttl = max(60, $ttl);

        // public = Google/CDNs can cache. must-revalidate = fresh fetch once expired.
        $response->headers->set('Cache-Control', "public, max-age={$ttl}, must-revalidate");
        $response->headers->set('Vary', 'Accept-Encoding, Accept-Language');

        return $response;
    }

    private function shouldApply(Request $request, Response $response): bool
    {
        if (Setting::get('optimize.enabled', '0') !== '1') {
            return false;
        }
        if (Setting::get('optimize.cache_control_headers', '0') !== '1') {
            return false;
        }
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        // Never cache authenticated responses — CSRF tokens differ per session.
        if ($request->user()) {
            return false;
        }
        if ($request->is('setting*') || $request->is('admin*') || $request->is('panel*')) {
            return false;
        }
        if ($request->expectsJson()) {
            return false;
        }
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html') && ! empty($contentType)) {
            return false;
        }

        return true;
    }
}
