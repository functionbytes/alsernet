<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProductSpecificationEnabledMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('ecommerce_product_specification_enabled', true)) {
            abort(404);
        }

        return $next($request);
    }
}
