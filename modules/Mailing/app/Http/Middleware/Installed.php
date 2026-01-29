<?php

namespace Modules\Mailing\Http\Middleware;

use Closure;

class Installed
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (isInitiated()) {
            return redirect()->action('HomeController@index');
        }

        return $next($request);
    }
}
