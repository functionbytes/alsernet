<?php

namespace Modules\Helpdesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Symfony\Component\HttpFoundation\Response;

class PortalAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $customerId = session('portal_customer_id');

        if (! $customerId) {
            return redirect()->route('portal.login')
                ->with('error', 'Debes iniciar sesion para acceder al portal.');
        }

        $customer = Customer::find($customerId);

        if (! $customer) {
            session()->forget('portal_customer_id');

            return redirect()->route('portal.login')
                ->with('error', 'Sesion invalida. Por favor inicia sesion nuevamente.');
        }

        $request->merge(['portal_customer' => $customer]);

        return $next($request);
    }
}
