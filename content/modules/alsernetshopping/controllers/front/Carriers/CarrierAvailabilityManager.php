<?php

namespace AlsernetShopping\Carriers;

use Context;
use Cart;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Gestor de disponibilidad de carriers
 * Maneja la lógica de filtrado de carriers basada en productos del carrito
 */
class CarrierAvailabilityManager
{
    private static $instance;
    
    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function filterAvailableCarriers(array $carriers_available, Cart $cart, Context $context): array
    {
        $registry = CarrierRegistry::getInstance();
        
        // Obtener todos los handlers que tienen reglas especiales
        $specialHandlers = [];
        foreach ($carriers_available as $carrierId => $carrierData) {
            $handler = $registry->getHandler((int)$carrierId);
            if ($handler && method_exists($handler, 'isAvailableForCart')) {
                $specialHandlers[$carrierId] = $handler;
            }
        }

        // Aplicar reglas de exclusión
        $excludeOtherCarriers = false;
        $availableSpecialCarriers = [];
        
        foreach ($specialHandlers as $carrierId => $handler) {
            if ($handler->isAvailableForCart($cart)) {
                $availableSpecialCarriers[] = $carrierId;
                
                // Si este handler requiere exclusión de otros carriers
                if (method_exists($handler, 'shouldExcludeOtherCarriers') && 
                    $handler->shouldExcludeOtherCarriers($cart)) {
                    $excludeOtherCarriers = true;
                }
            }
        }

        // Si hay carriers especiales que requieren exclusión
        if ($excludeOtherCarriers) {
            // Solo mantener los carriers especiales disponibles
            $filtered = [];
            foreach ($availableSpecialCarriers as $carrierId) {
                if (isset($carriers_available[$carrierId])) {
                    $filtered[$carrierId] = $carriers_available[$carrierId];
                }
            }
            return $filtered;
        }

        // Si hay carriers especiales disponibles pero no excluyen otros
        if (!empty($availableSpecialCarriers)) {
            return $carriers_available; // Mantener todos disponibles
        }

        // Eliminar carriers especiales que no están disponibles para este carrito
        foreach ($specialHandlers as $carrierId => $handler) {
            if (!$handler->isAvailableForCart($cart)) {
                unset($carriers_available[$carrierId]);
            }
        }

        return $carriers_available;
    }

    /**
     * Verifica si un carrier específico está disponible para un carrito
     * 
     * @param int $carrierId ID del carrier
     * @param Cart $cart Carrito
     * @return bool true si está disponible
     */
    public function isCarrierAvailableForCart(int $carrierId, Cart $cart): bool
    {
        $registry = CarrierRegistry::getInstance();
        $handler = $registry->getHandler($carrierId);
        
        if (!$handler) {
            return true; // Si no hay handler, asumimos que está disponible
        }

        if (method_exists($handler, 'isAvailableForCart')) {
            return $handler->isAvailableForCart($cart);
        }

        return $handler->isEnabled();
    }

    /**
     * Obtiene información de debugging sobre la disponibilidad
     * 
     * @param array $carriers_available Carriers disponibles
     * @param Cart $cart Carrito
     * @return array Información de debug
     */
    public function getAvailabilityDebugInfo(array $carriers_available, Cart $cart): array
    {
        $debug = [
            'total_carriers' => count($carriers_available),
            'cart_id' => $cart->id,
            'cart_products_count' => count($cart->getProducts()),
            'handlers_info' => []
        ];

        $registry = CarrierRegistry::getInstance();
        
        foreach ($carriers_available as $carrierId => $carrierData) {
            $handler = $registry->getHandler((int)$carrierId);
            $handlerInfo = [
                'carrier_id' => $carrierId,
                'has_handler' => !is_null($handler),
                'handler_class' => $handler ? get_class($handler) : null,
                'is_enabled' => $handler ? $handler->isEnabled() : null,
                'available_for_cart' => null,
                'should_exclude_others' => null
            ];

            if ($handler && method_exists($handler, 'isAvailableForCart')) {
                $handlerInfo['available_for_cart'] = $handler->isAvailableForCart($cart);
            }

            if ($handler && method_exists($handler, 'shouldExcludeOtherCarriers')) {
                $handlerInfo['should_exclude_others'] = $handler->shouldExcludeOtherCarriers($cart);
            }

            $debug['handlers_info'][] = $handlerInfo;
        }

        return $debug;
    }
}