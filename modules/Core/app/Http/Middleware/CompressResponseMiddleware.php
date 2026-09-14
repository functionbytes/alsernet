<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compress text responses (HTML, JSON, CSS, JS) with Gzip when the browser
 * accepts it. Reduces transfer size by 60-80% for text content.
 */
class CompressResponseMiddleware
{
    /**
     * MIME types eligible for compression.
     *
     * @var array<int, string>
     */
    private const COMPRESSIBLE = [
        'text/html',
        'text/plain',
        'text/css',
        'text/javascript',
        'application/javascript',
        'application/json',
        'application/xml',
        'text/xml',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || $content === '') {
            return $response;
        }

        $compressed = gzencode($content, 6);

        if ($compressed === false) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Vary', 'Accept-Encoding');
        $response->headers->remove('Content-Length');

        return $response;
    }

    private function shouldCompress(Request $request, Response $response): bool
    {
        if (! str_contains($request->header('Accept-Encoding', ''), 'gzip')) {
            return false;
        }

        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        foreach (self::COMPRESSIBLE as $mime) {
            if (str_starts_with($contentType, $mime)) {
                return true;
            }
        }

        return false;
    }
}
