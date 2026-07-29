<?php

namespace AlsernetShopping\Carriers;

use Module;
use Hook;

/**
 * Gestor de delegación entre módulos
 * Detecta si módulo externo ya maneja carrier y delega o toma control
 *
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class ModuleDelegationManager
{
    /**
     * Estrategias de manejo de módulos
     */
    const STRATEGY_DELEGATE = 'delegate';     // Delegar al módulo externo
    const STRATEGY_OVERRIDE = 'override';     // Tomar control completo
    const STRATEGY_HYBRID = 'hybrid';         // Usar datos del módulo, UI nuestra

    private static $moduleStrategies = [
        'mondialrelay' => self::STRATEGY_HYBRID,  // Usar datos MR, UI nuestra
        'inpost' => self::STRATEGY_DELEGATE,      // Delegar completamente
        'correosexpress' => self::STRATEGY_OVERRIDE // Tomar control
    ];

    /**
     * Determina estrategia para un carrier específico
     */
    public static function getCarrierStrategy(int $carrierId): array
    {
        $externalModule = self::detectExternalModule($carrierId);

        if (!$externalModule) {
            return [
                'strategy' => self::STRATEGY_OVERRIDE,
                'module' => null,
                'reason' => 'No external module detected'
            ];
        }

        $strategy = self::$moduleStrategies[$externalModule] ?? self::STRATEGY_DELEGATE;

        return [
            'strategy' => $strategy,
            'module' => $externalModule,
            'reason' => "Module {$externalModule} found, using {$strategy} strategy"
        ];
    }

    /**
     * Detecta qué módulo externo maneja un carrier
     */
    private static function detectExternalModule(int $carrierId): ?string
    {
        // Carriers que NO son externos (manejados internamente)
        $internalCarriers = [101]; // 101 = Entrega a dirección seleccionada

        if (in_array($carrierId, $internalCarriers)) {
            return null;
        }

        // Verificar en base de datos mondialrelay
        if (self::isCarrierInMondialRelay($carrierId)) {
            return 'mondialrelay';
        }

        // Verificar otros módulos por convención
        $possibleModules = [
            'inpost' => [98],
            'correosexpress' => [66]
        ];

        foreach ($possibleModules as $module => $carriers) {
            if (in_array($carrierId, $carriers) && Module::isEnabled($module)) {
                return $module;
            }
        }

        return null;
    }

    /**
     * Verifica si carrier está en tabla mondialrelay
     */
    private static function isCarrierInMondialRelay(int $carrierId): bool
    {
        try {
            $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'mondialrelay_carrier_method`
                    WHERE `id_carrier` = ' . (int)$carrierId;

            $count = \Db::getInstance()->getValue($sql);
            return $count > 0;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ejecuta estrategia para un carrier
     */
    public static function executeStrategy(int $carrierId, array $data, \Context $context): array
    {
        // Iniciar monitoreo de rendimiento
        $monitor = \AlsernetShopping\Carriers\CarrierPerformanceMonitor::getInstance();
        $monitor->startTimer("delegation_strategy_{$carrierId}");

        $strategyInfo = self::getCarrierStrategy($carrierId);
        $strategy = $strategyInfo['strategy'];

        try {
            switch ($strategy) {
                case self::STRATEGY_DELEGATE:
                    $result = self::executeDelegate($carrierId, $strategyInfo['module'], $data, $context);
                    break;

                case self::STRATEGY_OVERRIDE:
                    $result = self::executeOverride($carrierId, $data, $context);
                    break;

                case self::STRATEGY_HYBRID:
                    $result = self::executeHybrid($carrierId, $strategyInfo['module'], $data, $context);
                    break;

                default:
                    $result = self::executeOverride($carrierId, $data, $context);
                    $strategy = 'default_override';
            }

            // Registrar métricas de delegación
            $executionTime = $monitor->stopTimer("delegation_strategy_{$carrierId}");
            $monitor->recordDelegationCall($carrierId, $strategy, $executionTime, $result['status'] === 'success');

            // Añadir información de rendimiento al resultado
            $result['performance'] = [
                'strategy' => $strategy,
                'execution_time' => $executionTime,
                'timestamp' => time()
            ];

            return $result;

        } catch (\Exception $e) {
            $executionTime = $monitor->stopTimer("delegation_strategy_{$carrierId}");
            $monitor->recordDelegationCall($carrierId, $strategy, $executionTime, false);

            // error_log("DelegationManager: Strategy execution error - " . $e->getMessage());

            return [
                'status' => 'error',
                'message' => 'Strategy execution failed: ' . $e->getMessage(),
                'strategy' => $strategy,
                'performance' => [
                    'execution_time' => $executionTime,
                    'error' => true
                ]
            ];
        }
    }

    /**
     * Estrategia DELEGATE - usar módulo externo completamente
     */
    private static function executeDelegate(int $carrierId, string $moduleName, array $data, \Context $context): array
    {
        try {
            $module = Module::getInstanceByName($moduleName);

            if (!$module) {
                throw new \Exception("Module {$moduleName} not available");
            }

            // Delegar al módulo externo
            if (method_exists($module, 'getCarrierContent')) {
                $content = $module->getCarrierContent($carrierId, $data);
            } else {
                // Fallback: usar hook del módulo
                $content = Hook::exec('displayCarrierExtraContent', $data, $module->id);
            }

            return [
                'status' => 'success',
                'html' => $content,
                'strategy' => 'delegated',
                'module' => $moduleName,
                'message' => "Delegated to {$moduleName} module"
            ];

        } catch (\Exception $e) {
            error_log("DelegationManager: Delegate error - " . $e->getMessage());
            return self::executeOverride($carrierId, $data, $context);
        }
    }

    /**
     * Estrategia OVERRIDE - usar nuestro sistema completamente
     */
    private static function executeOverride(int $carrierId, array $data, \Context $context): array
    {
        try {
            $registry = CarrierRegistry::getInstance();
            $handler = $registry->getHandler($carrierId);

            if (!$handler) {
                throw new \Exception("No handler found for carrier {$carrierId}");
            }

            $result = $handler->processSelection($data, $context);
            $result['strategy'] = 'override';

            return $result;

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Override strategy failed: ' . $e->getMessage(),
                'strategy' => 'override_failed'
            ];
        }
    }

    /**
     * Estrategia HYBRID - datos del módulo, UI nuestra
     */
    private static function executeHybrid(int $carrierId, string $moduleName, array $data, \Context $context): array
    {
        try {
            // 1. Obtener datos del módulo externo
            $externalData = self::getExternalModuleData($moduleName, $carrierId, $data, $context);

            // 2. Usar nuestro handler para UI
            $registry = CarrierRegistry::getInstance();
            $handler = $registry->getHandler($carrierId);

            if (!$handler) {
                throw new \Exception("No handler found for carrier {$carrierId}");
            }

            // 3. Combinar datos externos con nuestro procesamiento
            $combinedData = array_merge($data, $externalData);
            $result = $handler->processSelection($combinedData, $context);

            $result['strategy'] = 'hybrid';
            $result['external_module'] = $moduleName;
            $result['external_data'] = $externalData;

            return $result;

        } catch (\Exception $e) {
            error_log("DelegationManager: Hybrid error - " . $e->getMessage());
            return self::executeOverride($carrierId, $data, $context);
        }
    }

    /**
     * Obtiene datos del módulo externo
     */
    private static function getExternalModuleData(string $moduleName, int $carrierId, array $data, \Context $context): array
    {
        switch ($moduleName) {
            case 'mondialrelay':
                return self::getMondialRelayData($carrierId, $data, $context);

            case 'inpost':
                return self::getInPostData($carrierId, $data, $context);

            default:
                return [];
        }
    }

    /**
     * Obtiene datos específicos de MondialRelay
     */
    private static function getMondialRelayData(int $carrierId, array $data, \Context $context): array
    {
        try {
            // Obtener configuración del carrier desde BD
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_carrier_method`
                    WHERE `id_carrier` = ' . (int)$carrierId;

            $carrierMethod = \Db::getInstance()->getRow($sql);

            // Obtener configuración del módulo
            $moduleConfig = [
                'webservice_enseigne' => \Configuration::get('MONDIALRELAY_WEBSERVICE_ENSEIGNE'),
                'webservice_key' => \Configuration::get('MONDIALRELAY_WEBSERVICE_KEY'),
                'display_map' => \Configuration::get('MONDIALRELAY_DISPLAY_MAP'),
                'max_weight' => \Configuration::get('MONDIALRELAY_MAX_WEIGHT')
            ];

            return [
                'external_carrier_method' => $carrierMethod,
                'external_module_config' => $moduleConfig,
                'external_selected_relay' => self::getMondialRelaySelectedRelay($context)
            ];

        } catch (\Exception $e) {
            error_log("DelegationManager: Error getting MondialRelay data - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene punto seleccionado de MondialRelay
     */
    private static function getMondialRelaySelectedRelay(\Context $context): ?array
    {
        if (!$context->cart || !$context->cart->id) {
            return null;
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_selected_relay`
                WHERE `id_cart` = ' . (int)$context->cart->id;

        return \Db::getInstance()->getRow($sql) ?: null;
    }

    /**
     * Obtiene datos específicos de InPost
     */
    private static function getInPostData(int $carrierId, array $data, \Context $context): array
    {
        // Implementar según estructura del módulo InPost
        return [
            'external_inpost_config' => [],
            'external_selected_locker' => null
        ];
    }

    /**
     * Obtiene estadísticas de delegación
     */
    public static function getStats(): array
    {
        $stats = [
            'strategies' => self::$moduleStrategies,
            'carriers_by_strategy' => []
        ];

        // Analizar carriers por estrategia
        foreach ([98, 100, 101, 107, 108, 109, 110, 111] as $carrierId) {
            $strategy = self::getCarrierStrategy($carrierId);
            $stats['carriers_by_strategy'][$strategy['strategy']][] = $carrierId;
        }

        return $stats;
    }
}