<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Auth;
use Closure;
class IsManager
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check() || !$request->user()->hasRole('manager')) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        return $next($request);

    }

}
