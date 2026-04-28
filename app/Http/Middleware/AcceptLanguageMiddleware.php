<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcceptLanguageMiddleware
{
    private const SUPPORTED = ['es', 'en', 'pt'];

    private const FALLBACK = 'es';

    public function handle(Request $request, Closure $next): Response
    {
        $header = $request->header('Accept-Language', self::FALLBACK);

        $first = strtolower(substr(explode(',', $header)[0] ?? '', 0, 2));

        $locale = in_array($first, self::SUPPORTED, true) ? $first : self::FALLBACK;

        app()->setLocale($locale);

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}
