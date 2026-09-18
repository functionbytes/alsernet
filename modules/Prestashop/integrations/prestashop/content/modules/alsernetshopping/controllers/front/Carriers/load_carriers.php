<?php

use AlsernetShopping\Carriers\CarrierInitializer;

/**
 * Sistema de carga manual para carriers - Compatible con PrestaShop
 * No usa autoload para evitar conflictos
 *
 * @version 1.0.0
 *
 * @since 2025-08-16
 */

// Evitar carga múltiple
if (defined('ALSERNET_CARRIERS_LOADED')) {
    return;
}

define('ALSERNET_CARRIERS_LOADED', true);

// Directorio base de carriers
$carriersDir = dirname(__FILE__).'/';

// Orden de carga (dependencias primero)
$loadOrder = [
    // 1. Interface base
    'CarrierHandlerInterface.php',

    // 2. Clases base
    'AbstractCarrierHandler.php',
    'CarrierRegistry.php',

    // 3. Clases extendidas
    'ExternalModuleCarrierHandler.php',

    // 4. Handlers específicos
    'DeliveryAddressHandler.php',
    'GuardPickupHandler.php',
    'StorePickupHandler.php',
    // 'MondialRelayHandler.php',
    // 'MondialRelayModuleHandler.php',
    // 'InPostCarrierHandler.php',
    // 'InPostIndependentHandler.php',
    'CorreosExpressHandler.php',

    // 5. Gestores de sistema
    'ModuleBypassManager.php',
    'ModuleDelegationManager.php',

    // 6. Inicializador (último)
    'CarrierInitializer.php',
];

// Cargar archivos en orden
foreach ($loadOrder as $filename) {
    $filePath = $carriersDir.$filename;

    if (file_exists($filePath)) {
        require_once $filePath;
        // error_log("CarrierSystem: Loaded - {$filename}");
    } else {
        // error_log("CarrierSystem: File not found - {$filename}");
    }
}

// Inicializar sistema automáticamente
try {
    // error_log('CarrierSystem: Attempting to initialize...');
    if (class_exists('AlsernetShopping\Carriers\CarrierInitializer')) {
        // error_log('CarrierSystem: CarrierInitializer class found, initializing...');
        $result = CarrierInitializer::initialize();
        // error_log('CarrierSystem: Initialization result: ' . ($result ? 'SUCCESS' : 'FAILED'));
    } else {
        // error_log('CarrierSystem: CarrierInitializer class NOT found');
    }
} catch (Exception $e) {
    error_log('CarrierSystem: Auto-initialization failed - '.$e->getMessage());
}
