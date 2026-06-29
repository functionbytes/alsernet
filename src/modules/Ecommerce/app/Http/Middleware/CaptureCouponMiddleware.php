<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureCouponMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->has('coupon')) {
            session()->put('ecommerce_coupon', $request->input('coupon'));
        }

        return $next($request);
    }
}
