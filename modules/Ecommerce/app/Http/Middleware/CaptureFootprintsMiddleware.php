<?php

namespace Modules\Ecommerce\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Ecommerce\Services\FootprinterService;
use Symfony\Component\HttpFoundation\Response;

class CaptureFootprintsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth('ecommerce')->check()) {
            $footprint = app(FootprinterService::class)->capture();
            session()->put('ecommerce_footprint', $footprint);
        }

        return $next($request);
    }
}
