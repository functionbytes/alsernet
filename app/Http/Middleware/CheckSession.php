<?php

namespace App\Http\Middleware;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Closure;

class CheckSession
{
    public function handle($request, Closure $next)
    {

        if(!Auth::check() && Auth::user()->available == 1){
            return redirect()->route('auth.login');
        }else{

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
