<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {

        if (!Auth::check() || !$request->user()->hasRole($role)) {
            return redirect('login');
        }

        if ($request->user()) {
            $request->attributes->set($role, $request->user());
            $request->session()->put($role,  $request->user());
            app()->instance($role, $request->user());
        }

        return $next($request);
    }
}
