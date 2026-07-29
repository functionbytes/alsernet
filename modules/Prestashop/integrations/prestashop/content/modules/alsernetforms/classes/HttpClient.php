<?php

/**
 * HttpClient
 *
 * Cliente HTTP centralizado para todas las peticiones curl.
 * Maneja GET, POST, PUT, DELETE, PATCH con una interfaz consistente.
 *
 * Evita duplicación de código curl en:
 * - EndpointAvailabilityChecker
 * - ApiManager
 * - Actions
 * - Loggers
 */
class HttpClient
{
    private $timeout = 10;

    private $connectTimeout = 10;

    private $sslVerify = false;

    private $maxRedirects = 3;

    public function __construct($timeout = 10, $connectTimeout = 10, $sslVerify = false)
    {
        $this->timeout = $timeout;
        $this->connectTimeout = $connectTimeout;
        $this->sslVerify = $sslVerify;
    }

    /**
     * Realiza una petición HTTP
     *
     * @param  string  $method  HTTP method (GET, POST, PUT, DELETE, PATCH)
     * @param  string  $url  URL completa
     * @param  array  $data  Datos a enviar (body para POST/PUT/PATCH, query para GET)
     * @param  array  $headers  Headers HTTP adicionales
     * @return array ['status' => int, 'body' => string, 'error' => string|null]
     */
    public function request($method, $url, array $data = [], array $headers = [])
    {
        $ch = curl_init();

        try {
            // Configuración base
            $this->configureBase($ch);

            // Configurar según el método
            $this->configureByMethod($ch, $method, $url, $data, $headers);

            // Ejecutar
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);

            curl_close($ch);

            if ($curlError) {
                return [
                    'status' => 0,
                    'body' => null,
                    'error' => $curlError,
                ];
            }

            return [
                'status' => $httpCode,
                'body' => $response,
                'error' => null,
            ];
        } catch (\Exception $e) {
            curl_close($ch);

            return [
                'status' => 0,
                'body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * GET request
     *
     * @param  string  $url
     * @return array
     */
    public function get($url, array $queryParams = [], array $headers = [])
    {
        if (! empty($queryParams)) {
            $url .= '?'.http_build_query($queryParams);
        }

        return $this->request('GET', $url, [], $headers);
    }

    /**
     * POST request
     *
     * @param  string  $url
     * @return array
     */
    public function post($url, array $data = [], array $headers = [])
    {
        return $this->request('POST', $url, $data, $headers);
    }

    /**
     * PUT request
     *
     * @param  string  $url
     * @return array
     */
    public function put($url, array $data = [], array $headers = [])
    {
        return $this->request('PUT', $url, $data, $headers);
    }

    /**
     * DELETE request
     *
     * @param  string  $url
     * @return array
     */
    public function delete($url, array $data = [], array $headers = [])
    {
        return $this->request('DELETE', $url, $data, $headers);
    }

    /**
     * PATCH request
     *
     * @param  string  $url
     * @return array
     */
    public function patch($url, array $data = [], array $headers = [])
    {
        return $this->request('PATCH', $url, $data, $headers);
    }

    /**
     * Decodifica una respuesta JSON
     *
     * @param  string  $jsonBody
     * @return array|null
     */
    public function decodeJson($jsonBody)
    {
        if (empty($jsonBody)) {
            return null;
        }

        $decoded = json_decode($jsonBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded;
    }

    /**
     * Configura opciones base de curl
     *
     * @param  resource  $ch
     */
    private function configureBase(&$ch)
    {
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => $this->sslVerify,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => $this->maxRedirects,
        ]);
    }

    /**
     * Configura curl según el método HTTP
     *
     * @param  resource  $ch
     * @param  string  $method
     * @param  string  $url
     */
    private function configureByMethod(&$ch, $method, &$url, array $data, array $headers)
    {
        // Headers por defecto
        $defaultHeaders = ['Accept: application/json'];

        // Si enviamos datos, asegurarse que Content-Type esté definido
        if (! empty($data) && ! in_array('Content-Type: application/json', $headers)) {
            $defaultHeaders[] = 'Content-Type: application/json';
        }

        $allHeaders = array_merge($defaultHeaders, $headers);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $allHeaders);
        curl_setopt($ch, CURLOPT_URL, $url);

        switch (strtoupper($method)) {
            case 'GET':
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;

            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                if (! empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'PUT':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
                if (! empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                if (! empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            case 'PATCH':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
                if (! empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
                break;

            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                if (! empty($data)) {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                }
        }
    }
}
