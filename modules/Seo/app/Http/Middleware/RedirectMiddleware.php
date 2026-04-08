<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Modules\Seo\Models\SeoRedirect;
use Modules\Seo\Models\SeoRedirectHit;
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
        $cacheKey = SeoRedirect::cacheKey($currentPath);

        $redirect = Cache::remember($cacheKey, 3600, function () use ($currentPath) {
            return SeoRedirect::findBySourcePath($currentPath);
        });

        if ($redirect) {
            $this->incrementHitsAsync($redirect->id);

            return redirect($redirect->target_path, $redirect->status_code);
        }

        // Wildcard redirects (not cached — evaluated on each request)
        $wildcardRedirects = SeoRedirect::currentlyActive()
            ->where('is_wildcard', true)
            ->where('is_regex', false)
            ->get(['id', 'source_path', 'target_path', 'status_code', 'hits_count']);

        foreach ($wildcardRedirects as $wildcardRedirect) {
            $pattern = '/^'.str_replace(['\*', '\/'], ['(.*)', '\/'], preg_quote($wildcardRedirect->source_path, '/')).'$/';
            if (@preg_match($pattern, $currentPath, $matches) === 1) {
                $target = str_replace('*', $matches[1] ?? '', $wildcardRedirect->target_path);
                $this->incrementHitsAsync($wildcardRedirect->id);

                return redirect($target, $wildcardRedirect->status_code);
            }
        }

        // Fall back to regex redirects (not cached — evaluated on each request)
        $regexRedirects = SeoRedirect::currentlyActive()
            ->where('is_regex', true)
            ->get(['id', 'source_path', 'target_path', 'status_code']);

        foreach ($regexRedirects as $regexRedirect) {
            if (@preg_match($regexRedirect->source_path, $currentPath) === 1) {
                $target = @preg_replace($regexRedirect->source_path, $regexRedirect->target_path, $currentPath);
                $this->incrementHitsAsync($regexRedirect->id);

                return redirect($target, $regexRedirect->status_code);
            }
        }

        return $next($request);
    }

    /**
     * Increment hits count and record daily analytics.
     */
    protected function incrementHitsAsync(int $redirectId): void
    {
        if (config('queue.default') !== 'sync') {
            dispatch(function () use ($redirectId) {
                SeoRedirect::where('id', $redirectId)->increment('hits_count');

                try {
                    SeoRedirectHit::recordHit($redirectId);
                } catch (\Throwable) {
                }

                $redirect = SeoRedirect::find($redirectId);
                if ($redirect) {
                    Cache::forget(SeoRedirect::cacheKey($redirect->source_path));
                }
            })->afterResponse();
        } else {
            SeoRedirect::where('id', $redirectId)->increment('hits_count');

            try {
                SeoRedirectHit::recordHit($redirectId);
            } catch (\Throwable) {
            }
        }
    }
}
