<?php

namespace Modules\Ecommerce\Http\Controllers\Shop\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Ecommerce\Events\CustomerEmailVerified;
use Modules\Ecommerce\Models\Customer;
use Modules\Ecommerce\Notifications\ConfirmEmailNotification;

class EmailVerificationController extends Controller
{
    public function verify(Request $request): RedirectResponse
    {
        $token = $request->query('token');

        if (! $token) {
            return redirect()->route('ecommerce.login')
                ->with('error', 'Token de verificacion invalido.');
        }

        $customer = Customer::query()
            ->where('email_verification_token', $token)
            ->first();

        if (! $customer) {
            return redirect()->route('ecommerce.login')
                ->with('error', 'El enlace de verificacion no es valido o ya fue usado.');
        }

        $customer->update([
            'email_verified_at' => now(),
            'email_verification_token' => null,
        ]);

        CustomerEmailVerified::dispatch($customer);

        return redirect()->route('ecommerce.login')
            ->with('success', '¡Correo verificado con éxito! Ya puedes iniciar sesión.');
    }

    public function resend(Request $request): RedirectResponse
    {
        /** @var Customer $customer */
        $customer = auth('ecommerce')->user();

        if ($customer->email_verified_at !== null) {
            return redirect()->back()
                ->with('info', 'Tu correo ya está verificado.');
        }

        $token = Str::random(60);
        $customer->update(['email_verification_token' => $token]);
        $customer->notify(new ConfirmEmailNotification($token));

        Log::info('Reenvio de correo de verificacion', ['customer_id' => $customer->id]);

        return redirect()->back()
            ->with('success', 'Correo de verificacion reenviado. Revisa tu bandeja de entrada.');
    }
}
