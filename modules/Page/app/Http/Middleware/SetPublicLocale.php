<?php

namespace Modules\Page\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Page\Services\PageService;
use Symfony\Component\HttpFoundation\Response;

class SetPublicLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $supported = PageService::getSupportedLocales();
        $locale = session('public_locale');

        if ($locale && in_array($locale, $supported)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
