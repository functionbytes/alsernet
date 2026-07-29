<?php

include_once dirname(__FILE__).'/HttpClient.php';

/**
 * EndpointAvailabilityChecker
 *
 * Verifica la disponibilidad de endpoints antes de realizar peticiones.
 * Utiliza un sistema de circuit breaker para evitar sobrecargar servidores caídos.
 * Se conecta al endpoint /api/health del servidor Laravel para verificar disponibilidad.
 *
 * Usa HttpClient para todas las peticiones HTTP (curl centralizado).
 */
class EndpointAvailabilityChecker
{
    private $db;

    private $httpClient;

    private $checkTimeoutSeconds = 5;

    private $failureThreshold = 3; // Número de fallos consecutivos antes de marcar como no disponible

    private $recoveryCheckInterval = 300; // 5 minutos entre intentos cuando está marcado como no disponible

    private $healthEndpointUrl = 'https://webadminpruebas.a-alvarez.com/api/health'; // URL base del health check

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->httpClient = new HttpClient($this->checkTimeoutSeconds, $this->checkTimeoutSeconds, false);
    }

    /**
     * Verifica si un endpoint está disponible
     *
     * @param  string  $url  URL del endpoint a verificar
     * @param  string  $type  Tipo de endpoint (documents, subscription, form, etc.)
     * @return array ['available' => bool, 'reason' => string|null]
     */
    public function isEndpointAvailable($url, $type = 'default')
    {
        $healthRecord = $this->getEndpointHealth($url, $type);

        // Si existe un registro y está marcado como no disponible
        if ($healthRecord && ! $healthRecord['is_available']) {
            // Verificar si ha pasado el tiempo de recuperación
            $lastCheck = strtotime($healthRecord['last_check_at']);
            $now = time();

            if (($now - $lastCheck) < $this->recoveryCheckInterval) {
                return [
                    'available' => false,
                    'reason' => 'Endpoint marcado como no disponible. Próximo intento en '.
                               ($this->recoveryCheckInterval - ($now - $lastCheck)).' segundos',
                    'next_retry_at' => date('Y-m-d H:i:s', $lastCheck + $this->recoveryCheckInterval),
                ];
            }
        }

        // Realizar una verificación real del endpoint
        return $this->performHealthCheck($url, $type);
    }

    /**
     * Realiza una verificación real de salud del endpoint
     * Usa el endpoint /api/health específico según el tipo
     *
     * @param  string  $url  URL original del endpoint de negocio (no se usa, solo para tracking)
     * @param  string  $type  Tipo de endpoint (documents, subscription, etc.)
     * @return array
     */
    private function performHealthCheck($url, $type)
    {
        $startTime = microtime(true);

        // Determinar qué endpoint de health usar según el tipo
        $healthUrl = $this->getHealthEndpointForType($type);

        // Usar HttpClient centralizado
        $httpResponse = $this->httpClient->get($healthUrl);

        $responseTime = (microtime(true) - $startTime) * 1000; // En milisegundos
        $httpCode = $httpResponse['status'];
        $curlError = $httpResponse['error'];

        // Parsear respuesta JSON
        $healthData = null;
        if (! $curlError && $httpResponse['body']) {
            $healthData = $this->httpClient->decodeJson($httpResponse['body']);
        }

        // Determinar si está disponible
        // El endpoint debe responder 200 y tener status "healthy" o "ok"
        $isHealthy = false;
        if ($httpCode === 200 && $healthData) {
            $status = $healthData['status'] ?? '';
            $isHealthy = in_array($status, ['healthy', 'ok']);
        }

        $isAvailable = $isHealthy && empty($curlError);

        // Actualizar registro de salud
        $this->updateEndpointHealth($url, $type, $isAvailable, $responseTime, $curlError ?: null);

        if (! $isAvailable) {
            $healthRecord = $this->getEndpointHealth($url, $type);
            $reason = $curlError ?: "HTTP {$httpCode}";

            // Si tenemos datos de health, usar mensaje más descriptivo
            if ($healthData && isset($healthData['checks'])) {
                $unhealthyChecks = array_filter($healthData['checks'], function ($check) {
                    return $check['status'] !== 'healthy';
                });

                if (! empty($unhealthyChecks)) {
                    $reason = 'Unhealthy checks: '.implode(', ', array_keys($unhealthyChecks));
                }
            }

            return [
                'available' => false,
                'reason' => $reason,
                'health_data' => $healthData,
                'next_retry_at' => ($healthRecord && $healthRecord['consecutive_failures'] >= $this->failureThreshold)
                    ? date('Y-m-d H:i:s', time() + $this->recoveryCheckInterval)
                    : date('Y-m-d H:i:s', time() + 60), // 1 minuto si aún no alcanza el threshold
            ];
        }

        return [
            'available' => true,
            'reason' => null,
            'response_time_ms' => round($responseTime, 2),
            'health_data' => $healthData,
        ];
    }

    private function getHealthEndpointForType($type)
    {
        switch ($type) {
            case 'documents':
                // Health check específico para documentos
                return rtrim($this->healthEndpointUrl, '/').'/documents';

            case 'subscription':
            case 'form':
            case 'default':
            default:
                // Health check general
                return rtrim($this->healthEndpointUrl, '/');
        }
    }

    private function getEndpointHealth($url, $type)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'alsernet_endpoint_health
                WHERE endpoint_url = "'.pSQL($url).'"
                AND endpoint_type = "'.pSQL($type).'"';

        return $this->db->getRow($sql);
    }

    /**
     * Actualiza el estado de salud de un endpoint
     *
     * @param  string  $url
     * @param  string  $type
     * @param  bool  $isAvailable
     * @param  float  $responseTime
     * @param  string|null  $error
     */
    private function updateEndpointHealth($url, $type, $isAvailable, $responseTime, $error = null)
    {
        $existing = $this->getEndpointHealth($url, $type);
        $now = date('Y-m-d H:i:s');

        if (! $existing) {
            // Crear nuevo registro
            $this->db->insert('alsernet_endpoint_health', [
                'endpoint_url' => pSQL($url),
                'endpoint_type' => pSQL($type),
                'is_available' => (int) $isAvailable,
                'last_check_at' => $now,
                'last_success_at' => $isAvailable ? $now : null,
                'last_failure_at' => ! $isAvailable ? $now : null,
                'consecutive_failures' => $isAvailable ? 0 : 1,
                'response_time_ms' => (int) $responseTime,
            ]);
        } else {
            // Actualizar registro existente
            $consecutiveFailures = $isAvailable
                ? 0
                : $existing['consecutive_failures'] + 1;

            $updateData = [
                'is_available' => (int) ($isAvailable || $consecutiveFailures < $this->failureThreshold),
                'last_check_at' => $now,
                'consecutive_failures' => $consecutiveFailures,
                'response_time_ms' => (int) $responseTime,
            ];

            if ($isAvailable) {
                $updateData['last_success_at'] = $now;
            } else {
                $updateData['last_failure_at'] = $now;
            }

            $this->db->update(
                'alsernet_endpoint_health',
                $updateData,
                'id_endpoint_health = '.(int) $existing['id_endpoint_health']
            );
        }
    }

    public function getHealthStats()
    {
        $sql = 'SELECT
                    endpoint_type,
                    COUNT(*) as total,
                    SUM(is_available) as available,
                    AVG(response_time_ms) as avg_response_time,
                    MAX(last_check_at) as last_check
                FROM '._DB_PREFIX_.'alsernet_endpoint_health
                GROUP BY endpoint_type';

        return $this->db->executeS($sql);
    }

    public function forceCheck($url, $type = 'default')
    {
        return $this->performHealthCheck($url, $type);
    }
}
