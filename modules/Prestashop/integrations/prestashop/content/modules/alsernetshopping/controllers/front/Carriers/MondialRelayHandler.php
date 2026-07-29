<?php

namespace AlsernetShopping\Carriers;

use Address;
use Context;
use Configuration;
use Tools;

/**
 * Handler específico para MondialRelay (Carrier ID: 101)
 * Gestiona la selección de puntos de entrega MondialRelay
 *
 * @package AlsernetShopping\Carriers
 * @version 1.0.0
 * @since 2025-08-16
 */
class MondialRelayHandler extends AbstractCarrierHandler
{
    const CARRIER_ID = 100;  // Cambiado de 101 a 100 (MondialRelay real)
    const CARRIER_NAME = 'MondialRelay';

    private $mondialRelayConfig = [];

    /**
     * Constructor
     */
    public function __construct(Context $context = null)
    {
        parent::__construct($context);
        $this->loadMondialRelayConfiguration();
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
    protected function loadConfiguration(): void
    {
        parent::loadConfiguration();

        // Configuración específica de MondialRelay
        $this->configuration = array_merge($this->configuration, [
            'enabled' => (bool)Configuration::get('MONDIALRELAY_ENABLED', true),
            'api_key' => Configuration::get('MONDIALRELAY_API_KEY'),
            'brand_id' => Configuration::get('MONDIALRELAY_BRAND_ID'),
            'widget_enabled' => (bool)Configuration::get('MONDIALRELAY_WIDGET_ENABLED', true),
            'max_points' => (int)Configuration::get('MONDIALRELAY_MAX_POINTS', 10),
            'search_radius' => (int)Configuration::get('MONDIALRELAY_SEARCH_RADIUS', 15),
        ]);
    }

    /**
     * Carga configuración específica de MondialRelay
     */
    private function loadMondialRelayConfiguration(): void
    {
        $this->mondialRelayConfig = [
            'widget_config' => [
                'brand' => $this->configuration['brand_id'] ?? 'BDTEST13',
                'language' => $this->context->language->iso_code,
                'country' => $this->context->country->iso_code,
                'target' => 'mondialrelay-widget-container',
                'delivery_mode' => '24R',
            ],
            'api_endpoints' => [
                'search_points' => 'https://api.mondialrelay.com/Web_Services.asmx',
                'point_details' => 'https://api.mondialrelay.com/Web_Services.asmx',
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return parent::isEnabled() && !empty($this->configuration['api_key']);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtraContent(Address $address, Context $context): string
    {
        try {
            $templateData = [
                'carrier_id' => self::CARRIER_ID,
                'delivery_address' => $address,
                'state_name' => $address->id_state ? \State::getNameById($address->id_state) : '',
                'country_name' => $address->id_country ? \Country::getNameById($context->language->id, $address->id_country) : '',
                'mondialrelay_config' => $this->mondialRelayConfig,
                'widget_enabled' => $this->configuration['widget_enabled'],
                'max_points' => $this->configuration['max_points'],
                'search_radius' => $this->configuration['search_radius'],
            ];

            return $this->renderTemplate($this->getTemplatePath(), $templateData);
        } catch (\Exception $e) {
            $this->debug("Error generating extraContent", ['error' => $e->getMessage()]);
            return $this->getErrorTemplate('Error loading MondialRelay interface');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processSelection(array $data, Context $context): array
    {
        try {
            $this->debug("Processing MondialRelay selection", $data);

            // Validar datos de entrada
            $validation = $this->validateData($data, ['id_carrier', 'delivery_address']);
            if (!$validation['valid']) {
                return $this->createResponse('error', '', [], implode(', ', $validation['errors']));
            }

            $address = $data['delivery_address'];
            $carrier = $data['carrier'];

            // Verificar si hay punto seleccionado en sesión
            $selectedPoint = $this->getSelectedPoint($context);

            $templateData = [
                'delivery_address' => $address,
                'state_name' => $data['state_name'] ?? '',
                'country_name' => $data['country_name'] ?? '',
                'carrier' => $carrier,
                'mondialrelay_config' => $this->mondialRelayConfig,
                'selected_point' => $selectedPoint,
                'widget_enabled' => $this->configuration['widget_enabled'],
            ];

            $html = $this->renderTemplate($this->getTemplatePath(), $templateData);

            return $this->createResponse('success', $html, [
                'carrier_id' => self::CARRIER_ID,
                'selected_point' => $selectedPoint,
                'widget_config' => $this->mondialRelayConfig['widget_config'],
            ]);

        } catch (\Exception $e) {
            $this->debug("Error processing MondialRelay selection", ['error' => $e->getMessage()]);
            return $this->createResponse('error', '', [], 'Error processing MondialRelay selection: ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRequiredAssets(): array
    {
        $baseAssets = [
            [
                'type' => 'css',
                'path' => 'modules/alsernetshopping/views/css/front/carriers/mondialrelay.css',
                'priority' => 100
            ],
            [
                'type' => 'js',
                'path' => 'modules/alsernetshopping/views/js/front/checkout/carriers/MondialRelayCarrier.js',
                'priority' => 200
            ]
        ];

        // Widget MondialRelay oficial si está habilitado
        $widgetAssets = $this->getWidgetAssets();

        return array_merge($baseAssets, $widgetAssets);
    }

    /**
     * Obtiene assets del widget oficial de MondialRelay
     */
    private function getWidgetAssets(): array
    {
        if (!$this->configuration['widget_enabled']) {
            return [];
        }

        return [
            [
                'type' => 'js',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/jquery.plugin.mondialrelay.parcelshoppicker.min.js',
                'priority' => 150
            ],
            [
                'type' => 'css',
                'path' => 'https://widget.mondialrelay.com/parcelshop-picker/css/mondialrelay.css',
                'priority' => 90
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplatePath(): string
    {
        return 'module:alsernetshopping/views/templates/front/checkout/partials/delivery/carriers/100_mondialrelay/interface.tpl';
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

        // Validaciones específicas de MondialRelay
        if (empty($this->configuration['api_key'])) {
            return [
                'valid' => false,
                'message' => 'MondialRelay API key not configured'
            ];
        }

        // Verificar que el país esté soportado
        $supportedCountries = ['ES', 'FR', 'BE', 'LU']; // Países soportados por MondialRelay
        if (!in_array($context->country->iso_code, $supportedCountries)) {
            return [
                'valid' => false,
                'message' => 'MondialRelay not available in this country'
            ];
        }

        return [
            'valid' => true,
            'message' => 'MondialRelay is available'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function cleanup(): void
    {
        // Limpiar datos de sesión de MondialRelay
        if (isset($this->context->cookie->mondialrelay_selected_point)) {
            unset($this->context->cookie->mondialrelay_selected_point);
        }

        $this->debug("MondialRelay cleanup completed");
    }

    /**
     * Obtiene el punto seleccionado desde la sesión
     */
    private function getSelectedPoint(Context $context): ?array
    {
        if (isset($context->cookie->mondialrelay_selected_point)) {
            $selectedData = json_decode($context->cookie->mondialrelay_selected_point, true);
            return is_array($selectedData) ? $selectedData : null;
        }
        return null;
    }

    /**
     * Guarda el punto seleccionado en la sesión
     */
    public function saveSelectedPoint(array $pointData, Context $context): bool
    {
        try {
            $context->cookie->mondialrelay_selected_point = json_encode($pointData);
            $this->debug("MondialRelay point saved", $pointData);
            return true;
        } catch (\Exception $e) {
            $this->debug("Error saving MondialRelay point", ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Busca puntos MondialRelay cerca de una dirección
     */
    public function searchNearbyPoints(string $postcode, string $city = '', string $country = 'ES'): array
    {
        try {
            // TODO: Implementar llamada real a API MondialRelay
            // Por ahora devolver datos mock para testing

            $this->debug("Searching MondialRelay points", [
                'postcode' => $postcode,
                'city' => $city,
                'country' => $country
            ]);

            // Mock data para testing
            return $this->getMockPoints($postcode, $city);

        } catch (\Exception $e) {
            $this->debug("Error searching MondialRelay points", ['error' => $e->getMessage()]);
            return [
                'status' => 'error',
                'message' => 'Error searching pickup points: ' . $e->getMessage(),
                'points' => []
            ];
        }
    }

    /**
     * Datos mock para testing (reemplazar con API real)
     */
    private function getMockPoints(string $postcode, string $city): array
    {
        return [
            'status' => 'success',
            'message' => 'Points found successfully',
            'points' => [
                [
                    'id' => 'MR001',
                    'name' => 'MondialRelay - Centro Comercial',
                    'address' => 'Calle Mayor, 123',
                    'postcode' => $postcode,
                    'city' => $city ?: 'Madrid',
                    'distance' => '0.5 km',
                    'hours' => 'L-V: 9:00-21:00, S: 10:00-22:00',
                    'available' => true
                ],
                [
                    'id' => 'MR002',
                    'name' => 'MondialRelay - Estación Metro',
                    'address' => 'Plaza del Sol, 45',
                    'postcode' => $postcode,
                    'city' => $city ?: 'Madrid',
                    'distance' => '1.2 km',
                    'hours' => 'L-D: 7:00-23:00',
                    'available' => true
                ]
            ]
        ];
    }

    /**
     * Genera template de error
     */
    private function getErrorTemplate(string $message): string
    {
        return '<div class="alert alert-warning">
            <i class="fa-solid fa-exclamation-triangle me-2"></i>
            ' . htmlspecialchars($message) . '
        </div>';
    }

    /**
     * Obtiene configuración del widget para JavaScript
     */
    public function getWidgetConfig(): array
    {
        return $this->mondialRelayConfig['widget_config'];
    }
}