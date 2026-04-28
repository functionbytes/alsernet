<?php

namespace Modules\Ecommerce\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Bridge between transactional emails and the Flutter mobile app.
 *
 * Emails contain web URLs that hit these endpoints. The endpoints render
 * a minimal HTML page that:
 *  - On mobile (User-Agent or Accept header): redirects to the deep link
 *    `inoqualabapp://...` so the Flutter app can complete the action.
 *  - On desktop browsers: shows a friendly fallback page or redirects to
 *    the legacy web flow (the existing /confirm-email and /reset-password).
 */
class EmailBridgeController extends Controller
{
    public function verifyEmail(Request $request): View
    {
        $token = (string) $request->query('token', '');
        $deepLink = 'inoqualabapp://verify-email?token='.urlencode($token);
        $webFallback = url('/confirm-email').'?'.http_build_query(['token' => $token]);

        return view('ecommerce::email-bridge.redirect', [
            'title' => 'Confirma tu correo',
            'deepLink' => $deepLink,
            'webFallback' => $webFallback,
            'isMobile' => $this->isMobileUserAgent($request),
        ]);
    }

    public function resetPassword(Request $request): View
    {
        $token = (string) $request->query('token', '');
        $email = (string) $request->query('email', '');
        $deepLink = 'inoqualabapp://reset-password?'.http_build_query([
            'token' => $token,
            'email' => $email,
        ]);
        $webFallback = url('/reset-password').'?'.http_build_query([
            'token' => $token,
            'email' => $email,
        ]);

        return view('ecommerce::email-bridge.redirect', [
            'title' => 'Restablece tu contraseña',
            'deepLink' => $deepLink,
            'webFallback' => $webFallback,
            'isMobile' => $this->isMobileUserAgent($request),
        ]);
    }

    private function isMobileUserAgent(Request $request): bool
    {
        $ua = (string) $request->userAgent();

        return (bool) preg_match('/(android|iphone|ipad|ipod|webos|blackberry|windows phone)/i', $ua);
    }
}
