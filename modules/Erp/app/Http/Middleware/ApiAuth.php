<?php

namespace Modules\Erp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Core\Models\Setting;
use Modules\Erp\Models\ErpEndpointToken;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Auth gate for /api/erp/* (customer, products, suppliers, families...).
 *
 * 29-sep-2026 (auditoría C3):
 *  - Fail-closed: si `erp_api_auth_enabled` no existe, la autenticación está
 *    ACTIVA. Hoy hay una fila explícita 'no' en settings porque ningún
 *    consumidor (tienda, Supplier sync, HelpdeskErp, HelpdeskBirthday) envía
 *    credencial todavía.
 *  - Con la autenticación desactivada solo pasan las IPs de
 *    config('erp.api.allowed_ips') (el vhost ya no filtra por IP).
 *  - Scope `write` (POST customer, PATCH lopd, DELETE .../cache): exige SIEMPRE
 *    un ErpEndpointToken del endpoint de escritura, esté o no activa la
 *    autenticación de lectura.
 *  - Los tokens aceptados deben pertenecer al ErpEndpoint cuyo slug indica
 *    config('erp.api.token_endpoints'): un token de otro endpoint público no
 *    abre la API de clientes.
 */
class ApiAuth
{
    public function handle(Request $request, Closure $next, string $scope = 'read'): Response
    {
        if ($scope === 'write') {
            if (! $this->tryErpToken($request, 'write')) {
                return $this->deny($request, 'write-token', 401);
            }

            return $next($request);
        }

        $settings = Setting::getErpSettings();

        if (($settings['erp_api_auth_enabled'] ?? 'yes') === 'no') {
            if (! $this->ipAllowed($request->ip())) {
                return $this->deny($request, 'ip', 403);
            }

            return $next($request);
        }

        $guard = (string) ($settings['erp_api_auth_guard'] ?? 'sanctum');

        $passed = match ($guard) {
            'sanctum' => $this->trySanctum($request),
            'erp_token' => $this->tryErpToken($request, 'read'),
            'both' => $this->trySanctum($request) || $this->tryErpToken($request, 'read'),
            default => false,
        };

        if (! $passed) {
            return $this->deny($request, 'auth', 401);
        }

        return $next($request);
    }

    private function ipAllowed(?string $ip): bool
    {
        $allowed = config('erp.api.allowed_ips', []);
        if (is_string($allowed)) {
            $allowed = explode(',', $allowed);
        }
        $allowed = array_values(array_filter(array_map('trim', (array) $allowed)));

        return $ip !== null && $allowed !== [] && IpUtils::checkIp($ip, $allowed);
    }

    private function deny(Request $request, string $reason, int $status): Response
    {
        Log::warning('ERP API: acceso denegado', [
            'reason' => $reason,
            'ip' => $request->ip(),
            'method' => $request->method(),
            'path' => $request->path(),
        ]);

        return response()->json([
            'message' => $status === 403 ? 'Forbidden.' : 'Unauthenticated.',
        ], $status);
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

    private function tryErpToken(Request $request, string $scope): bool
    {
        $token = $request->header('X-Erp-Token')
            ?? $request->bearerToken();

        if (! is_string($token) || $token === '') {
            return false;
        }

        $record = ErpEndpointToken::with('endpoint')
            ->where('token', $token)
            ->where('is_active', true)
            ->first();

        if (! $record || ! hash_equals((string) $record->token, $token) || ! $record->isValid($request->ip())) {
            return false;
        }

        $requiredSlug = (string) config('erp.api.token_endpoints.'.$scope, '');
        if ($requiredSlug === '' || ($record->endpoint?->slug ?? null) !== $requiredSlug) {
            return false;
        }

        $request->attributes->set('endpoint_token', $record);
        $record->recordUsage();

        return true;
    }
}
