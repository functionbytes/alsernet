<?php

namespace Modules\Ecommerce\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Ecommerce\Models\Customer;

class RegisterController extends Controller
{
    public function showRegistrationForm(): View
    {
        return view('ecommerce::shop.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:ecommerce_customers,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customer = Customer::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => bcrypt($validated['password']),
            'status' => 'active',
        ]);

        event(new Registered($customer));
        auth('ecommerce')->login($customer);

        return redirect()->route('shop.index');
    }
}
