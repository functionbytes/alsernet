<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class HttpsProtocolMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = Cache::remember('app.installed', 3600, fn () => Schema::hasTable('settings'));

        if (! $installed) {
            return $next($request);
        }

        if (setting('FORCE_SSL') == 'on' && ! $request->secure()) {
            return redirect()->secure($request->getPathInfo());
        }

        return $next($request);
    }
}
