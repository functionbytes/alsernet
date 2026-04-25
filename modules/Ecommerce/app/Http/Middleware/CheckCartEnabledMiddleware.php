<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCartEnabledMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('ecommerce_cart_enabled', true)) {
            return redirect()->route('shop.index')->with('error', 'Carrito deshabilitado');
        }

        return $next($request);
    }
}
