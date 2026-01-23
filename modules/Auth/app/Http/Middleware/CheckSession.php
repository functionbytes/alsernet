<?php

namespace Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CheckSession
{
    public function handle($request, Closure $next)
    {

        if (! Auth::check() && Auth::user()->available == 1) {
            return redirect()->route('auth.login');
        } else {

            $previousSession = Auth::User()->session;

            if ($previousSession !== Session::getId()) {
                Session::getHandler()->destroy($previousSession);
                $request->session()->regenerate();
                Auth::user()->session = Session::getId();
                Auth::user()->save();
            }

            return $next($request);
        }

    }
}
