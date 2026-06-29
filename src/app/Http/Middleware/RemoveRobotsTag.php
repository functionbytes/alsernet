<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RemoveRobotsTag
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('X-Robots-Tag');

        return $response;
    }
}
