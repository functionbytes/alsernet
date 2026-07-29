<?php

include_once(dirname(__FILE__).'/DefaultEndpointLogger.php');

class DocumentsEndpointLogger extends DefaultEndpointLogger
{

    public function logDocumentRequest($method, $url, array $data, array $context = [])
    {
        // Enriquecer el payload con información de contexto
        $enrichedData = array_merge($data, [
            '_context' => [
                'uid' => $context['uid'] ?? null,
                'document_type' => $context['document_type'] ?? null,
                'customer_id' => $context['customer_id'] ?? null,
                'order_reference' => $context['order_reference'] ?? null,
            ]
        ]);

        return $this->logRequest($method, $url, $enrichedData);
    }

    /**
     * Actualiza el log de una petición de documentos con información adicional
     *
     * @param int $id Request ID
     * @param string $status Estado (success, failed, server_unavailable)
     * @param array $responseData Datos de respuesta
     * @param array $additionalInfo Información adicional sobre el procesamiento
     */
    public function updateDocumentRequestLog($id, $status, array $responseData = [], array $additionalInfo = [])
    {
        // Si hay información adicional, agregarla a la respuesta
        if (!empty($additionalInfo)) {
            $responseData['_processing_info'] = $additionalInfo;
        }

        $this->updateRequestLog($id, $status, $responseData);
    }

    /**
     * Marca una petición como pendiente debido a servidor no disponible
     *
     * @param int $id Request ID
     * @param string $reason Razón de la indisponibilidad
     * @param string|null $nextRetryAt Fecha/hora del próximo intento
     */
    public function markAsServerUnavailable($id, $reason, $nextRetryAt = null)
    {
        $updateData = [
            'status' => 'server_unavailable',
            'last_error' => pSQL($reason),
        ];

        if ($nextRetryAt) {
            $updateData['next_retry_at'] = pSQL($nextRetryAt);
        }

        $this->db->update(
            'alsernet_forms_requests',
            $updateData,
            'id_alsernetforms_request = ' . (int)$id
        );
    }

    /**
     * Incrementa el contador de reintentos
     *
     * @param int $id Request ID
     * @param string|null $nextRetryAt Fecha/hora del próximo intento
     */
    public function incrementRetryCount($id, $nextRetryAt = null)
    {
        $sql = 'UPDATE ' . _DB_PREFIX_ . 'alsernet_forms_requests
                SET retry_count = retry_count + 1' .
                ($nextRetryAt ? ', next_retry_at = "' . pSQL($nextRetryAt) . '"' : '') . '
                WHERE id_alsernetforms_request = ' . (int)$id;

        $this->db->execute($sql);
    }

    /**
     * Obtiene todas las peticiones pendientes de procesar
     *
     * @param int $limit Número máximo de peticiones a obtener
     * @return array
     */
    public function getPendingRequests($limit = 50)
    {
        $sql = 'SELECT *
                FROM ' . _DB_PREFIX_ . 'alsernet_forms_requests
                WHERE endpoint_type = "' . pSQL($this->getType()) . '"
                AND status IN ("pending", "server_unavailable")
                AND retry_count < max_retries
                AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                ORDER BY created_at ASC
                LIMIT ' . (int)$limit;

        return $this->db->executeS($sql);
    }

    /**
     * Obtiene estadísticas de peticiones de documentos
     *
     * @return array
     */
    public function getStats()
    {
        $sql = 'SELECT
                    status,
                    COUNT(*) as count,
                    AVG(retry_count) as avg_retries
                FROM ' . _DB_PREFIX_ . 'alsernet_forms_requests
                WHERE endpoint_type = "' . pSQL($this->getType()) . '"
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY status';

        return $this->db->executeS($sql);
    }

    protected function getType()
    {
        return 'documents';
    }
}
