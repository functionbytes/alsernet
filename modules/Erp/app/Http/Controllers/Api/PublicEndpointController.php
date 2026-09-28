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
use Modules\Erp\Support\ErpErrorSanitizer;

class PublicEndpointController extends Controller
{
    /**
     * Cabeceras entrantes que nunca se reenvían al endpoint remoto.
     */
    private const STRIPPED_HEADERS = [
        'host', 'connection', 'keep-alive', 'upgrade', 'transfer-encoding', 'content-length',
        'authorization', 'x-erp-token', 'cookie', 'x-csrf-token', 'x-xsrf-token',
        'x-forwarded-for', 'x-forwarded-host', 'x-forwarded-proto', 'x-real-ip',
    ];

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
            // Cabeceras del cliente sin las de hop-by-hop ni las de sesión/auth:
            // las credenciales del llamante (su token ERP, cookies) no deben
            // llegar al upstream. Las del endpoint van después para que el
            // cliente no pueda sobrescribir la auth configurada.
            // Symfony entrega las cabeceras del cliente en minúsculas: se
            // comparan igual las del endpoint para que no viajen duplicadas.
            $endpointHeaders = $endpoint->headers ?? [];
            $clientHeaders = array_diff_key(
                $request->headers->all(),
                array_flip(self::STRIPPED_HEADERS),
                array_change_key_case($endpointHeaders, CASE_LOWER)
            );
            $headers = array_merge($clientHeaders, $endpointHeaders);

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
                'request_headers' => $headers,
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
                'request_headers' => $headers ?? [],
                'execution_time' => $executionTime,
                'success' => false,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => ErpErrorSanitizer::forClient($e),
            ], 500);
        }
    }
}
