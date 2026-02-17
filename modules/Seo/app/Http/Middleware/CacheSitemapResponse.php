<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CacheSitemapResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Add cache headers for sitemap
        if ($response->isSuccessful() && str_contains($request->path(), 'sitemap')) {
            $response->header('Cache-Control', 'public, max-age=86400'); // 24 hours
            $response->header('X-Robots-Tag', 'noindex, nofollow');
        }

        return $response;
    }
}
