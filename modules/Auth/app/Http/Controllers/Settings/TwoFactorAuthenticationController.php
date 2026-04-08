<?php

namespace Modules\Auth\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Services\TwoFactorService;

class TwoFactorAuthenticationController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor
    ) {}

    /**
     * Get 2FA setup data: secret + QR code SVG.
     */
    public function setup(Request $request): JsonResponse
    {
        $user = $request->user();

        // Reuse existing pending secret if not yet confirmed
        if ($user->two_factor_secret && ! $user->hasTwoFactorEnabled()) {
            $secret = $user->two_factor_secret;
        } else {
            $secret = $this->twoFactor->generateSecret();
            $user->forceFill(['two_factor_secret' => $secret])->save();
        }

        $issuer = config('app.name', 'Alsernet');
        $qrSvg = $this->twoFactor->getQrCodeSvg($secret, $user->email, $issuer);

        return response()->json([
            'secret' => $secret,
            'qr_code' => base64_encode($qrSvg),
        ]);
    }

    /**
     * Confirm and activate 2FA after the user scans the QR and enters a valid code.
     */
    public function confirm(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json(['message' => 'Primero genera un código QR.'], 422);
        }

        if (! $this->twoFactor->verify($user->two_factor_secret, $request->code)) {
            return response()->json(['message' => 'Código incorrecto. Inténtalo de nuevo.'], 422);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $recoveryCodes,
        ])->save();

        return response()->json([
            'message' => 'Autenticación de dos factores activada correctamente.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    /**
     * Disable 2FA after password confirmation.
     */
    public function disable(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return response()->json(['message' => 'Autenticación de dos factores desactivada.']);
    }

    /**
     * Show new recovery codes (requires password confirmation).
     */
    public function regenerateCodes(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Contraseña incorrecta.'], 422);
        }

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json(['message' => '2FA no está activado.'], 422);
        }

        $recoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $recoveryCodes])->save();

        return response()->json([
            'message' => 'Códigos de recuperación regenerados.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }
}
