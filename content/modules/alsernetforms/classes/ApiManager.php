<?php

include_once(dirname(__FILE__).'/loggers/SubscriptionEndpointLogger.php');
include_once(dirname(__FILE__).'/loggers/FormEndpointLogger.php');
include_once(dirname(__FILE__).'/loggers/DefaultEndpointLogger.php');

class ApiManager
{
    private $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = 'https://webadminpruebas.a-alvarez.com/';
    }

    public function sendRequest($method, $endpoint, array $data = [], $type = 'default', array $headers = [])
    {
        $url = rtrim($this->apiBaseUrl, '/') . '/' . ltrim($endpoint, '/');
        $logger = $this->getLoggerForType($type);
        $requestLog = $logger->logRequest($method, $url, $data);

        $ch = curl_init();
        $defaultHeaders = ['Content-Type: application/json'];
        $headers = array_merge($defaultHeaders, $headers);
        $this->configureCurl($ch, $method, $url, $data, $headers);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        if ($curlError) {
            curl_close($ch);
            $logger->updateRequestLog($requestLog, 'failed', ['error' => $curlError]);
            return ['status' => 'error', 'message' => $this->translate('Error connecting to the server')];
        }

        curl_close($ch);
        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $logger->updateRequestLog($requestLog, 'failed', ['error' => 'Invalid JSON response']);
            return ['status' => 'error', 'message' => $this->translate('Invalid JSON response')];
        }

        $logger->updateRequestLog($requestLog, $httpCode === 200 ? 'success' : 'failed', $responseData);

        return [
            'response' => $responseData,
        ];
    }

    private function configureCurl($ch, $method, &$url, array $data, array $headers)
    {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        switch (strtoupper($method)) {
            case 'GET':
                $url .= '?' . http_build_query($data);
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case 'POST':
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_URL, $url);
    }

    private function getLoggerForType($type)
    {
        switch ($type) {
            case 'form':
                return new FormEndpointLogger();
            case 'subscription':
                return new SubscriptionEndpointLogger();
            default:
                return new DefaultEndpointLogger();
        }
    }

    private function translate($message)
    {
        return Context::getContext()->getTranslator()->trans($message, [], 'Modules.Tumodulo');
    }


}