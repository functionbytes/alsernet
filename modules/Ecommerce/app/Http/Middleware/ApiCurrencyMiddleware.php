<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ecommerce\Supports\CurrencySupport;
use Symfony\Component\HttpFoundation\Response;

class ApiCurrencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasHeader('X-Currency')) {
            $currency = CurrencySupport::getDefaultCurrency();
            app()->instance('ecommerce_currency', $currency);
        }

        return $next($request);
    }
}
