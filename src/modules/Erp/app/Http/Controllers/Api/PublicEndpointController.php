<?php

namespace Modules\Erp\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Erp\Models\ErpEndpoint;
use Modules\Erp\Models\ErpEndpointLog;
use Modules\Erp\Models\ErpEndpointToken;

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

        $startTime = microtime(true);

        try {
            // Merge endpoint headers with request headers
            $headers = array_merge(
                $endpoint->headers ?? [],
                $request->headers->all()
            );

            // Remove hop-by-hop headers
            unset($headers['host']);
            unset($headers['connection']);
            unset($headers['keep-alive']);
            unset($headers['upgrade']);
            unset($headers['transfer-encoding']);

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
                'request_headers' => $request->headers->all(),
                'execution_time' => $executionTime,
                'success' => false,
                'error_message' => $e->getMessage(),
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message' => 'Error al procesar la solicitud',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
