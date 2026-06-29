<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiLanguageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('X-Language')) {
            app()->setLocale($request->header('X-Language'));
        }

        return $next($request);
    }
}
