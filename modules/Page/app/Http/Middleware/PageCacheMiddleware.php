<?php

namespace Modules\Page\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Page\Services\PageCacheService;
use Symfony\Component\HttpFoundation\Response;

class PageCacheMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Only cache GET requests
        if ($request->method() !== 'GET' || ! PageCacheService::isEnabled()) {
            return $next($request);
        }

        // Don't cache if user is authenticated (may have different permissions)
        if (auth()->check()) {
            return $next($request);
        }

        // Don't cache admin routes
        if ($request->is('admin/*') || $request->is('dashboard/*')) {
            return $next($request);
        }

        // Extract slug from path if it's a page route
        $slug = $this->extractSlug($request->path());

        if ($slug) {
            $cached = PageCacheService::get($slug);

            if ($cached) {
                return response()->json($cached);
            }
        }

        return $next($request);
    }

    private function extractSlug(string $path): ?string
    {
        $prefix = setting('permalink-modules-page-models-page', '');

        if ($prefix && str_starts_with($path, $prefix)) {
            return ltrim(str_replace($prefix.'/', '', $path), '/');
        }

        return null;
    }
}
