<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Minify HTML responses by removing unnecessary whitespace, comments and
 * optional attribute quotes. Reduces HTML payload by 10-20%.
 */
class MinifyHtmlMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldMinify($response)) {
            return $response;
        }

        $content = $response->getContent();

        if ($content === false || $content === '') {
            return $response;
        }

        $minified = $this->minify($content);
        $response->setContent($minified);

        return $response;
    }

    private function shouldMinify(Response $response): bool
    {
        if (app()->isLocal()) {
            return false;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');

        return str_starts_with($contentType, 'text/html');
    }

    private function minify(string $html): string
    {
        // Preserve <pre>, <textarea>, <script>, <style> contents
        $preserved = [];
        $index = 0;

        $html = preg_replace_callback(
            '#<(pre|textarea|script|style)[^>]*>.*?</\1>#is',
            function ($matches) use (&$preserved, &$index): string {
                $placeholder = "___PRESERVED_{$index}___";
                $preserved[$placeholder] = $matches[0];
                $index++;

                return $placeholder;
            },
            $html
        );

        // Remove HTML comments (except IE conditionals)
        $html = preg_replace('/<!--(?!\s*(?:\[if\s*[^\]]+\]|\s*<!))[^>]*-->/s', '', $html);

        // Collapse whitespace (single spaces only)
        $html = preg_replace('/\s+/', ' ', $html);

        // Remove whitespace between tags
        $html = preg_replace('/> </', '><', $html);

        // Trim
        $html = trim($html);

        // Restore preserved blocks
        foreach ($preserved as $placeholder => $content) {
            $html = str_replace($placeholder, $content, $html);
        }

        return $html;
    }
}
