<?php

namespace Modules\Erp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Erp\Models\ErpEndpointToken;
use Symfony\Component\HttpFoundation\Response;

class ValidateEndpointToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->route('token');

        if (! $token || ! is_string($token)) {
            return $this->reject('Token no proporcionado', 401);
        }

        // Indexed lookup; the constant-time check below prevents matching
        // a near-collision on the index alone.
        $endpointToken = ErpEndpointToken::where('token', $token)->first();

        if (! $endpointToken || ! hash_equals($endpointToken->token, $token)) {
            return $this->reject('Token inválido', 401);
        }

        $ip = $request->ip();
        if (! $endpointToken->isValid($ip)) {
            $message = match (true) {
                $endpointToken->isExpired() => 'Token expirado',
                $endpointToken->hasReachedUsageLimit() => 'Límite de uso alcanzado',
                ! $endpointToken->isIpAllowed($ip) => 'IP no autorizada',
                ! $endpointToken->is_active => 'Token desactivado',
                default => 'Token no válido',
            };

            return $this->reject($message, 403);
        }

        $endpoint = $endpointToken->endpoint;
        if (! $endpoint || ! $endpoint->is_active) {
            return $this->reject('Endpoint no disponible', 404);
        }

        $request->attributes->set('endpoint_token', $endpointToken);
        $request->attributes->set('endpoint', $endpoint);

        $response = $next($request);

        if ($response->getStatusCode() < 400) {
            $endpointToken->recordUsage();
        }

        return $response;
    }

    private function reject(string $message, int $status): Response
    {
        return response()->json(['message' => $message], $status);
    }
}
