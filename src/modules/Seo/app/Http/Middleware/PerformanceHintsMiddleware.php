<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects performance hints and security headers into public HTML responses
 * to boost PageSpeed / Core Web Vitals:
 *
 *   - <link rel="preconnect" ...>  for third-party origins
 *   - <link rel="dns-prefetch" ...> as cheaper fallback
 *   - <link rel="preload" ...>      for critical resources (fonts, hero image)
 *   - Cache-Control                  public caching for anonymous GET requests
 *   - Security headers               X-Content-Type-Options, Referrer-Policy, etc.
 *
 * Only touches HTML responses for anonymous GET requests — never interferes
 * with panel/admin, API, or authenticated traffic.
 */
class PerformanceHintsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $this->shouldProcess($request, $response)) {
            return $response;
        }

        $this->injectHintsIntoHtml($response);
        $this->addSecurityHeaders($response);
        $this->addCacheHeaders($request, $response);

        return $response;
    }

    private function shouldProcess(Request $request, Response $response): bool
    {
        // Only GET/HEAD
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        // Skip panel/admin/api
        $path = $request->path();
        if ($request->is('panel/*') || $request->is('api/*') || $request->is('settings/*')) {
            return false;
        }

        // Only successful HTML responses
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (! str_contains(strtolower($contentType), 'html')) {
            return false;
        }

        return true;
    }

    private function injectHintsIntoHtml(Response $response): void
    {
        $html = $response->getContent();
        if (! $html || ! str_contains($html, '</head>')) {
            return;
        }

        $hints = $this->buildHints();
        if ($hints === '') {
            return;
        }

        $response->setContent(str_replace('</head>', $hints."\n</head>", $html));
    }

    private function buildHints(): string
    {
        $lines = [];

        // Preconnect origins
        $preconnect = array_filter(array_map('trim', explode(',', (string) seo_setting('performance.preconnect', config('Seo.performance.preconnect', '')))));
        foreach ($preconnect as $origin) {
            $origin = $this->normalizeOrigin($origin);
            if ($origin === null) {
                continue;
            }
            // gstatic (fonts) needs crossorigin; others don't break with it
            $crossorigin = str_contains($origin, 'fonts.gstatic.com') ? ' crossorigin' : '';
            $lines[] = sprintf('<link rel="preconnect" href="%s"%s>', e($origin), $crossorigin);
        }

        // DNS prefetch fallbacks
        $dnsPrefetch = array_filter(array_map('trim', explode(',', (string) seo_setting('performance.dns_prefetch', config('Seo.performance.dns_prefetch', '')))));
        foreach ($dnsPrefetch as $origin) {
            $origin = $this->normalizeOrigin($origin);
            if ($origin === null) {
                continue;
            }
            $lines[] = sprintf('<link rel="dns-prefetch" href="%s">', e($origin));
        }

        // Preload critical resources
        $preload = (array) config('Seo.performance.preload', []);
        foreach ($preload as $entry) {
            if (empty($entry['href']) || empty($entry['as'])) {
                continue;
            }
            $attrs = [
                'rel' => 'preload',
                'href' => $entry['href'],
                'as' => $entry['as'],
            ];
            if (! empty($entry['type'])) {
                $attrs['type'] = $entry['type'];
            }
            if (! empty($entry['fetchpriority'])) {
                $attrs['fetchpriority'] = $entry['fetchpriority'];
            }
            $tag = '<link';
            foreach ($attrs as $k => $v) {
                $tag .= sprintf(' %s="%s"', $k, e((string) $v));
            }
            if (! empty($entry['crossorigin'])) {
                $tag .= ' crossorigin';
            }
            $lines[] = $tag.'>';
        }

        return implode("\n", $lines);
    }

    private function normalizeOrigin(string $origin): ?string
    {
        $origin = trim($origin);
        if ($origin === '') {
            return null;
        }
        // Accept "example.com" or "https://example.com"
        if (! preg_match('/^https?:\/\//', $origin)) {
            $origin = 'https://'.$origin;
        }
        $parsed = parse_url($origin);
        if (! $parsed || empty($parsed['host'])) {
            return null;
        }

        return ($parsed['scheme'] ?? 'https').'://'.$parsed['host'];
    }

    private function addSecurityHeaders(Response $response): void
    {
        if (! seo_setting('performance.enable_security_headers', config('Seo.performance.enable_security_headers', true))) {
            return;
        }

        $set = fn (string $name, string $value) => $response->headers->has($name)
            ?: $response->headers->set($name, $value);

        $set('X-Content-Type-Options', 'nosniff');
        $set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $set('X-Frame-Options', 'SAMEORIGIN');
        $set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), interest-cohort=()');
    }

    private function addCacheHeaders(Request $request, Response $response): void
    {
        $ttl = (int) seo_setting('performance.html_cache_seconds', config('Seo.performance.html_cache_seconds', 0));

        if ($ttl <= 0) {
            return;
        }

        // Never cache for authenticated users (personalized content)
        if ($request->hasSession() && $request->session()->has('auth_user_id')) {
            return;
        }

        if ($response->headers->has('Cache-Control') && ! str_contains((string) $response->headers->get('Cache-Control'), 'no-store')) {
            return;
        }

        $response->headers->set('Cache-Control', sprintf('public, max-age=%d, s-maxage=%d, stale-while-revalidate=%d', $ttl, $ttl, $ttl * 10));
        $response->headers->set('Vary', 'Accept-Encoding, Accept-Language');
    }
}
