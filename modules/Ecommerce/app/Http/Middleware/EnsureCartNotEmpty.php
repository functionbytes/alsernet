<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ecommerce\Facades\CartHelper;
use Symfony\Component\HttpFoundation\Response;

class EnsureCartNotEmpty
{
    public function handle(Request $request, Closure $next): Response
    {
        if (CartHelper::getCart() === []) {
            return redirect()->route('shop.index')->with('error', 'Tu carrito esta vacio.');
        }

        return $next($request);
    }
}
