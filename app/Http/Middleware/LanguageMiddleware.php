<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Closure;
use Illuminate\Support\Facades\DB;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            DB::connection()->getPdo();
            if(!DB::getSchemaBuilder()->hasTable('settings')){

                return $next($request);
            }else{

                \App::setlocale(session()->get('locale') ?? @(DB::table('settings')->where('key', 'default_lang')->first()->value) );
                return $next($request);
            }
        } catch (\Exception $e) {
            return $next($request);
            die("Could not connect to the database.  Please check your configuration. error:" . $e );
        }
    }
}
