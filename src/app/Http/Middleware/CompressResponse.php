<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $accept = $request->header('Accept-Encoding', '');
        $contentType = $response->headers->get('Content-Type', '');

        if (
            ! str_contains($contentType, 'text/')
            && ! str_contains($contentType, 'json')
            && ! str_contains($contentType, 'xml')
            && ! str_contains($contentType, 'javascript')
        ) {
            return $response;
        }

        $content = $response->getContent();
        if (! $content || strlen($content) < 1024) {
            return $response;
        }

        if (str_contains($accept, 'br') && function_exists('brotli_compress')) {
            $compressed = brotli_compress($content, 4);
            $response->setContent($compressed);
            $response->headers->set('Content-Encoding', 'br');
            $response->headers->set('Content-Length', (string) strlen($compressed));
        } elseif (str_contains($accept, 'gzip')) {
            $compressed = gzencode($content, 6);
            $response->setContent($compressed);
            $response->headers->set('Content-Encoding', 'gzip');
            $response->headers->set('Content-Length', (string) strlen($compressed));
        }

        return $response;
    }
}
