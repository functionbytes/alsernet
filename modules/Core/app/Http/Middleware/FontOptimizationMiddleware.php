<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optimize font loading by injecting font-display: swap into @font-face
 * rules and adding preconnect hints for known font hosts.
 */
class FontOptimizationMiddleware
{
    /**
     * Known font CDN hosts for preconnect.
     *
     * @var array<int, string>
     */
    private const FONT_HOSTS = [
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'use.typekit.net',
        'cdn.jsdelivr.net',
        'cdnjs.cloudflare.com',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldOptimize($response)) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || $content === '') {
            return $response;
        }

        $content = $this->injectFontDisplaySwap($content);
        $content = $this->injectPreconnectHints($content);

        $response->setContent($content);

        return $response;
    }

    private function shouldOptimize(Response $response): bool
    {
        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_starts_with($contentType, 'text/html');
    }

    private function injectFontDisplaySwap(string $html): string
    {
        // Inject font-display: swap before the closing brace of @font-face rules
        return preg_replace(
            '/(@font-face\s*\{[^}]*?)(\})/i',
            '$1  font-display: swap;\n$2',
            $html
        );
    }

    private function injectPreconnectHints(string $html): string
    {
        $hints = [];

        foreach (self::FONT_HOSTS as $host) {
            if (str_contains($html, $host) && ! str_contains($html, 'preconnect" href="https://'.$host)) {
                $hints[] = '<link rel="preconnect" href="https://'.$host.'" crossorigin>';
            }
        }

        if (empty($hints)) {
            return $html;
        }

        $hintsHtml = implode("\n", $hints)."\n";

        // Insert after <head> if present
        if (preg_match('/<head[^>]*>/i', $html)) {
            return preg_replace('/(<head[^>]*>)/i', '$1'.
                "\n".$hintsHtml, $html, 1);
        }

        // Otherwise prepend
        return $hintsHtml.$html;
    }
}
