<?php

namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HttpsProtocolMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            DB::connection()->getPdo();
            if (! DB::getSchemaBuilder()->hasTable('backups')) {

                return $next($request);
            } else {

                if (setting('FORCE_SSL') == 'on') {
                    if (! $request->secure()) {
                        return redirect()->secure($request->getPathInfo());
                    }

                    return $next($request);
                } else {
                    return $next($request);
                }
            }
        } catch (\Exception $e) {
            return $next($request);
            exit('Could not connect to the database.  Please check your configuration. error:'.$e);
        }

    }
}
