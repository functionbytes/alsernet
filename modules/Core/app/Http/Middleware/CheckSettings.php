<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSettings
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }
}
