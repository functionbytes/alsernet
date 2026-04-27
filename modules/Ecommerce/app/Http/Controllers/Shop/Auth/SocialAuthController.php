<?php

namespace Modules\Ecommerce\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Modules\Ecommerce\Models\Customer;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook'];

    public function redirect(string $provider): SymfonyRedirect
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS, true), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable $e) {
            return redirect()->route('ecommerce.login')
                ->with('error', 'No se pudo iniciar sesion con '.ucfirst($provider).'. Intenta de nuevo.');
        }

        $customer = Customer::query()
            ->where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $customer && $socialUser->getEmail()) {
            $customer = Customer::query()->where('email', $socialUser->getEmail())->first();

            if ($customer) {
                $customer->update([
                    'provider' => $provider,
                    'provider_id' => $socialUser->getId(),
                ]);
            }
        }

        if (! $customer) {
            $customer = Customer::query()->create([
                'name' => $socialUser->getName() ?: ($socialUser->getNickname() ?: 'Cliente'),
                'email' => $socialUser->getEmail() ?: ($provider.'_'.$socialUser->getId().'@example.com'),
                'password' => bcrypt(Str::random(40)),
                'avatar' => $socialUser->getAvatar(),
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        }

        Auth::guard('ecommerce')->login($customer, true);

        return redirect()->intended(route('account.dashboard'));
    }
}
