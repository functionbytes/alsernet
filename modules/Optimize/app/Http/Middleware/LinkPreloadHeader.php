<?php

namespace Modules\Optimize\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Core\Models\Setting;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Emits HTTP/2 `Link: <url>; rel=preload; as=style|script|image` headers
 * for the first external stylesheet, the first script, and any hero-image
 * detected in the response HTML.
 *
 * Browsers start downloading these resources BEFORE the HTML parser sees
 * them → improves LCP (typical +3-5 PageSpeed points). Complements the
 * `InjectCriticalPreload` middleware (which writes the same hints as
 * <link> tags inside <head>); HTTP/2 multiplexing means the duplication
 * is cheap and the header arrives fastest.
 */
class LinkPreloadHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldApply($request, $response)) {
            return $response;
        }

        $html = $response->getContent();
        if (! is_string($html) || $html === '') {
            return $response;
        }

        $links = [];

        // First external stylesheet
        if (preg_match('/<link[^>]+rel=["\']stylesheet["\'][^>]+href=["\']([^"\']+)["\']/i', $html, $m)) {
            $links[] = self::entry($m[1], 'style');
        }

        // First external script
        if (preg_match('/<script[^>]+src=["\']([^"\']+\.js[^"\']*)["\']/i', $html, $m)) {
            $links[] = self::entry($m[1], 'script');
        }

        // Hero image (class includes hero|banner|slider) — LCP candidate
        if (preg_match('/<img[^>]+class=["\'][^"\']*(?:hero|banner|slider)[^"\']*["\'][^>]+src=["\']([^"\']+)["\']/i', $html, $m)
            || preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]+class=["\'][^"\']*(?:hero|banner|slider)[^"\']*["\']/i', $html, $m)) {
            $links[] = self::entry($m[1], 'image');
        }

        $links = array_filter($links);
        if ($links !== []) {
            // RFC 8288 — multiple Link values joined by comma.
            $response->headers->set('Link', implode(', ', $links), false);
        }

        return $response;
    }

    private static function entry(string $href, string $as): ?string
    {
        $href = trim($href);
        if ($href === '' || str_starts_with($href, 'data:')) {
            return null;
        }

        // Absolute / protocol-relative URLs pass through; relative URLs
        // keep their relativity (browsers resolve against the document).
        return '<'.$href.'>; rel=preload; as='.$as;
    }

    private function shouldApply(Request $request, Response $response): bool
    {
        if (Setting::get('optimize.enabled', '0') !== '1') {
            return false;
        }
        if (Setting::get('optimize.link_preload_header', '0') !== '1') {
            return false;
        }
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        if ($request->is('setting*') || $request->is('panel*') || $request->is('admin*')) {
            return false;
        }
        if ($request->expectsJson()) {
            return false;
        }
        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return false;
        }
        $ct = $response->headers->get('Content-Type', '');
        if (! str_contains($ct, 'text/html') && ! empty($ct)) {
            return false;
        }

        return true;
    }
}
