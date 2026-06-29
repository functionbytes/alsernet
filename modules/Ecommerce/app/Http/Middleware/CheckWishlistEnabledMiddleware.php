<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckWishlistEnabledMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! setting('ecommerce_wishlist_enabled', true)) {
            return redirect()->route('shop.index');
        }

        return $next($request);
    }
}
