<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Seo\Models\SeoMeta;

class XRobotsTagMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        // Only apply to successful HTML responses
        if (! $response instanceof Response || $response->getStatusCode() !== 200) {
            return $response;
        }

        $path = $request->path();

        // Skip admin and API routes
        if (str_starts_with($path, 'setting/') || str_starts_with($path, 'api/')) {
            return $response;
        }

        $meta = SeoMeta::where('canonical_url', $request->url())
            ->orWhere('canonical_url', $request->getPathInfo())
            ->select(['robots'])
            ->first();

        if ($meta && ! empty($meta->robots) && strtolower($meta->robots) !== 'index, follow') {
            $response->header('X-Robots-Tag', $meta->robots);
        }

        return $response;
    }
}
