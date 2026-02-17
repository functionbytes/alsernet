<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Seo\Models\SeoRedirect;
use Symfony\Component\HttpFoundation\Response;

class RedirectMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip redirect check for settings routes and API routes
        if ($request->is('settings/*') || $request->is('api/*')) {
            return $next($request);
        }

        // Get the current path
        $currentPath = $request->path();

        // Normalize to root path
        if ($currentPath === '/') {
            $currentPath = '/';
        } else {
            $currentPath = '/'.ltrim($currentPath, '/');
        }

        // Check for redirect with caching for better performance
        $cacheKey = 'seo_redirect_'.md5(strtolower($currentPath));

        $redirect = Cache::remember($cacheKey, 3600, function () use ($currentPath) {
            return SeoRedirect::findBySourcePath($currentPath);
        });

        if ($redirect) {
            // Increment hits count asynchronously to avoid slowing down the redirect
            $this->incrementHitsAsync($redirect->id);

            // Perform the redirect
            return redirect($redirect->target_path, $redirect->status_code);
        }

        return $next($request);
    }

    /**
     * Increment hits count asynchronously.
     */
    protected function incrementHitsAsync(int $redirectId): void
    {
        // Use Laravel's queue if available, otherwise increment directly
        if (config('queue.default') !== 'sync') {
            dispatch(function () use ($redirectId) {
                SeoRedirect::where('id', $redirectId)->increment('hits_count');

                // Clear cache for this redirect
                $redirect = SeoRedirect::find($redirectId);
                if ($redirect) {
                    Cache::forget('seo_redirect_'.md5(strtolower($redirect->source_path)));
                }
            })->afterResponse();
        } else {
            SeoRedirect::where('id', $redirectId)->increment('hits_count');
        }
    }
}
