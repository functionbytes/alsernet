<?php

include_once dirname(__FILE__).'/../ApiManager.php';
include_once dirname(__FILE__).'/../EndpointAvailabilityChecker.php';

abstract class BaseAction
{
    protected $apiManager;

    protected $logger;  // Logger específico de esta Action

    protected $availabilityChecker;

    protected $endpoint;  // Ej: 'api/documents', 'api/forms', 'api/chat'

    protected $actionType;  // Ej: 'documents', 'forms', 'chat' - usado para health check

    public function __construct()
    {
        $this->apiManager = new ApiManager;
        $this->availabilityChecker = new EndpointAvailabilityChecker;
        $this->logger = $this->createLogger();  // Factory Method
    }

    abstract protected function createLogger();

    final protected function execute(array $payload, array $context = [])
    {
        // 1. Construir URL completa
        $url = rtrim($this->apiManager->getBaseUrl(), '/').'/'.ltrim($this->endpoint, '/');

        // 2. Registrar petición inicial en BD (tracking start)
        $requestId = $this->logger->logRequest('POST', $url, $payload);

        // 3. Verificar disponibilidad del servidor (Circuit Breaker Pattern)
        $availability = $this->checkAvailability($url);

        if (! $availability['available']) {
            // 4. Servidor NO disponible: marcar como pending y retornar inmediatamente
            if (method_exists($this->logger, 'markAsServerUnavailable')) {
                $this->logger->markAsServerUnavailable(
                    $requestId,
                    $availability['reason'],
                    $availability['next_retry_at'] ?? null
                );
            } else {
                $this->logger->updateRequestLog($requestId, 'server_unavailable', [
                    'error' => $availability['reason'],
                ]);
            }

            // Retornar respuesta pending (será procesada por cron cuando servidor vuelva)
            return $this->mapResponse([
                'status' => 'pending',
                'message' => 'Server unavailable. Request queued for later processing.',
                'request_id' => $requestId,
                'reason' => $availability['reason'],
                'response' => [],
            ]);
        }

        // 5. Servidor disponible: enviar HTTP request (SIN logging interno)
        $httpResponse = $this->apiManager->sendRequestWithoutLogging(
            'POST',
            $this->endpoint,
            $payload,
            []  // headers
        );

        // 6. Actualizar log con resultado final (tracking end)
        $this->logger->updateRequestLog(
            $requestId,
            $httpResponse['status'] === 200 ? 'success' : 'failed',
            $httpResponse['response'] ?? []
        );

        // 7. Preparar respuesta completa para mapeo
        $finalResponse = array_merge($httpResponse, [
            'request_id' => $requestId,
        ]);

        // Mapear respuesta según el tipo de acción (cada subclase define su mapeo)
        return $this->mapResponse($finalResponse);
    }


    private function checkAvailability($url)
    {
        return $this->availabilityChecker->isEndpointAvailable($url, $this->actionType);
    }

    abstract protected function mapResponse(array $response);
}
