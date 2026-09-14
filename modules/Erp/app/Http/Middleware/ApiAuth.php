<?php

namespace Modules\Erp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Models\Setting;
use Modules\Erp\Models\ErpEndpointToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth gate for /api/erp/* (customer, products, suppliers, families...).
 *
 * Reads `erp_api_auth_enabled` and `erp_api_auth_guard` from the Settings
 * table (bundle-cached in Redis). When disabled, requests pass through
 * unchanged — the deployment is expected to be firewalled. When enabled,
 * the configured guard (or both, in cascade) decides whether the request
 * is authenticated.
 */
class ApiAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::getErpSettings();

        if (($settings['erp_api_auth_enabled'] ?? 'no') !== 'yes') {
            return $next($request);
        }

        $guard = (string) ($settings['erp_api_auth_guard'] ?? 'sanctum');

        $passed = match ($guard) {
            'sanctum' => $this->trySanctum($request),
            'erp_token' => $this->tryErpToken($request),
            'both' => $this->trySanctum($request) || $this->tryErpToken($request),
            default => false,
        };

        if (! $passed) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return $next($request);
    }

    private function trySanctum(Request $request): bool
    {
        try {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                Auth::setUser($user);

                return true;
            }
        } catch (\Throwable) {
            // sanctum not bootable, fall through to false
        }

        return false;
    }

    private function tryErpToken(Request $request): bool
    {
        $token = $request->header('X-Erp-Token')
            ?? $request->bearerToken();

        if (! $token) {
            return false;
        }

        $record = ErpEndpointToken::where('token', $token)
            ->where('is_active', true)
            ->first();

        if (! $record || ! hash_equals($record->token, $token) || ! $record->isValid($request->ip())) {
            return false;
        }

        $request->attributes->set('endpoint_token', $record);
        $record->recordUsage();

        return true;
    }
}
