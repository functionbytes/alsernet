<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;

class IsInventarie
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check() || !$request->user()->hasRole('inventarie')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $inventarie = Auth::user();
        if ($inventarie) {
            $request->attributes->set('inventarie', $inventarie);
            $request->session()->put('inventarie',  $inventarie);
            app()->instance('inventarie', $inventarie);
        }

        return $next($request);

    }

}
