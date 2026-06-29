<?php

namespace Modules\Ecommerce\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Customer;

class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, ?string $token = null): View
    {
        return view('ecommerce::shop.auth.reset-password')->with([
            'token' => $token,
            'email' => $request->input('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('ecommerce_customers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Customer $customer, string $password) {
                $customer->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($customer));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('ecommerce.login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }
}
