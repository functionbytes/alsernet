<?php

/**
 * Cargador bajo demanda para carriers - Compatible con PrestaShop
 * Solo carga las clases cuando se necesitan
 * 
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class CarrierLoader
{
    private static $loaded = [];
    private static $carriersDir = null;

    /**
     * Inicializa el loader
     */
    public static function init()
    {
        if (self::$carriersDir === null) {
            self::$carriersDir = dirname(__FILE__) . '/';
        }
    }

    /**
     * Carga una clase específica
     */
    public static function loadClass($className)
    {
        self::init();
        
        if (isset(self::$loaded[$className])) {
            return true; // Ya cargada
        }

        $filename = $className . '.php';
        $filepath = self::$carriersDir . $filename;

        if (file_exists($filepath)) {
            require_once $filepath;
            self::$loaded[$className] = true;
            return true;
        }

        return false;
    }

    /**
     * Carga clases base necesarias
     */
    public static function loadBaseClasses()
    {
        $baseClasses = [
            'CarrierHandlerInterface',
            'AbstractCarrierHandler',
            'CarrierRegistry',
            'CarrierAssetManager'
        ];

        foreach ($baseClasses as $class) {
            self::loadClass($class);
        }
    }

    /**
     * Carga handler específico para un carrier
     */
    public static function loadCarrierHandler($carrierId)
    {
        self::loadBaseClasses();

        // Mapeo de carriers a handlers
        $carrierHandlers = [
            101 => 'MondialRelayHandler',
            98 => 'InPostIndependentHandler',
            100 => 'MondialRelayModuleHandler',
            107 => 'MondialRelayModuleHandler',
            108 => 'MondialRelayModuleHandler',
            109 => 'MondialRelayModuleHandler',
            110 => 'MondialRelayModuleHandler',
            111 => 'MondialRelayModuleHandler'
        ];

        if (isset($carrierHandlers[$carrierId])) {
            $handlerClass = $carrierHandlers[$carrierId];
            
            // Cargar dependencias según el handler
            if (strpos($handlerClass, 'External') !== false || 
                strpos($handlerClass, 'MondialRelayModule') !== false || 
                strpos($handlerClass, 'InPostIndependent') !== false) {
                self::loadClass('ExternalModuleCarrierHandler');
            }

            return self::loadClass($handlerClass);
        }

        return false;
    }

    /**
     * Carga sistema de delegación
     */
    public static function loadDelegationSystem()
    {
        self::loadBaseClasses();
        self::loadClass('ModuleDelegationManager');
        self::loadClass('ModuleBypassManager');
    }

    /**
     * Obtiene estadísticas de carga
     */
    public static function getStats()
    {
        return [
            'loaded_classes' => array_keys(self::$loaded),
            'total_loaded' => count(self::$loaded),
            'carriers_dir' => self::$carriersDir
        ];
    }
}