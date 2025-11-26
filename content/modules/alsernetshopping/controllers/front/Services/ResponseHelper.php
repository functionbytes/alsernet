<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Helper para estandarizar respuestas de controladores
 * Elimina duplicación de código en las respuestas AJAX
 */
class ResponseHelper
{
    /**
     * Crea una respuesta de éxito estandarizada
     */
    public static function success(array $data = [], string $message = ''): array
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Crea una respuesta de error estandarizada
     */
    public static function error(string $message, array $data = []): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Crea una respuesta de advertencia estandarizada
     */
    public static function warning(string $message, array $data = []): array
    {
        return [
            'status' => 'warning',
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Respuesta específica para carriers
     */
    public static function carrierResponse(string $status, string $html, int $carrierId, int $addressId, string $message = ''): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'html' => $html,
            'id_carrier' => $carrierId,
            'id_address' => $addressId
        ];
    }

    /**
     * Respuesta de autenticación requerida
     */
    public static function authRequired(\Context $context): array
    {
        require_once dirname(__FILE__) . '/../../classes/TranslationManager.php';

        return self::error(
            \TranslationManager::error('auth_required', $context->language->locale)
        );
    }

    /**
     * Respuesta para direcciones
     */
    public static function addressResponse(string $status, string $message, string $htmlDelivery = '', string $htmlInvoice = '', array $addresses = []): array
    {
        return [
            'status' => $status,
            'message' => $message,
            'html_delivery' => $htmlDelivery,
            'html_invoice' => $htmlInvoice,
            'addresses' => $addresses
        ];
    }
}