<?php

namespace Modules\Mailing\Http\Middleware;

use Closure;

class Subscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! config('app.saas')) {
            return $next($request);
        }

        return $next($request);
    }
}
