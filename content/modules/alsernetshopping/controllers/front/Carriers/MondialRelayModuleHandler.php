<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;
use Configuration;
use Db;

/**
 * Handler unificado para todos los carriers que usan el módulo MondialRelay
 * Maneja carriers: 100, 107, 108, 109, 110, 111
 *
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class MondialRelayModuleHandler extends ExternalModuleCarrierHandler
{
    // Configuración de carriers
    const CARRIER_CONFIGS = [
        100 => [ // MondialRelay Standard
            'name' => 'MondialRelay',
            'delivery_mode' => '24R',
            'delivery_type' => 'MR',
            'service_type' => 'standard',
            'insurance_level' => '0',
            'country' => 'ES',
            'theme' => 'mondialrelay'
        ],
        107 => [ // InPost Punto Pack via MondialRelay
            'name' => 'InPost Punto Pack',
            'delivery_mode' => 'ESP',
            'delivery_type' => 'IP',
            'service_type' => 'punto_pack',
            'insurance_level' => '0',
            'country' => 'ES',
            'theme' => 'inpost'
        ],
        108 => [ // InPost Locker via MondialRelay
            'name' => 'InPost Locker',
            'delivery_mode' => 'ESP',
            'delivery_type' => 'IP',
            'service_type' => 'locker',
            'insurance_level' => '1',
            'country' => 'ES',
            'theme' => 'inpost'
        ],
        109 => [ // MondialRelay Punto Pack
            'name' => 'MondialRelay Punto Pack',
            'delivery_mode' => '24R',
            'delivery_type' => 'MR',
            'service_type' => 'punto_pack',
            'insurance_level' => '0',
            'country' => 'ES',
            'theme' => 'mondialrelay'
        ],
        110 => [ // MondialRelay Locker
            'name' => 'MondialRelay Locker',
            'delivery_mode' => '24R',
            'delivery_type' => 'MR',
            'service_type' => 'locker',
            'insurance_level' => '1',
            'country' => 'ES',
            'theme' => 'mondialrelay'
        ],
        111 => [ // MondialRelay Alemania
            'name' => 'MondialRelay Alemania',
            'delivery_mode' => '24R',
            'delivery_type' => 'MR',
            'service_type' => 'international',
            'insurance_level' => '0',
            'country' => 'DE',
            'theme' => 'mondialrelay'
        ]
    ];

    private $carrierId;
    private $carrierConfig;

    /**
     * Constructor
     */
    public function __construct(int $carrierId, Context $context = null)
    {
        $this->carrierId = $carrierId;

        if (!isset(self::CARRIER_CONFIGS[$carrierId])) {
            throw new \InvalidArgumentException("Unsupported carrier ID: {$carrierId}");
        }

        $this->carrierConfig = self::CARRIER_CONFIGS[$carrierId];
        parent::__construct($context);
        $this->loadCarrierMethodFromDB();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return $this->carrierId;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticName(): string
    {
        return $this->carrierConfig['name'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExternalModuleName(): string
    {
        return 'mondialrelay';
    }

    /**
     * Carga configuración del carrier desde la BD
     */
    private function loadCarrierMethodFromDB(): void
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_carrier_method` 
                WHERE `id_carrier` = ' . (int)$this->carrierId;

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            // Usar configuración de la BD si existe
            $this->carrierConfig = array_merge($this->carrierConfig, [
                'delivery_mode' => $result['delivery_mode'],
                'delivery_type' => $result['delivery_type'],
                'insurance_level' => $result['insurance_level']
            ]);
        } else {
            // Insertar configuración por defecto en la BD
            $this->createCarrierMethodInDB();
        }

        $this->debug("MondialRelay carrier configuration loaded", [
            'carrier_id' => $this->carrierId,
            'config' => $this->carrierConfig
        ]);
    }

    /**
     * Crea configuración del carrier en la BD
     */
    private function createCarrierMethodInDB(): void
    {
        try {
            $sql = 'INSERT INTO `' . _DB_PREFIX_ . 'mondialrelay_carrier_method` 
                    (`id_carrier`, `delivery_mode`, `delivery_type`, `insurance_level`) 
                    VALUES (' . (int)$this->carrierId . ', 
                           "' . pSQL($this->carrierConfig['delivery_mode']) . '", 
                           "' . pSQL($this->carrierConfig['delivery_type']) . '", 
                           "' . pSQL($this->carrierConfig['insurance_level']) . '")';

            Db::getInstance()->execute($sql);
            $this->debug("Carrier method created in DB", ['carrier_id' => $this->carrierId]);

        } catch (\Exception $e) {
            $this->debug("Error creating carrier method in DB", [
                'carrier_id' => $this->carrierId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getExternalModuleConfig(): array
    {
        if (!$this->moduleEnabled) {
            return [];
        }

        // Configuración base del módulo MondialRelay
        $baseConfig = [
            'webservice_enseigne' => Configuration::get('MONDIALRELAY_WEBSERVICE_ENSEIGNE'),
            'webservice_key' => Configuration::get('MONDIALRELAY_WEBSERVICE_KEY'),
            'display_map' => (bool)Configuration::get('MONDIALRELAY_DISPLAY_MAP'),
            'max_weight' => (float)Configuration::get('MONDIALRELAY_MAX_WEIGHT'),
            'country_iso' => $this->carrierConfig['country'],
            'language_iso' => $this->context->language->iso_code,
        ];

        // Configuración específica del carrier
        $carrierSpecificConfig = [
            'carrier_id' => $this->carrierId,
            'delivery_mode' => $this->carrierConfig['delivery_mode'],
            'delivery_type' => $this->carrierConfig['delivery_type'],
            'insurance_level' => $this->carrierConfig['insurance_level'],
            'service_type' => $this->carrierConfig['service_type'],
            'theme' => $this->carrierConfig['theme'],
            'widget_theme' => $this->carrierConfig['theme'],
        ];

        return array_merge($baseConfig, $carrierSpecificConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function processExternalModuleData(array $data, Context $context): array
    {
        try {
            // Procesar datos específicos del carrier
            $carrierData = [
                'delivery_address' => $data['delivery_address'],
                'state_name' => $data['state_name'] ?? '',
                'country_name' => $data['country_name'] ?? '',
                'carrier' => $data['carrier'],
                'carrier_id' => $this->carrierId,
                'carrier_config' => $this->carrierConfig,
                'mondialrelay_config' => $this->getExternalModuleConfig(),
                'widget_config' => $this->getWidgetConfig(),
                'selected_relay' => $this->getSelectedRelay($context),
            ];

            return [
                'status' => 'success',
                'data' => $carrierData,
                'message' => 'MondialRelay data processed successfully'
            ];

        } catch (\Exception $e) {
            $this->debug("Error processing MondialRelay data", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Error processing MondialRelay data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene configuración del widget
     */
    private function getWidgetConfig(): array
    {
        $config = $this->getExternalModuleConfig();

        return [
            'Target' => '#mondialrelay-widget-container-' . $this->carrierId,
            'Brand' => $config['webservice_enseigne'] ?? 'BDTEST13',
            'Country' => $config['country_iso'] ?? 'ES',
            'Language' => $config['language_iso'] ?? 'es',
            'Theme' => $this->carrierConfig['theme'],
            'ColLivMod' => $this->carrierConfig['delivery_mode'],
            'NbResults' => 10,
            'ShowResultsOnMap' => $config['display_map'],
            'DeliveryType' => $this->carrierConfig['delivery_type'],
            'InsuranceLevel' => $this->carrierConfig['insurance_level'],
            'OnParcelShopSelected' => 'onMondialRelaySelected_' . $this->carrierId
        ];
    }

    /**
     * Obtiene el punto seleccionado
     */
    private function getSelectedRelay(Context $context): ?array
    {
        if (!$context->cart || !$context->cart->id) {
            return null;
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_selected_relay` 
                WHERE `id_cart` = ' . (int)$context->cart->id . '
                AND `id_customer` = ' . (int)$context->customer->id;

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            return [
                'relay_num' => $result['selected_relay_num'],
                'relay_name' => $result['selected_relay_adr1'],
                'relay_address' => $result['selected_relay_adr1'],
                'relay_city' => $result['selected_relay_city'],
                'relay_postcode' => $result['selected_relay_postcode'],
            ];
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredAssets(): array
    {
        $assets = parent::getRequiredAssets();

        // Determinar tema y assets según el carrier
        $theme = $this->carrierConfig['theme'];
        $serviceType = $this->carrierConfig['service_type'];

        // CSS específico
        $cssFile = ($theme === 'inpost') ? 'inpost.css' : 'mondialrelay.css';
        $assets[] = [
            'type' => 'css',
            'path' => "modules/alsernetshopping/views/css/front/carriers/{$cssFile}",
            'priority' => 100
        ];

        // JavaScript específico
        $jsFile = ($theme === 'inpost') ? 'InPostCarrier.js' : 'mondial-relay-carrier.js';
        $assets[] = [
            'type' => 'js',
            'path' => "modules/alsernetshopping/views/js/front/checkout/carriers/{$jsFile}",
            'priority' => 200
        ];

        // Widget oficial si está configurado
        if ($this->configuration['widget_enabled'] ?? true) {
            $assets[] = [
                'type' => 'js',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js',
                'priority' => 150
            ];
            $assets[] = [
                'type' => 'css',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/css/mondialrelay.css',
                'priority' => 90
            ];
        }

        return $assets;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath(): string
    {
        $theme = $this->carrierConfig['theme'];
        $carrierId = $this->carrierId;

        // Usar template específico según el tema
        if ($theme === 'inpost') {
            return "module:alsernetshopping/views/templates/front/checkout/carriers/{$carrierId}_inpost/interface.tpl";
        } else {
            return "module:alsernetshopping/views/templates/front/checkout/carriers/{$carrierId}_mondialrelay/interface.tpl";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function validateAvailability(Context $context): array
    {
        $baseValidation = parent::validateAvailability($context);

        if (!$baseValidation['valid']) {
            return $baseValidation;
        }

        // Validaciones específicas
        $config = $this->getExternalModuleConfig();

        if (empty($config['webservice_key'])) {
            return [
                'valid' => false,
                'message' => 'MondialRelay API key not configured'
            ];
        }

        // Verificar país soportado
        $supportedCountries = ['ES', 'FR', 'BE', 'LU', 'DE', 'PL', 'UK', 'IT'];
        if (!in_array($this->carrierConfig['country'], $supportedCountries)) {
            return [
                'valid' => false,
                'message' => 'Service not available in target country'
            ];
        }

        return [
            'valid' => true,
            'message' => 'MondialRelay service is available'
        ];
    }

    /**
     * Guarda punto seleccionado
     */
    public function saveSelectedRelay(array $relayData, Context $context): bool
    {
        try {
            if (!$context->cart || !$context->cart->id || !$context->customer || !$context->customer->id) {
                return false;
            }

            // Eliminar selección anterior
            $deleteSql = 'DELETE FROM `' . _DB_PREFIX_ . 'mondialrelay_selected_relay` 
                         WHERE `id_cart` = ' . (int)$context->cart->id;
            Db::getInstance()->execute($deleteSql);

            // Insertar nueva selección
            $insertSql = 'INSERT INTO `' . _DB_PREFIX_ . 'mondialrelay_selected_relay` 
                         (`id_cart`, `id_customer`, `selected_relay_num`, `selected_relay_adr1`, 
                          `selected_relay_city`, `selected_relay_postcode`) 
                         VALUES (' . (int)$context->cart->id . ', ' . (int)$context->customer->id . ', 
                                "' . pSQL($relayData['num'] ?? '') . '", 
                                "' . pSQL($relayData['name'] ?? '') . '", 
                                "' . pSQL($relayData['city'] ?? '') . '", 
                                "' . pSQL($relayData['postcode'] ?? '') . '")';

            $result = Db::getInstance()->execute($insertSql);

            if ($result) {
                $this->debug("Relay saved", array_merge($relayData, ['carrier_id' => $this->carrierId]));
            }

            return $result;

        } catch (\Exception $e) {
            $this->debug("Error saving relay", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Obtiene información específica del carrier
     */
    public function getCarrierInfo(): array
    {
        return [
            'id' => $this->carrierId,
            'name' => $this->carrierConfig['name'],
            'service_type' => $this->carrierConfig['service_type'],
            'theme' => $this->carrierConfig['theme'],
            'country' => $this->carrierConfig['country'],
            'delivery_type' => $this->carrierConfig['delivery_type'],
            'module_info' => $this->getExternalModuleInfo()
        ];
    }
}