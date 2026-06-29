<?php

namespace Modules\Seo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Seo\Services\SeoService;

class AutoPaginationMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        $response = $next($request);

        if (! $request->has('page')) {
            return $response;
        }

        $page = (int) $request->query('page', 1);

        /** @var SeoService $seo */
        $seo = app('seo');

        if (! method_exists($seo, 'setPagination')) {
            return $response;
        }

        $prevUrl = '';
        if ($page > 1) {
            $prevUrl = $page === 2
                ? $request->url()
                : $request->fullUrlWithQuery(['page' => $page - 1]);
        }

        $nextUrl = $request->fullUrlWithQuery(['page' => $page + 1]);

        $seo->setPagination($prevUrl, $nextUrl);

        return $response;
    }
}
