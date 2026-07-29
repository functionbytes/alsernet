<?php

include_once(dirname(__FILE__).'/EndpointAvailabilityChecker.php');
include_once(dirname(__FILE__).'/ApiManager.php');
include_once(dirname(__FILE__).'/loggers/DefaultEndpointLogger.php');
include_once(dirname(__FILE__).'/loggers/DocumentsEndpointLogger.php');
// Removed: FormEndpointLogger.php y SubscriptionEndpointLogger.php (deleted files)

/**
 * PendingRequestsProcessor
 *
 * Procesa peticiones pendientes que no pudieron completarse debido a
 * indisponibilidad del servidor o errores temporales.
 *
 * REFACTORIZADO: Ahora usa DefaultEndpointLogger con tipo como parámetro
 * en lugar de loggers específicos para cada tipo.
 */
class PendingRequestsProcessor
{
    private $db;
    private $availabilityChecker;
    private $apiManager;
    private $batchSize = 50;
    private $maxExecutionTime = 300; // 5 minutos
    private $startTime;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->availabilityChecker = new EndpointAvailabilityChecker();
        $this->apiManager = new ApiManager();
        $this->startTime = time();
    }


    public function process()
    {
        $stats = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Obtener peticiones pendientes agrupadas por tipo
        $pendingRequests = $this->getPendingRequestsByType();

        foreach ($pendingRequests as $type => $requests) {
            if ($this->shouldStop()) {
                break;
            }

            $typeStats = $this->processRequestsByType($type, $requests);
            $stats['processed'] += $typeStats['processed'];
            $stats['successful'] += $typeStats['successful'];
            $stats['failed'] += $typeStats['failed'];
            $stats['skipped'] += $typeStats['skipped'];
            $stats['errors'] = array_merge($stats['errors'], $typeStats['errors']);
        }

        return $stats;
    }

    /**
     * Procesa peticiones de un tipo específico
     *
     * @param string $type Tipo de endpoint
     * @param array $requests Lista de peticiones
     * @return array Estadísticas
     */
    private function processRequestsByType($type, array $requests)
    {
        $stats = [
            'processed' => 0,
            'successful' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        // Obtener el logger apropiado para este tipo
        $logger = $this->getLoggerForType($type);

        foreach ($requests as $request) {
            if ($this->shouldStop()) {
                break;
            }

            try {
                $result = $this->processRequest($request, $logger);
                $stats['processed']++;

                if ($result['status'] === 'success') {
                    $stats['successful']++;
                } elseif ($result['status'] === 'failed') {
                    $stats['failed']++;
                } elseif ($result['status'] === 'skipped') {
                    $stats['skipped']++;
                }
            } catch (Exception $e) {
                $stats['errors'][] = [
                    'request_id' => $request['id_alsernetforms_request'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return $stats;
    }

    /**
     * Procesa una petición individual
     *
     * @param array $request Datos de la petición
     * @param object $logger Logger a utilizar
     * @return array Resultado del procesamiento
     */
    private function processRequest(array $request, $logger)
    {
        $requestId = $request['id_alsernetforms_request'];
        $url = $request['url'];
        $type = $request['endpoint_type'];

        // Verificar si el endpoint está disponible
        $availability = $this->availabilityChecker->isEndpointAvailable($url, $type);

        if (!$availability['available']) {
            // Servidor aún no disponible, actualizar next_retry_at
            $nextRetry = $availability['next_retry_at'] ?? date('Y-m-d H:i:s', time() + 300);
            $logger->markAsServerUnavailable($requestId, $availability['reason'], $nextRetry);
            $logger->incrementRetryCount($requestId, $nextRetry);

            return ['status' => 'skipped', 'reason' => 'server_unavailable'];
        }

        // El servidor está disponible, intentar procesar la petición
        try {
            $payload = json_decode($request['payload'], true);
            $method = $request['method'];

            // Realizar la petición (sin logging interno - el logger ya lo maneja)
            $response = $this->apiManager->sendRequestWithoutLogging(
                $method,
                $url,
                $payload,
                [] // headers
            );

            // Verificar si la petición fue exitosa
            if ($response['status'] === 200 || (isset($response['response']['status']) && $response['response']['status'] === 'success')) {
                $logger->updateRequestLog($requestId, 'success', $response['response'] ?? []);
                return ['status' => 'success'];
            } else {
                // La petición falló, pero el servidor está disponible
                $logger->incrementRetryCount($requestId);

                if ($request['retry_count'] >= $request['max_retries'] - 1) {
                    // Se alcanzó el máximo de reintentos
                    $logger->updateRequestLog($requestId, 'failed', $response['response'] ?? []);
                    return ['status' => 'failed', 'reason' => 'max_retries_reached'];
                } else {
                    // Programar próximo reintento
                    $nextRetry = $this->calculateNextRetry($request['retry_count'] + 1);
                    $logger->updateRequestLog($requestId, 'pending', $response['response'] ?? []);
                    $this->db->update(
                        'alsernet_forms_requests',
                        ['next_retry_at' => pSQL($nextRetry)],
                        'id_alsernetforms_request = ' . (int)$requestId
                    );
                    return ['status' => 'failed', 'reason' => 'will_retry'];
                }
            }
        } catch (Exception $e) {
            // Error al procesar, incrementar contador y programar reintento
            $logger->incrementRetryCount($requestId);
            $nextRetry = $this->calculateNextRetry($request['retry_count'] + 1);

            $this->db->update(
                'alsernet_forms_requests',
                [
                    'last_error' => pSQL($e->getMessage()),
                    'next_retry_at' => pSQL($nextRetry)
                ],
                'id_alsernetforms_request = ' . (int)$requestId
            );

            return ['status' => 'failed', 'reason' => 'exception', 'error' => $e->getMessage()];
        }
    }

    /**
     * Calcula la fecha/hora del próximo reintento usando backoff exponencial
     *
     * @param int $retryCount Número de reintentos realizados
     * @return string Fecha/hora del próximo reintento
     */
    private function calculateNextRetry($retryCount)
    {
        // Backoff exponencial: 1min, 5min, 15min, 30min, 60min
        $delays = [60, 300, 900, 1800, 3600];
        $delayIndex = min($retryCount - 1, count($delays) - 1);
        $delay = $delays[$delayIndex];

        return date('Y-m-d H:i:s', time() + $delay);
    }

    /**
     * Obtiene peticiones pendientes agrupadas por tipo
     *
     * @return array Peticiones agrupadas por tipo
     */
    private function getPendingRequestsByType()
    {
        $sql = 'SELECT *
                FROM ' . _DB_PREFIX_ . 'alsernet_forms_requests
                WHERE status IN ("pending", "server_unavailable")
                AND retry_count < max_retries
                AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                ORDER BY created_at ASC
                LIMIT ' . (int)$this->batchSize;

        $requests = $this->db->executeS($sql);
        $grouped = [];

        foreach ($requests as $request) {
            $type = $request['endpoint_type'];
            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
            }
            $grouped[$type][] = $request;
        }

        return $grouped;
    }

    /**
     * Obtiene el logger apropiado para un tipo de petición
     *
     * REFACTORIZADO: Ahora usa DefaultEndpointLogger con tipo como parámetro
     * para subscription y form. Solo documents tiene logger específico.
     *
     * @param string $type Tipo de endpoint
     * @return object Logger instance
     */
    private function getLoggerForType($type)
    {
        // DocumentsEndpointLogger tiene lógica específica (circuit breaker, retries, stats)
        if ($type === 'documents') {
            return new DocumentsEndpointLogger();
        }

        // Para todos los demás tipos, usar DefaultEndpointLogger con tipo como parámetro
        return new DefaultEndpointLogger($type);
    }

    /**
     * Verifica si debe detenerse el procesamiento
     *
     * @return bool
     */
    private function shouldStop()
    {
        return (time() - $this->startTime) >= $this->maxExecutionTime;
    }

    /**
     * Obtiene estadísticas generales de peticiones pendientes
     *
     * @return array
     */
    public function getPendingStats()
    {
        $sql = 'SELECT
                    endpoint_type,
                    status,
                    COUNT(*) as count,
                    MIN(created_at) as oldest,
                    MAX(retry_count) as max_retries
                FROM ' . _DB_PREFIX_ . 'alsernet_forms_requests
                WHERE status IN ("pending", "server_unavailable")
                AND retry_count < max_retries
                GROUP BY endpoint_type, status';

        return $this->db->executeS($sql);
    }

    /**
     * Limpia peticiones antiguas que ya no son relevantes
     *
     * @param int $daysOld Días de antigüedad para considerar una petición como antigua
     * @return int Número de peticiones eliminadas
     */
    public function cleanupOldRequests($daysOld = 30)
    {
        $sql = 'DELETE FROM ' . _DB_PREFIX_ . 'alsernet_forms_requests
                WHERE (
                    (status = "success" AND synced_at < DATE_SUB(NOW(), INTERVAL ' . (int)$daysOld . ' DAY))
                    OR
                    (status IN ("failed", "server_unavailable") AND retry_count >= max_retries
                     AND created_at < DATE_SUB(NOW(), INTERVAL ' . (int)$daysOld . ' DAY))
                )';

        return $this->db->execute($sql);
    }
}
