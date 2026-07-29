<?php

include_once dirname(__FILE__).'/loggers/DefaultEndpointLogger.php';
include_once dirname(__FILE__).'/loggers/DocumentsEndpointLogger.php';
include_once dirname(__FILE__).'/EndpointAvailabilityChecker.php';
include_once dirname(__FILE__).'/HttpClient.php';

class ApiManager
{
    private $apiBaseUrl;

    private $availabilityChecker;

    private $httpClient;

    private $checkAvailability = true;

    public function __construct()
    {
        $this->apiBaseUrl = 'https://webadminpruebas.a-alvarez.com/';
        $this->availabilityChecker = new EndpointAvailabilityChecker;
        $this->httpClient = new HttpClient;
    }

    public function getBaseUrl()
    {
        return $this->apiBaseUrl;
    }

    public function sendRequestWithoutLogging($method, $endpoint, array $data = [], array $headers = [])
    {
        $url = rtrim($this->apiBaseUrl, '/').'/'.ltrim($endpoint, '/');

        // Enviar petición HTTP usando HttpClient centralizado
        $httpResponse = $this->httpClient->request($method, $url, $data, $headers);

        $httpCode = $httpResponse['status'];
        $curlError = $httpResponse['error'];

        if ($curlError) {
            return [
                'status' => 0,
                'message' => $this->translate('Error connecting to the server'),
                'response' => [],
                'error' => $curlError,
            ];
        }

        $responseData = $this->httpClient->decodeJson($httpResponse['body']);

        if ($responseData === null && ! empty($httpResponse['body'])) {
            return [
                'status' => 0,
                'message' => $this->translate('Invalid JSON response'),
                'response' => [],
                'error' => 'Invalid JSON response',
            ];
        }

        return [
            'status' => $httpCode,
            'message' => $httpCode === 200 ? 'Request successful' : 'Request failed',
            'response' => $responseData,
            'error' => null,
        ];
    }

    public function sendRequest($method, $endpoint, array $data = [], $type = 'default', array $headers = [], $checkAvailability = true)
    {
        $url = rtrim($this->apiBaseUrl, '/').'/'.ltrim($endpoint, '/');
        $logger = $this->getLoggerForType($type);
        $requestLog = $logger->logRequest($method, $url, $data);

        // Verificar disponibilidad del endpoint si está habilitado
        if ($checkAvailability && $this->checkAvailability) {
            $availability = $this->availabilityChecker->isEndpointAvailable($url, $type);

            if (! $availability['available']) {
                // Servidor no disponible, marcar como pendiente
                if (method_exists($logger, 'markAsServerUnavailable')) {
                    $logger->markAsServerUnavailable(
                        $requestLog,
                        $availability['reason'],
                        $availability['next_retry_at'] ?? null
                    );
                } else {
                    $logger->updateRequestLog($requestLog, 'server_unavailable', [
                        'error' => $availability['reason'],
                    ]);
                }

                return [
                    'status' => 'pending',
                    'message' => $this->translate('Server unavailable. Request queued for later processing.'),
                    'reason' => $availability['reason'],
                    'request_id' => $requestLog,
                ];
            }
        }

        // Servidor disponible, proceder con la petición usando HttpClient centralizado
        $httpResponse = $this->httpClient->request($method, $url, $data, $headers);

        $httpCode = $httpResponse['status'];
        $curlError = $httpResponse['error'];

        if ($curlError) {
            $logger->updateRequestLog($requestLog, 'failed', ['error' => $curlError]);

            return ['status' => 'error', 'message' => $this->translate('Error connecting to the server')];
        }

        $responseData = $this->httpClient->decodeJson($httpResponse['body']);

        if ($responseData === null && ! empty($httpResponse['body'])) {
            $logger->updateRequestLog($requestLog, 'failed', ['error' => 'Invalid JSON response']);

            return ['status' => 'error', 'message' => $this->translate('Invalid JSON response')];
        }

        $logger->updateRequestLog($requestLog, $httpCode === 200 ? 'success' : 'failed', $responseData);

        return [
            'status' => $httpCode === 200 ? 'success' : 'error',
            'message' => $httpCode === 200 ? 'Request successful' : 'Request failed',
            'response' => $responseData,
        ];
    }

    private function getLoggerForType($type)
    {

        if ($type === 'documents') {
            return new DocumentsEndpointLogger;
        }

        return new DefaultEndpointLogger($type);
    }

    private function translate($message)
    {
        return Context::getContext()->getTranslator()->trans($message, [], 'modules.Tumodulo');
    }
}
