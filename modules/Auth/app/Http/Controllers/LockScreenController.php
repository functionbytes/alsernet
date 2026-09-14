<?php

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LockScreenController extends Controller
{
    public const SESSION_KEY = 'auth.screen_locked';

    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route($request->user()->redirectRouteName());
        }

        return view('auth::auth.lock');
    }

    public function lock(Request $request): JsonResponse|RedirectResponse
    {
        $request->session()->put(self::SESSION_KEY, now()->timestamp);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => route('auth.lock')]);
        }

        return redirect()->route('auth.lock');
    }

    public function unlock(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Contraseña incorrecta.'], 422);
            }

            return back()->withErrors(['password' => 'Contraseña incorrecta.']);
        }

        $request->session()->forget(self::SESSION_KEY);
        $redirect = route($request->user()->redirectRouteName());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'redirect' => $redirect]);
        }

        return redirect()->intended($redirect);
    }
}
