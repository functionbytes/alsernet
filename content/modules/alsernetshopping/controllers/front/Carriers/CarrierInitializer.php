<?php

namespace AlsernetShopping\Carriers;

use Context;

/**
 * Inicializador del sistema de carriers
 * Se encarga de registrar todos los handlers disponibles
 *
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class CarrierInitializer
{
    private static $initialized = false;

    /**
     * Inicializa el sistema de carriers registrando todos los handlers
     *
     * @param Context|null $context Contexto de PrestaShop
     * @return bool true si se inicializó correctamente
     */
    public static function initialize(Context $context = null): bool
    {
        if (self::$initialized) {
            return true; // Ya inicializado
        }

        try {
            $context = $context ?: Context::getContext();
            $registry = CarrierRegistry::getInstance();

            //$inpostIndependentHandler = new InPostIndependentHandler($context);
           // $registry->registerHandler($inpostIndependentHandler);

            //$mondialRelayCarriers = [100, 107, 108, 109, 110, 111];
            //foreach ($mondialRelayCarriers as $carrierId) {
            //    $handler = new MondialRelayModuleHandler($carrierId, $context);
            //    $registry->registerHandler($handler);
           // }

            $deliveryAddressHandler = new DeliveryAddressHandler();
            $registry->registerHandler($deliveryAddressHandler);
            // error_log("CarrierInitializer: DeliveryAddressHandler registered for carrier 101");

            // error_log("=== CarrierInitializer: Creating GuardPickupHandler ===");
            $gcPickupHandler = new GuardPickupHandler();
            $registry->registerHandler($gcPickupHandler);
            // error_log("CarrierInitializer: GuardPickupHandler registered for carrier 39 - ID: " . $gcPickupHandler->getId());

            // error_log("=== CarrierInitializer: Creating StorePickupHandler ===");
            if (!class_exists('AlsernetShopping\\Carriers\\StorePickupHandler')) {
                error_log('CarrierInitializer: StorePickupHandler class not found, checking if file was loaded');
                throw new \Exception('StorePickupHandler class not available');
            }

            $storePickupHandler = new StorePickupHandler($context);
            $registry->registerHandler($storePickupHandler);
            // error_log("CarrierInitializer: StorePickupHandler registered for carrier 78 - ID: " . $storePickupHandler->getId());

            // Registrar CorreosExpressHandler (carrier 66)
            if (!class_exists('AlsernetShopping\Carriers\CorreosExpressHandler')) {
                error_log('CarrierInitializer: CorreosExpressHandler class not found, checking if file was loaded');
                throw new \Exception('CorreosExpressHandler class not available');
            }
            $correosExpressHandler = new CorreosExpressHandler($context);
            $registry->registerHandler($correosExpressHandler);
            self::$initialized = true;

            // Debug info
            if (\Configuration::get('ALSERNET_CARRIER_DEBUG')) {
                $stats = $registry->getStats();
                error_log('CarrierInitializer: System initialized - ' . json_encode($stats));
            }

            return true;

        } catch (\Exception $e) {
            error_log('CarrierInitializer: Error during initialization - ' . $e->getMessage());
            return false;
        }
    }

    public static function isInitialized(): bool
    {
        return self::$initialized;
    }

    /**
     * Reinicia el sistema (útil para testing)
     */
    public static function reset(): void
    {
        self::$initialized = false;
        CarrierRegistry::getInstance()->clearCache();
    }

    /**
     * Obtiene estadísticas del sistema inicializado
     *
     * @return array|null Estadísticas o null si no está inicializado
     */
    public static function getSystemStats(): ?array
    {
        if (!self::$initialized) {
            return null;
        }

        $registry = CarrierRegistry::getInstance();
        $assetManager = CarrierAssetManager::getInstance();

        return [
            'initialized' => true,
            'registry_stats' => $registry->getStats(),
            'asset_stats' => $assetManager->getStats(),
            'registered_handlers' => array_keys($registry->getAllHandlers())
        ];
    }
}