<?php

namespace Modules\Helpdesk\Http\Controllers\Managers\Compliance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Helpdesk\Services\Compliance\TwoFactorService;

class TwoFactorController extends Controller
{
    public function __construct(private readonly TwoFactorService $twoFactorService) {}

    /**
     * Show the 2FA setup page with QR code.
     */
    public function setup(): View
    {
        return view('helpdesk::compliance.2fa.setup');
    }

    /**
     * Show the 2FA challenge page (session verification).
     */
    public function challenge(): View
    {
        return view('helpdesk::compliance.2fa.challenge');
    }

    /**
     * Generate a new TOTP secret and return QR code + recovery codes.
     */
    public function enable(Request $request): JsonResponse
    {
        $data = $this->twoFactorService->enableForUser($request->user());

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code_url' => $data['qr_code_url'],
                'secret' => $data['secret'],
                'recovery_codes' => $data['recovery_codes'],
            ],
        ]);
    }

    /**
     * Confirm 2FA setup by verifying the first TOTP code from the authenticator.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        $confirmed = $this->twoFactorService->confirmEnable(
            $request->user(),
            $request->input('code')
        );

        if (! $confirmed) {
            return response()->json([
                'success' => false,
                'message' => 'Código inválido. Verifique la hora de su dispositivo e intente nuevamente.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Autenticación de dos factores activada correctamente.',
        ]);
    }

    /**
     * Verify a TOTP code during login and mark the session as 2FA-passed.
     */
    public function verify(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $valid = $this->twoFactorService->verify(
            $request->user(),
            $request->input('code')
        );

        if (! $valid) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código incorrecto o expirado.',
                ], 422);
            }

            return back()->withErrors(['code' => 'Código incorrecto o expirado.']);
        }

        $request->session()->put('2fa_passed', true);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Verificación completada.']);
        }

        $intended = $request->session()->pull('intended', route('manager.helpdesk'));

        return redirect($intended);
    }

    /**
     * Disable 2FA for the authenticated user.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $this->twoFactorService->disableForUser($request->user());

        $request->session()->forget('2fa_passed');

        return response()->json([
            'success' => true,
            'message' => 'Autenticación de dos factores desactivada.',
        ]);
    }
}
