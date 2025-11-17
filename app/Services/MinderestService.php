<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class MinderestService
{
    protected Client $client;
    protected string $urlErp;
    protected string $apiKey;


    public function __construct(?string $apiKey = null)
    {
        $this->urlErp = rtrim(config('services.minderest.url', env('MINDEREST_URL')), '/');
        $this->apiKey = $apiKey ?? config('services.minderest.es');

        $this->client = new Client([
            'base_uri' => $this->urlErp.'/',
            'timeout' => config('services.minderest.timeout', 120),
            'connect_timeout' => config('services.minderest.connect_timeout', 120),
            'http_errors' => false,
            'headers' => [
                'x-api-key' => $apiKey,
            ],
            'curl' => [
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ],
        ]);
    }

    /**
     * Realizar petición GET al ERP
     */
    public function get(string $endpoint, array $params = []): ?array
    {

        try {
            $response = $this->client->get($endpoint, [
                'query' => $params,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("GET {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'GET', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("GET {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }


    /**
     * Realizar petición POST al ERP
     */
    public function post(string $endpoint, array $data = []): ?array
    {
        try {
            $response = $this->client->post($endpoint, [
                'form_params' => $data,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("POST {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'POST', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("POST {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Realizar petición PUT al ERP
     */
    public function put(string $endpoint, array $data = []): ?array
    {
        try {
            $response = $this->client->put($endpoint, [
                'form_params' => $data,
                'headers' => [
                    'Accept' => 'application/xml',
                ],
            ]);

            $status = $response->getStatusCode();
            $body = trim($response->getBody()->getContents());

            Log::info("PUT {$endpoint} -> Raw Response", ['body' => $body]);

            // Manejar respuesta "OK" simple
            if ($status === 200 && $body === 'OK') {
                return [
                    'status' => 'success',
                    'message' => 'Operation completed successfully.'
                ];
            }

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("PUT {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'PUT', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("PUT {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Realizar petición DELETE al ERP
     */
    public function delete(string $endpoint, array $params = []): ?array
    {
        try {
            $response = $this->client->delete($endpoint, [
                'query' => $params,
            ]);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            if ($status === 200 && !empty($body)) {
                return $this->parseXmlResponse($body, $endpoint);
            }

            Log::error("DELETE {$endpoint} -> Respuesta no exitosa", [
                'status' => $status,
                'body' => $body
            ]);
            return null;

        } catch (RequestException $e) {
            $this->logRequestException($e, 'DELETE', $endpoint);
            return null;
        } catch (\Exception $e) {
            Log::error("DELETE {$endpoint} -> Error inesperado: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parsear respuesta XML a array
     */
    private function parseXmlResponse(string $xmlContent, string $endpoint): ?array
    {
        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xmlContent);

        if ($xmlObject === false) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                Log::error("XML Parsing Error", [
                    'endpoint' => $endpoint,
                    'error' => $error->message
                ]);
            }
            libxml_clear_errors();
            return null;
        }

        try {
            $json = json_encode($xmlObject, JSON_THROW_ON_ERROR);
            $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

            Log::info("Datos recibidos de {$endpoint}", ['data' => $data]);
            return $data;
        } catch (\JsonException $e) {
            Log::error("Error al convertir JSON en {$endpoint}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Registrar excepciones de peticiones HTTP
     */
    private function logRequestException(RequestException $e, string $method, string $endpoint): void
    {
        $response = $e->getResponse();
        $errorBody = $response ? $response->getBody()->getContents() : 'No response body';
        $statusCode = $response ? $response->getStatusCode() : 'No status code';

        Log::error("Error HTTP en {$method} {$endpoint}", [
            'message' => $e->getMessage(),
            'status' => $statusCode,
            'body' => $errorBody
        ]);
    }

    public function getData($comparator): ?array
    {
        $url = $comparator->configurationForLang; // o donde tengas la URL

        $code = $url->code;
        $type = $url->type;

        if (!$url) {
            Log::error("No URL definida para el comparador");
            return null;
        }

        $endpoint = $code.'/'.$type;

        dd($endpoint);
        $data = $this->get($endpoint);

        dd($data);
        if (!$data) {

            return null;
        }

        return ['csv' => $response];
    }


    protected function cacheGet(string $key, callable $callback, int $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $callback);
    }



}
