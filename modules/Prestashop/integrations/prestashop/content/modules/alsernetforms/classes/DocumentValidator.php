<?php

/**
 * DocumentValidator - Validador de Documentos con Resiliencia
 *
 * Esta clase proporciona una capa de abstracción resiliente para la validación de documentos
 * de pedidos que requieren licencias (armas cortas, rifles, escopetas, DNI, etc.).
 *
 * CARACTERÍSTICAS PRINCIPALES:
 * ────────────────────────────────────────────────────────────────────────────
 * ✅ Circuit Breaker Pattern - Verifica disponibilidad del servidor antes de enviar
 * ✅ Queue System - Guarda peticiones como "pending" si el servidor está caído
 * ✅ Automatic Retry - El sistema de cron procesa automáticamente las pendientes
 * ✅ Normalized Response - Estructura de respuesta consistente independiente del resultado
 * ✅ i18n Support - Traducciones automáticas según contexto del usuario
 *
 * FLUJO DE TRABAJO:
 * ────────────────────────────────────────────────────────────────────────────
 * 1. validateDocuments() recibe UID y tipo de documento
 * 2. ApiManager verifica disponibilidad con EndpointAvailabilityChecker
 * 3. Si servidor disponible → Procesa inmediatamente
 * 4. Si servidor NO disponible → Guarda en BD como "pending"
 * 5. Cron job procesa pendientes cada 5-15 minutos
 * 6. Usuario recibe feedback apropiado en ambos casos
 *
 * EJEMPLO DE USO BÁSICO:
 * ────────────────────────────────────────────────────────────────────────────
 * ```php
 * // Crear instancia del validador
 * $validator = new DocumentValidator();
 *
 * // Validar documentos de un pedido
 * $result = $validator->validateDocuments(
 *     'ORDER-12345',           // UID del pedido
 *     'corta',                 // Tipo: corta|rifle|escopeta|dni
 *     [
 *         'customer_id' => 789,
 *         'order_reference' => 'ORDER-12345'
 *     ]
 * );
 *
 * // Interpretar resultado
 * if ($result['status'] === 'success') {
 *     echo "Documentos validados: " . count($result['data']['uploaded_documents']);
 * } elseif ($result['status'] === 'pending') {
 *     echo "Servidor no disponible. Petición guardada con ID: " . $result['request_id'];
 * } else {
 *     echo "Error: " . $result['message'];
 * }
 * ```
 *
 * EJEMPLO CON VERIFICACIÓN DE ESTADO:
 * ────────────────────────────────────────────────────────────────────────────
 * ```php
 * // Si guardaste el request_id cuando el servidor estaba caído
 * $requestId = 12345;
 *
 * // Verificar estado actual de la petición
 * $status = $validator->checkPendingRequestStatus($requestId);
 *
 * echo "Estado: " . $status['status'];
 * echo "Reintentos: " . $status['retry_count'] . "/" . $status['max_retries'];
 * echo "Próximo intento: " . $status['next_retry_at'];
 * ```
 *
 * ESTRUCTURA DE RESPUESTA:
 * ────────────────────────────────────────────────────────────────────────────
 * Array con los siguientes campos:
 * - status: 'success' | 'pending' | 'error'
 * - type: string - Tipo de documento solicitado
 * - label: string - Etiqueta traducida del tipo de documento
 * - upload: bool - Si se permite subir documentos
 * - data: array - Contiene required_documents, uploaded_documents, missing_documents
 * - message: string - Mensaje descriptivo del resultado
 * - request_id: int|null - ID de la petición (solo si status='pending')
 * - reason: string|null - Razón del fallo (solo si status='pending')
 * - error: string|null - Detalles del error (solo si status='error')
 *
 * TIPOS DE DOCUMENTO SOPORTADOS:
 * ────────────────────────────────────────────────────────────────────────────
 * - 'corta': Arma corta (requiere DNI + Licencia tipo B/F)
 * - 'rifle': Rifle rayado (requiere DNI + Licencia tipo D)
 * - 'escopeta': Escopeta (requiere DNI + Licencia tipo E)
 * - 'dni': Armas de aire comprimido (solo requiere DNI)
 * - Otros: Pasaporte o permiso de conducir
 *
 * INTEGRACIÓN CON LARAVEL:
 * ────────────────────────────────────────────────────────────────────────────
 * Espera que Laravel tenga configurado el endpoint:
 * POST /api/orders/validate-documents
 *
 * Y los health check endpoints:
 * GET /api/health/ping
 * GET /api/health/documents
 *
 * @author     Alsernet Development Team
 *
 * @version    1.0.0
 *
 * @since      2025-01-06
 * @see        ApiManager Para la gestión de peticiones HTTP
 * @see        DocumentsEndpointLogger Para el registro de peticiones
 * @see        EndpointAvailabilityChecker Para verificación de disponibilidad
 * @link       /modules/alsernetforms/DOCUMENTATION.md Documentación completa
 * @link       /modules/alsernetforms/README_PENDING_REQUESTS.md Sistema de peticiones pendientes
 */

include_once dirname(__FILE__).'/Actions/DocumentAction.php';

class DocumentValidator
{
    /**
     * Action para validación de documentos (usa nueva arquitectura refactorizada)
     *
     * @var DocumentAction
     *
     * @see DocumentAction::validateToken() Para validación de tokens
     */
    private $documentAction;

    /**
     * Endpoint relativo de Laravel para validación de documentos
     * NOTA: Este endpoint es diferente al usado por DocumentAction (api/documents)
     *
     * Se concatena con apiBaseUrl configurado en ApiManager
     * URL completa: https://webadminpruebas.a-alvarez.com/api/orders/validate-documents
     *
     * @var string
     */
    private $validationEndpoint = 'api/orders/validate-documents';

    public function __construct()
    {
        $this->documentAction = new DocumentAction;
    }

    public function validateDocuments($uid, $documentType = null, array $additionalContext = [])
    {
        // Preparar contexto completo
        $context = array_merge(
            ['document_type' => $documentType],
            $additionalContext
        );

        $actionResponse = $this->documentAction->validateToken($uid, $context);

        return $this->parseValidationResponse($actionResponse, $uid, $documentType);
    }

    private function parseValidationResponse(array $response, $uid, $documentType)
    {
        $status = $response['status'] ?? 'error';
        $data = $response['data'] ?? [];

        // Determinar si puede subir documentos (solo si status=success)
        $canUpload = ($status === 'success' || $status === 200);

        // Usar datos de DocumentAction si existen, sino usar helpers locales
        $finalDocumentType = $data['document_type'] ?? $documentType ?? 'dni';

        $result = [
            'status' => $status === 200 ? 'success' : $status,  // Normalizar status HTTP 200
            'type' => $finalDocumentType,
            'label' => $data['label'] ?? $this->getDocumentLabel($finalDocumentType),
            'upload' => $canUpload,
            'data' => [
                'uid' => $data['uid'] ?? $uid,
                'document_type' => $finalDocumentType,
                'required_documents' => $data['required_documents'] ?? $this->getRequiredDocumentsByType($finalDocumentType),
                'uploaded_documents' => $data['uploaded_documents'] ?? [],
                'missing_documents' => $data['missing_documents'] ?? $this->getRequiredDocumentsByType($finalDocumentType),
            ],
            'message' => $response['error'] ?? 'Validation processed',
        ];

        // Agregar campos adicionales si están presentes
        if (isset($response['request_id'])) {
            $result['request_id'] = $response['request_id'];
        }

        if (isset($response['reason'])) {
            $result['reason'] = $response['reason'];
        }

        if ($status === 'error' && isset($response['error'])) {
            $result['error'] = $response['error'];
        }

        return $result;
    }

    private function getDocumentLabel($documentType)
    {
        $iso = Context::getContext()->language->iso_code;
        $module = Module::getInstanceByName('alsernetforms');

        $labels = [
            'corta' => $module->l('Handgun permit', 'DocumentValidator', $iso),
            'rifle' => $module->l('Rifle permit', 'DocumentValidator', $iso),
            'escopeta' => $module->l('Shotgun license', 'DocumentValidator', $iso),
            'dni' => $module->l('ID document', 'DocumentValidator', $iso),
        ];

        return $labels[$documentType] ?? $module->l('Document validation', 'DocumentValidator', $iso);
    }

    private function getRequiredDocumentsByType($documentType)
    {
        $iso = Context::getContext()->language->iso_code;
        $module = Module::getInstanceByName('alsernetforms');

        switch ($documentType) {
            case 'corta':
                return [
                    $module->l('ID (both sides)', 'DocumentValidator', $iso),
                    $module->l('Handgun permit (type B) or Olympic shooting permit (type F)', 'DocumentValidator', $iso),
                ];
            case 'rifle':
                return [
                    $module->l('ID (both sides)', 'DocumentValidator', $iso),
                    $module->l('Rifled long-range firearm permit (type D)', 'DocumentValidator', $iso),
                ];
            case 'escopeta':
                return [
                    $module->l('ID (both sides)', 'DocumentValidator', $iso),
                    $module->l('Shotgun license (type E)', 'DocumentValidator', $iso),
                ];
            case 'dni':
                return [
                    $module->l('ID (both sides)', 'DocumentValidator', $iso),
                ];
            default:
                return [
                    $module->l('Passport or driving licence (both sides)', 'DocumentValidator', $iso),
                ];
        }
    }

    public function checkPendingRequestStatus($requestId)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'alsernet_forms_requests
                WHERE id_alsernetforms_request = '.(int) $requestId;

        return \Db::getInstance()->getRow($sql);
    }

    public function getPendingRequestsForUid($uid)
    {
        $sql = 'SELECT * FROM '._DB_PREFIX_.'alsernet_forms_requests
                WHERE endpoint_type = "documents"
                AND payload LIKE "%\"uid\":\"'.pSQL($uid).'\"%"
                AND status IN ("pending", "server_unavailable")
                ORDER BY created_at DESC';

        return \Db::getInstance()->executeS($sql);
    }

}
