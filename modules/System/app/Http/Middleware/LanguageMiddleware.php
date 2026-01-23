<?php

namespace Modules\System\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LanguageMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            DB::connection()->getPdo();
            if (! DB::getSchemaBuilder()->hasTable('backups')) {

                return $next($request);
            } else {

                \App::setlocale(session()->get('locale') ?? @(DB::table('backups')->where('key', 'default_lang')->first()->value));

                return $next($request);
            }
        } catch (\Exception $e) {
            return $next($request);
            exit('Could not connect to the database.  Please check your configuration. error:'.$e);
        }
    }
}
