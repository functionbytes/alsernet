<?php

include_once dirname(__FILE__).'/BaseAction.php';
include_once dirname(__FILE__).'/../loggers/DocumentsEndpointLogger.php';

class DocumentAction extends BaseAction
{
    public function __construct()
    {
        parent::__construct();

        $this->endpoint = 'api/documents';
        $this->actionType = 'documents';
    }

    protected function createLogger()
    {
        return new DocumentsEndpointLogger;
    }

    public function requestDocument(array $orderData, array $context = [])
    {

        $payload = $orderData;

        return $this->execute($payload, $context);
    }

    public function validate($uid, array $context = [])
    {
        // Defensa en profundidad: sin uid no hay nada que validar contra Laravel.
        // Sin este guard, un uid vacio se concatenaba igual en la URL
        // (".../api/documents//validation", doble barra) que Laravel rechaza con
        // 404 de ruta, y encima quedaba logueado como si fuera un intento real.
        if (empty($uid)) {
            return [
                'status' => 'error',
                'request_id' => null,
                'data' => [],
                'message' => 'Missing uid',
            ];
        }

        $url = rtrim($this->apiManager->getBaseUrl(), '/').'/'.ltrim($this->endpoint, '/').'/'.$uid.'/validation';

        $requestId = $this->logger->logRequest('GET', $url, $context);

        $availability = $this->checkAvailability($url);

        if (! $availability['available']) {

            if (method_exists($this->logger, 'markAsServerUnavailable')) {
                $this->logger->markAsServerUnavailable(
                    $requestId,
                    $availability['reason'],
                    $availability['next_retry_at'] ?? null
                );
            }

            return $this->mapResponse([
                'status' => 'pending',
                'message' => 'Server unavailable. Request queued.',
                'request_id' => $requestId,
                'reason' => $availability['reason'],
                'response' => [],
            ]);
        }

        $httpResponse = $this->apiManager->sendRequestWithoutLogging(
            'GET',
            $this->endpoint.'/'.$uid.'/validation',
            [],  // Sin payload para GET
            []   // headers
        );

        $this->logger->updateRequestLog(
            $requestId,
            $httpResponse['status'] === 200 ? 'success' : 'failed',
            $httpResponse['response'] ?? []
        );

        $finalResponse = array_merge($httpResponse, [
            'request_id' => $requestId,
        ]);

        return $this->mapResponse($finalResponse);
    }

    public function validateToken($token, array $context = [])
    {
        // Defensa en profundidad: ver el mismo guard en validate() de arriba.
        // alsernetforms.php ya corta antes de llegar aqui, pero cualquier otro
        // caller de este metodo queda igual protegido.
        if (empty($token)) {
            return [
                'status' => 'error',
                'request_id' => null,
                'data' => [],
                'message' => 'Missing token',
            ];
        }

        $url = rtrim($this->apiManager->getBaseUrl(), '/').'/'.ltrim($this->endpoint, '/').'/'.$token.'/validation';

        $requestId = $this->logger->logRequest('GET', $url, $context);

        $availability = $this->checkAvailability($url);

        if (! $availability['available']) {
            if (method_exists($this->logger, 'markAsServerUnavailable')) {
                $this->logger->markAsServerUnavailable(
                    $requestId,
                    $availability['reason'],
                    $availability['next_retry_at'] ?? null
                );
            }

            return $this->mapResponse([
                'status' => 'pending',
                'message' => 'Server unavailable. Request queued.',
                'request_id' => $requestId,
                'reason' => $availability['reason'],
                'response' => [],
            ]);
        }

        $httpResponse = $this->apiManager->sendRequestWithoutLogging(
            'GET',
            $this->endpoint.'/'.$token.'/validation',
            [],  // Sin payload para GET
            []   // headers
        );

        $this->logger->updateRequestLog(
            $requestId,
            $httpResponse['status'] === 200 ? 'success' : 'failed',
            $httpResponse['response'] ?? []
        );

        $finalResponse = array_merge($httpResponse, [
            'request_id' => $requestId,
        ]);

        return $this->mapResponse($finalResponse);
    }

    public function verifyByOrderId($orderId, array $context = [])
    {

        $url = rtrim($this->apiManager->getBaseUrl(), '/').'/'.ltrim($this->endpoint, '/').'/verify?order_id='.$orderId;

        $requestId = $this->logger->logRequest('GET', $url, $context);

        $availability = $this->checkAvailability($url);

        if (! $availability['available']) {
            if (method_exists($this->logger, 'markAsServerUnavailable')) {
                $this->logger->markAsServerUnavailable(
                    $requestId,
                    $availability['reason'],
                    $availability['next_retry_at'] ?? null
                );
            }

            return $this->mapResponse([
                'status' => 'pending',
                'message' => 'Server unavailable. Request queued.',
                'request_id' => $requestId,
                'reason' => $availability['reason'],
                'response' => [],
            ]);
        }

        $httpResponse = $this->apiManager->sendRequestWithoutLogging(
            'GET',
            $this->endpoint.'/verify?order_id='.$orderId,
            [],  // Sin payload para GET
            []   // headers
        );

        // Actualizar tracking
        $this->logger->updateRequestLog(
            $requestId,
            $httpResponse['status'] === 200 ? 'success' : 'failed',
            $httpResponse['response'] ?? []
        );

        // Preparar respuesta completa
        $finalResponse = array_merge($httpResponse, [
            'request_id' => $requestId,
        ]);

        return $this->mapResponse($finalResponse);
    }

    private function checkAvailability($url)
    {
        return $this->availabilityChecker->isEndpointAvailable($url, $this->actionType);
    }

    protected function mapResponse(array $response)
    {
        $responseData = $response['response'] ?? [];
        $httpStatus = $response['status'] ?? 500;

        if ($httpStatus >= 200 && $httpStatus < 300) {
            $status = $responseData['status'] ?? 'success';
        } else {
            $status = $responseData['status'] ?? 'failed';
        }

        if (isset($response['status']) && $response['status'] === 'pending') {
            $status = 'pending';
        }

        return [
            'status' => $status,
            'request_id' => $response['request_id'] ?? null,
            'data' => [
                'uid' => $responseData['data']['uid'] ?? null,
                'document_type' => $responseData['data']['type'] ?? 'dni',
                'order_id' => $responseData['data']['order_id'] ?? null,
                'reference' => $responseData['data']['reference'] ?? null,
                'label' => $responseData['data']['label'] ?? 'N/A',
                'can_upload' => $responseData['data']['can_upload'] ?? false,
                'required_documents' => $responseData['data']['required_documents'] ?? [],
                'uploaded_documents' => $responseData['data']['uploaded_documents'] ?? [],
                'missing_documents' => $responseData['data']['missing_documents'] ?? [],
            ],
            'message' => $responseData['message'] ?? $response['message'] ?? null,
        ];
    }
}
