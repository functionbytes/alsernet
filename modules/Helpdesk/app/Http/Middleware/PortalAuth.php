<?php

namespace Modules\Helpdesk\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Models\CustomerSession;
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

        $lastActivity = session('portal_last_activity_at');
        $idleMinutes = (int) config('helpdesk.portal.session_idle_minutes', 120);
        $lastActivityTimestamp = is_numeric($lastActivity)
            ? (int) $lastActivity
            : (strtotime((string) $lastActivity) ?: null);

        if ($lastActivityTimestamp !== null && now()->timestamp - $lastActivityTimestamp > ($idleMinutes * 60)) {
            CustomerSession::query()->where('session_id', $request->session()->getId())->delete();
            $request->session()->forget(['portal_customer_id', 'portal_customer_name', 'portal_last_activity_at']);

            return redirect()->route('portal.login')
                ->with('error', 'Tu sesión del portal ha caducado por inactividad.');
        }

        $customer = Customer::find($customerId);

        if (! $customer) {
            session()->forget(['portal_customer_id', 'portal_customer_name', 'portal_last_activity_at']);

            return redirect()->route('portal.login')
                ->with('error', 'Sesion invalida. Por favor inicia sesion nuevamente.');
        }

        $request->session()->put('portal_last_activity_at', now()->timestamp);
        CustomerSession::query()->updateOrCreate(
            ['session_id' => $request->session()->getId()],
            [
                'customer_id' => $customer->id,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_activity_at' => now(),
            ],
        );

        $request->merge(['portal_customer' => $customer]);

        return $next($request);
    }
}
