<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCompareEnabledMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('ecommerce_compare_enabled', true)) {
            return redirect()->route('shop.index');
        }

        return $next($request);
    }
}
