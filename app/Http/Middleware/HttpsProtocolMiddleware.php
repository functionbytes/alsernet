<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\DB;

class HttpsProtocolMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            DB::connection()->getPdo();
            if(!DB::getSchemaBuilder()->hasTable('settings')){

                return $next($request);
            }else{

                if(setting('FORCE_SSL') == 'on'){
                if (!$request->secure()) {
                    return redirect()->secure($request->getPathInfo());
                }
                return $next($request);
            }else{
                return $next($request);
            }
            }
        } catch (\Exception $e) {
            return $next($request);
            die("Could not connect to the database.  Please check your configuration. error:" . $e );
        }


    }
}
