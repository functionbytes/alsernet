<?php

namespace Modules\System\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = Cache::remember('app.installed', 3600, fn () => Schema::hasTable('settings'));

        if (! $installed) {
            return $next($request);
        }

        App::setLocale(session()->get('locale') ?? setting('default_lang', config('app.locale')));

        return $next($request);
    }
}
