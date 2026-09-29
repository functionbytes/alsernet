<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;
use Modules\Erp\Models\ErpEndpointToken;
use Modules\Erp\Support\ErpEndpointUrlGuard;

class PublicEndpointController extends Controller
{
    /**
     * Call a public endpoint using a token
     */
    public function call(Request $request)
    {
        /** @var ErpEndpointToken $endpointToken */
        $endpointToken = $request->attributes->get('endpoint_token');

        /** @var ErpEndpoint $endpoint */
        $endpoint = $request->attributes->get('endpoint');

        // Defensa en profundidad: este endpoint es invocable sin sesión (solo
        // token), así que un endpoint creado antes del guard de creación (o
        // manipulado directamente en BD) no debe poder usarse como oráculo SSRF.
        if (! ErpEndpointUrlGuard::isAllowed($endpoint->url)) {
            Log::warning('PublicEndpointController: endpoint con URL no permitida bloqueado', [
                'endpoint_id' => $endpoint->id,
            ]);

            return response()->json(['message' => 'Endpoint no disponible'], 422);
        }

        $startTime = microtime(true);

        try {
            // 29-sep-2026: solo se reenvían al destino las cabeceras de una lista
            // blanca (antes iban todas: Cookie, Authorization, X-Forwarded-For…).
            $headers = array_merge(
                $endpoint->headers ?? [],
                $this->forwardableHeaders($request)
            );

            // Build HTTP request
            $http = Http::timeout($endpoint->timeout ?? 30)
                ->withHeaders($headers);

            // Add request query params
            $url = $endpoint->url;
            if (! empty($endpoint->query_params)) {
                $url .= (str_contains($url, '?') ? '&' : '?').http_build_query($endpoint->query_params);
            }

            // Make request based on method
            $response = match ($endpoint->method) {
                'GET' => $http->get($url),
                'POST' => $http->post($url, $request->json()->all() ?? []),
                'PUT' => $http->put($url, $request->json()->all() ?? []),
                'PATCH' => $http->patch($url, $request->json()->all() ?? []),
                'DELETE' => $http->delete($url),
                default => throw new \Exception('Método HTTP no soportado: '.$endpoint->method),
            };

            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            // Log the request
            ErpEndpointLog::create([
                'endpoint_id' => $endpoint->id,
                'token_id' => $endpointToken->id,
                'method' => $endpoint->method,
                'url' => $url,
                'request_headers' => $this->redactHeaders($headers),
                'request_payload' => $request->json()->all() ?? [],
                'response_headers' => $response->headers(),
                'response_payload' => $response->json(),
                'status_code' => $response->status(),
                'execution_time' => $executionTime,
                'success' => $response->successful(),
                'error_message' => $response->failed() ? $response->body() : null,
                'ip_address' => $request->ip(),
            ]);

            // Return response
            return response()->json(
                $response->json(),
                $response->status(),
                $response->headers()
            );

        } catch (\Exception $e) {
            $executionTime = (int) ((microtime(true) - $startTime) * 1000);

            Log::error('Error calling public ERP endpoint', [
                'endpoint_id' => $endpoint->id,
                'token_id' => $endpointToken->id,
                'error' => $e->getMessage(),
            ]);

            // Log the error
            ErpEndpointLog::create([
                'endpoint_id' => $endpoint->id,
                'token_id' => $endpointToken->id,
                'method' => $endpoint->method,
                'url' => $endpoint->url,
                'request_headers' => $this->redactHeaders($this->forwardableHeaders($request)),
                'execution_time' => $executionTime,
                'success' => false,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Error al procesar la solicitud',
            ], 500);
        }
    }

    /**
     * Cabeceras del llamante que se pueden reenviar al endpoint de destino.
     *
     * @return array<string, string>
     */
    private function forwardableHeaders(Request $request): array
    {
        $allowed = ['accept', 'accept-language', 'content-type'];
        $out = [];
        foreach ($allowed as $name) {
            $value = $request->headers->get($name);
            if ($value !== null && $value !== '') {
                $out[$name] = $value;
            }
        }

        return $out;
    }

    /**
     * Quita credenciales antes de guardar las cabeceras en erp_endpoint_logs
     * (las de $endpoint->headers pueden llevar Authorization/API keys).
     *
     * @param  array<string, mixed>  $headers
     * @return array<string, mixed>
     */
    private function redactHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'proxy-authorization', 'cookie', 'set-cookie', 'x-api-key', 'api-key', 'x-auth-token', 'x-erp-token'];
        foreach ($headers as $name => $value) {
            if (in_array(strtolower((string) $name), $sensitive, true)) {
                $headers[$name] = '[redacted]';
            }
        }

        return $headers;
    }
}
