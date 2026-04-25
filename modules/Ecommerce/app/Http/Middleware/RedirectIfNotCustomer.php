<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('ecommerce')->check()) {
            return redirect()->route('ecommerce.login');
        }

        return $next($request);
    }
}
