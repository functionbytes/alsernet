<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;
use Configuration;

/**
 * Handler independiente para InPost (Carrier 98)
 * Para casos donde InPost tiene su propio módulo separado
 *
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class InPostIndependentHandler extends ExternalModuleCarrierHandler
{
    const CARRIER_ID = 98;
    const CARRIER_NAME = 'InPost Punto Recogida';

    private $inpostConfig = [];

    /**
     * Constructor
     */
    public function __construct(Context $context = null)
    {
        parent::__construct($context);
        $this->loadInPostIndependentConfiguration();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): int
    {
        return self::CARRIER_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function getAnalyticName(): string
    {
        return self::CARRIER_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExternalModuleName(): string
    {
        // Intentar detectar módulo InPost independiente
        $possibleModules = ['inpost', 'inpostpickup', 'inpostlocker'];

        foreach ($possibleModules as $moduleName) {
            if (\Module::isEnabled($moduleName)) {
                return $moduleName;
            }
        }

        // Fallback a mondialrelay si no hay módulo independiente
        return 'mondialrelay';
    }

    /**
     * Carga configuración específica de InPost independiente
     */
    private function loadInPostIndependentConfiguration(): void
    {
        // Configuración específica si hay módulo InPost independiente
        if ($this->externalModuleName !== 'mondialrelay') {
            $this->inpostConfig = [
                'api_key' => Configuration::get('INPOST_API_KEY'),
                'api_url' => Configuration::get('INPOST_API_URL', 'https://api-shipx-pl.easypack24.net/v1/'),
                'organization_id' => Configuration::get('INPOST_ORGANIZATION_ID'),
                'service_type' => 'pickup_point',
                'country' => 'ES',
            ];
        } else {
            // Fallback usando configuración MondialRelay
            $this->inpostConfig = [
                'webservice_enseigne' => Configuration::get('MONDIALRELAY_WEBSERVICE_ENSEIGNE'),
                'webservice_key' => Configuration::get('MONDIALRELAY_WEBSERVICE_KEY'),
                'delivery_mode' => 'ESP',
                'delivery_type' => 'IP',
                'service_type' => 'pickup_point',
                'country' => 'ES',
            ];
        }

        $this->debug("InPost independent configuration loaded", [
            'carrier_id' => self::CARRIER_ID,
            'module' => $this->externalModuleName,
            'config' => $this->inpostConfig
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getExternalModuleConfig(): array
    {
        if (!$this->moduleEnabled) {
            return [];
        }

        $baseConfig = [
            'carrier_id' => self::CARRIER_ID,
            'service_type' => 'pickup_point',
            'country_iso' => $this->context->country->iso_code,
            'language_iso' => $this->context->language->iso_code,
        ];

        return array_merge($baseConfig, $this->inpostConfig);
    }

    /**
     * {@inheritdoc}
     */
    protected function processExternalModuleData(array $data, Context $context): array
    {
        try {
            // Verificar disponibilidad del servicio
            if (!$this->isInPostServiceAvailable()) {
                return [
                    'status' => 'error',
                    'message' => 'InPost service not available or not configured'
                ];
            }

            // Procesar datos específicos del carrier
            $carrierData = [
                'delivery_address' => $data['delivery_address'],
                'state_name' => $data['state_name'] ?? '',
                'country_name' => $data['country_name'] ?? '',
                'carrier' => $data['carrier'],
                'carrier_id' => self::CARRIER_ID,
                'inpost_config' => $this->getExternalModuleConfig(),
                'widget_config' => $this->getWidgetConfig(),
                'selected_point' => $this->getSelectedPoint($context),
                'service_info' => $this->getServiceInfo(),
            ];

            return [
                'status' => 'success',
                'data' => $carrierData,
                'message' => 'InPost data processed successfully'
            ];

        } catch (\Exception $e) {
            $this->debug("Error processing InPost independent data", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Error processing InPost data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si el servicio InPost está disponible
     */
    private function isInPostServiceAvailable(): bool
    {
        if ($this->externalModuleName !== 'mondialrelay') {
            // Módulo InPost independiente
            return !empty($this->inpostConfig['api_key']);
        } else {
            // Usando MondialRelay como fallback
            return !empty($this->inpostConfig['webservice_key']);
        }
    }

    /**
     * Obtiene configuración del widget
     */
    private function getWidgetConfig(): array
    {
        if ($this->externalModuleName !== 'mondialrelay') {
            // Configuración para widget InPost independiente
            return [
                'api_key' => $this->inpostConfig['api_key'] ?? '',
                'organization_id' => $this->inpostConfig['organization_id'] ?? '',
                'country' => $this->inpostConfig['country'] ?? 'ES',
                'language' => $this->context->language->iso_code,
                'service_type' => 'pickup_point',
                'container_id' => 'inpost-widget-container-98'
            ];
        } else {
            // Configuración para widget MondialRelay (fallback)
            return [
                'Target' => '#inpost-widget-container-98',
                'Brand' => $this->inpostConfig['webservice_enseigne'] ?? 'BDTEST13',
                'Country' => 'ES',
                'Language' => $this->context->language->iso_code,
                'Theme' => 'inpost',
                'ColLivMod' => 'ESP',
                'DeliveryType' => 'IP',
                'OnParcelShopSelected' => 'onInPostIndependentSelected_98'
            ];
        }
    }

    /**
     * Obtiene información del servicio
     */
    private function getServiceInfo(): array
    {
        return [
            'name' => self::CARRIER_NAME,
            'type' => 'pickup_point',
            'features' => [
                '24/7 availability',
                'Secure pickup',
                'Mobile notifications',
                'Easy location'
            ],
            'supported_countries' => ['ES', 'PL', 'UK', 'IT', 'FR'],
            'module_type' => $this->externalModuleName
        ];
    }

    /**
     * Obtiene punto seleccionado
     */
    private function getSelectedPoint(Context $context): ?array
    {
        if (!$context->cart || !$context->cart->id) {
            return null;
        }

        // Intentar obtener desde cookie/sesión específica
        $cookieKey = 'inpost_selected_98';
        if (isset($context->cookie->$cookieKey)) {
            $selectedData = json_decode($context->cookie->$cookieKey, true);
            return is_array($selectedData) ? $selectedData : null;
        }

        // Fallback a tabla MondialRelay si aplica
        if ($this->externalModuleName === 'mondialrelay') {
            $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'mondialrelay_selected_relay` 
                    WHERE `id_cart` = ' . (int)$context->cart->id . '
                    AND `id_customer` = ' . (int)$context->customer->id;

            $result = \Db::getInstance()->getRow($sql);

            if ($result) {
                return [
                    'point_id' => $result['selected_relay_num'],
                    'point_name' => $result['selected_relay_adr1'],
                    'point_address' => $result['selected_relay_adr1'],
                    'point_city' => $result['selected_relay_city'],
                    'point_postcode' => $result['selected_relay_postcode'],
                ];
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredAssets(): array
    {
        $assets = parent::getRequiredAssets();

        // Assets específicos de InPost independiente
        $inpostAssets = [
            [
                'type' => 'css',
                'path' => 'modules/alsernetshopping/views/css/front/carriers/inpost-independent.css',
                'priority' => 100
            ],
            [
                'type' => 'js',
                'path' => 'modules/alsernetshopping/views/js/front/checkout/carriers/InPostIndependentCarrier.js',
                'priority' => 200
            ]
        ];

        // Widget específico según el módulo
        if ($this->externalModuleName !== 'mondialrelay') {
            // Widget InPost independiente
            $inpostAssets[] = [
                'type' => 'js',
                'path' => 'https://geowidget.inpost.pl/inpost-geowidget.js',
                'priority' => 150
            ];
        } else {
            // Widget MondialRelay como fallback
            $inpostAssets[] = [
                'type' => 'js',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js',
                'priority' => 150
            ];
        }

        return array_merge($assets, $inpostAssets);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath(): string
    {
        return 'module:alsernetshopping/views/templates/front/checkout/partials/delivery/carriers/107_inpost/interface.tpl';
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
        if (!$this->isInPostServiceAvailable()) {
            return [
                'valid' => false,
                'message' => 'InPost service not configured'
            ];
        }

        // Verificar país soportado
        $supportedCountries = ['ES', 'PL', 'UK', 'IT', 'FR'];
        if (!in_array($context->country->iso_code, $supportedCountries)) {
            return [
                'valid' => false,
                'message' => 'InPost not available in this country'
            ];
        }

        return [
            'valid' => true,
            'message' => 'InPost independent service is available'
        ];
    }

    /**
     * Guarda punto seleccionado
     */
    public function saveSelectedPoint(array $pointData, Context $context): bool
    {
        try {
            // Guardar en cookie/sesión
            $cookieKey = 'inpost_selected_98';
            $context->cookie->$cookieKey = json_encode($pointData);

            $this->debug("InPost independent point saved", array_merge($pointData, ['carrier_id' => self::CARRIER_ID]));
            return true;

        } catch (\Exception $e) {
            $this->debug("Error saving InPost independent point", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(): void
    {
        parent::cleanup();

        // Limpiar datos específicos
        $cookieKey = 'inpost_selected_98';
        if (isset($this->context->cookie->$cookieKey)) {
            unset($this->context->cookie->$cookieKey);
        }

        $this->debug("InPost independent cleanup completed");
    }
}