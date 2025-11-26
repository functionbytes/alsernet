<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;
use Configuration;
use Db;

class InPostCarrierHandler extends ExternalModuleCarrierHandler
{
    const INPOST_PUNTO_PACK = 107;
    const INPOST_LOCKER = 108;

    private $carrierConfig = [];
    private $deliveryMode = 'ESP'; // España
    private $deliveryType = 'IP';  // InPost

    public function __construct(int $carrierId, Context $context = null)
    {
        $this->carrierId = $carrierId;
        parent::__construct($context);
        $this->loadInPostConfiguration();
    }

    public function getId(): int
    {
        return $this->carrierId;
    }

    public function getAnalyticName(): string
    {
        switch ($this->carrierId) {
            case self::INPOST_PUNTO_PACK:
                return 'InPost Punto Pack';
            case self::INPOST_LOCKER:
                return 'InPost Locker';
            default:
                return 'InPost';
        }
    }

    protected function getExternalModuleName(): string
    {
        return 'mondialrelay';
    }

    private function loadInPostConfiguration(): void
    {
        // Cargar configuración del carrier desde la BD
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_carrier_method` 
                WHERE `id_carrier` = ' . (int)$this->carrierId;

        $result = Db::getInstance()->getRow($sql);

        if ($result) {
            $this->carrierConfig = [
                'delivery_mode' => $result['delivery_mode'],
                'delivery_type' => $result['delivery_type'],
                'insurance_level' => $result['insurance_level']
            ];
        } else {
            // Configuración por defecto según el carrier
            $this->carrierConfig = [
                'delivery_mode' => $this->deliveryMode,
                'delivery_type' => $this->deliveryType,
                'insurance_level' => $this->carrierId === self::INPOST_LOCKER ? '1' : '0'
            ];
        }

        $this->debug("InPost configuration loaded", [
            'carrier_id' => $this->carrierId,
            'config' => $this->carrierConfig
        ]);
    }

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
            'country_iso' => $this->context->country->iso_code,
            'language_iso' => $this->context->language->iso_code,
        ];

        // Configuración específica de InPost
        $inpostConfig = [
            'carrier_id' => $this->carrierId,
            'delivery_mode' => $this->carrierConfig['delivery_mode'],
            'delivery_type' => $this->carrierConfig['delivery_type'],
            'insurance_level' => $this->carrierConfig['insurance_level'],
            'widget_theme' => 'inpost', // Tema específico de InPost
            'service_type' => $this->carrierId === self::INPOST_LOCKER ? 'locker' : 'punto_pack',
        ];

        return array_merge($baseConfig, $inpostConfig);
    }

    protected function processExternalModuleData(array $data, Context $context): array
    {
        try {
            // Verificar que el módulo MondialRelay esté disponible
            if (!$this->hasExternalMethod('validateCarrierConfiguration')) {
                return [
                    'status' => 'error',
                    'message' => 'MondialRelay module methods not available'
                ];
            }

            // Procesar datos específicos del carrier
            $carrierData = [
                'delivery_address' => $data['delivery_address'],
                'state_name' => $data['state_name'] ?? '',
                'country_name' => $data['country_name'] ?? '',
                'carrier' => $data['carrier'],
                'carrier_id' => $this->carrierId,
                'mondialrelay_config' => $this->getExternalModuleConfig(),
                'inpost_config' => $this->carrierConfig,
                'widget_config' => $this->getWidgetConfig(),
                'selected_relay' => $this->getSelectedRelay($context),
            ];

            return [
                'status' => 'success',
                'data' => $carrierData,
                'message' => 'InPost data processed successfully'
            ];

        } catch (\Exception $e) {
            $this->debug("Error processing InPost data", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Error processing InPost data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene configuración del widget para InPost
     */
    private function getWidgetConfig(): array
    {
        $config = $this->getExternalModuleConfig();

        return [
            'Target' => '#inpost-widget-container-' . $this->carrierId,
            'Brand' => $config['webservice_enseigne'] ?? 'BDTEST13',
            'Country' => $config['country_iso'] ?? 'ES',
            'Language' => $config['language_iso'] ?? 'es',
            'Theme' => 'inpost',
            'ColLivMod' => $this->carrierConfig['delivery_mode'],
            'NbResults' => 10,
            'ShowResultsOnMap' => $config['display_map'],
            'DeliveryType' => $this->carrierConfig['delivery_type'],
            'InsuranceLevel' => $this->carrierConfig['insurance_level'],
            'OnParcelShopSelected' => 'onInPostSelected_' . $this->carrierId
        ];
    }

    /**
     * Obtiene el punto seleccionado desde la sesión/BD
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

        // Assets específicos de InPost
        $inpostAssets = [
            [
                'type' => 'css',
                'path' => 'modules/alsernetshopping/views/css/front/carriers/inpost.css',
                'priority' => 100
            ],
            [
                'type' => 'js',
                'path' => 'modules/alsernetshopping/views/js/front/checkout/carriers/InPostCarrier.js',
                'priority' => 200
            ]
        ];

        // Widget de MondialRelay/InPost si está configurado
        if ($this->configuration['widget_enabled'] ?? true) {
            $inpostAssets[] = [
                'type' => 'js',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js',
                'priority' => 150
            ];
            $inpostAssets[] = [
                'type' => 'css',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/css/mondialrelay.css',
                'priority' => 90
            ];
        }

        return array_merge($assets, $inpostAssets);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath(): string
    {
        $carrierId = $this->carrierId;
        return "module:alsernetshopping/views/templates/front/checkout/partials/delivery/carriers/{$carrierId}_inpost/interface.tpl";
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

        // Validaciones específicas de InPost
        $config = $this->getExternalModuleConfig();

        if (empty($config['webservice_key'])) {
            return [
                'valid' => false,
                'message' => 'MondialRelay/InPost API key not configured'
            ];
        }

        // Verificar que el país esté soportado por InPost
        $supportedCountries = ['ES', 'PL', 'UK', 'FR', 'IT'];
        if (!in_array($context->country->iso_code, $supportedCountries)) {
            return [
                'valid' => false,
                'message' => 'InPost not available in this country'
            ];
        }

        return [
            'valid' => true,
            'message' => 'InPost is available'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(): void
    {
        parent::cleanup();

        // Limpiar datos específicos de InPost
        if (isset($this->context->cookie->{'inpost_selected_' . $this->carrierId})) {
            unset($this->context->cookie->{'inpost_selected_' . $this->carrierId});
        }

        $this->debug("InPost cleanup completed", ['carrier_id' => $this->carrierId]);
    }

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
                $this->debug("InPost relay saved", array_merge($relayData, ['carrier_id' => $this->carrierId]));
            }

            return $result;

        } catch (\Exception $e) {
            $this->debug("Error saving InPost relay", ['error' => $e->getMessage()]);
            return false;
        }
    }
}