<?php

namespace Modules\Ecommerce\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('ecommerce::shop.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (auth('ecommerce')->attempt($validated, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('shop.index'));
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden.',
        ])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        auth('ecommerce')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('shop.index');
    }
}
