<?php

namespace Modules\Helpdesk\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Services\PortalAuthService;

class PortalAuthController extends Controller
{
    public function __construct(
        private readonly PortalAuthService $authService
    ) {}

    public function loginForm(): View
    {
        return view('helpdesk::portal.login');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electronico es obligatorio.',
            'email.email' => 'Ingresa un correo electronico valido.',
        ]);

        // Always show success to avoid email enumeration
        $this->authService->sendMagicLink($request->email);

        return back()->with('success', 'Si tu correo esta registrado, recibirás un enlace de acceso en breve.');
    }

    public function verify(string $token): RedirectResponse
    {
        $customer = $this->authService->verifyToken($token);

        if (! $customer) {
            return redirect()->route('portal.login')
                ->with('error', 'El enlace es invalido o ha expirado. Solicita uno nuevo.');
        }

        session(['portal_customer_id' => $customer->id]);

        return redirect()->route('helpdesk.portal.conversations')
            ->with('success', '¡Bienvenido, '.$customer->name.'!');
    }

    public function logout(Request $request): RedirectResponse
    {
        session()->forget('portal_customer_id');

        return redirect()->route('portal.login')
            ->with('success', 'Has cerrado sesion correctamente.');
    }
}
