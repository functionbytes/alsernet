<?php

namespace AlsernetShopping\Carriers;

use Context;
use Address;


class DeliveryAddressHandler extends AbstractCarrierHandler
{
    const CARRIER_ID = 101;
    const CARRIER_NAME = 'Entrega a dirección seleccionada';

    public function __construct(Context $context = null)
    {
        // error_log("DeliveryAddressHandler: Constructor called for carrier 101");
        parent::__construct($context);
        $this->loadConfiguration();
    }

    public function getId(): int
    {
        return self::CARRIER_ID;
    }

    public function getAnalyticName(): string
    {
        return self::CARRIER_NAME;
    }

    protected function loadConfiguration(): void
    {
        parent::loadConfiguration();

        $this->configuration = array_merge($this->configuration, [
            'requires_address' => true,
            'show_change_button' => true,
            'debug' => true  // Activar debug temporalmente
        ]);
    }

    public function validateAvailability(Context $context): array
    {
        // Siempre disponible si hay una dirección de entrega
        $cart = $context->cart;

        if (!$cart || !$cart->id_address_delivery) {
            return [
                'valid' => false,
                'message' => 'No delivery address selected'
            ];
        }

        return [
            'valid' => true,
            'message' => 'Delivery address available'
        ];
    }

    public function processSelection(array $data, Context $context): array
    {
        try {
            $addressId = $data['id_address'] ?? $context->cart->id_address_delivery;

            if (!$addressId) {
                return $this->createErrorResponse('No delivery address provided');
            }

            $address = new Address($addressId);

            if (!$address->id) {
                return $this->createErrorResponse('Invalid delivery address');
            }

            // Preparar datos para el template
            $templateData = [
                'carrier' => $data['carrier'] ?? null,
                'delivery_address' => $address,
                'state_name' => $data['state_name'] ?? '',
                'country_name' => $data['country_name'] ?? ''
            ];

            $html = $this->renderTemplate($templateData);

            return [
                'status' => 'success',
                'html' => $html,
                'carrier_id' => self::CARRIER_ID,
                'address_id' => $address->id,
                'message' => 'Delivery address loaded successfully'
            ];

        } catch (\Exception $e) {
            error_log("DeliveryAddressHandler: Error processing selection - " . $e->getMessage());
            return $this->createErrorResponse('Error loading delivery address: ' . $e->getMessage());
        }
    }

    public function getExtraContent(Address $address, Context $context): string
    {
        // error_log("DeliveryAddressHandler: getExtraContent called for address ID: " . $address->id);

        $templateData = [
            'delivery_address' => $address,
            'state_name' => $address->id_state ? \State::getNameById($address->id_state) : '',
            'country_name' => $address->id_country ? \Country::getNameById($context->language->id, $address->id_country) : ''
        ];

        $templatePath = $this->getTemplatePath();
        // error_log("DeliveryAddressHandler: Template path: " . $templatePath);
        // error_log("DeliveryAddressHandler: Template data: " . json_encode([
            //     'address_id' => $address->id,
            //     'state_name' => $templateData['state_name'],
            //     'country_name' => $templateData['country_name']
            // ]));

        return $this->renderTemplate($templatePath, $templateData);
    }

    public function processForm($requestData, Context $context): array
    {
        // error_log("DeliveryAddressHandler: processForm called for carrier 101");
        // Para delivery address, no hay formulario específico que procesar
        // Solo devolver la interfaz estándar
        $address = isset($requestData['delivery_address']) ? $requestData['delivery_address'] : new \Address($requestData['id_address']);

        return [
            'status' => 'success',
            'html' => $this->getExtraContent($address, $context),
            'id_carrier' => $this->getId(),
            'id_address' => $requestData['id_address'],
            'carrier_id' => $this->getId(),
            'address_id' => $requestData['id_address'],
            'message' => 'Delivery address interface loaded successfully'
        ];
    }

    public function getRequiredAssets(): array
    {
        return [
            [
                'type' => 'css',
                'path' => 'modules/alsernetshopping/views/css/front/checkout/carriers/delivery-address-carrier.css',
                'priority' => 100
            ],
            'js' => [
                'modules/alsernetshopping/views/js/front/checkout/steps/delivery/carriers/delivery-address-carrier.js'
            ]
        ];
    }

    public function getTemplatePath(): string
    {
        return 'module:alsernetshopping/views/templates/front/checkout/partials/delivery/carriers/101_delivery_address/interface.tpl';
    }

    public function isEnabled(): bool
    {
        return $this->configuration['enabled'] ?? true;
    }

    public function cleanup(): void
    {
        // No hay recursos específicos que limpiar
        parent::cleanup();
    }

    protected function getDefaultConfig(): array
    {
        return [
            'enabled' => true,
            'requires_address' => true,
            'show_change_button' => true,
            'cache_ttl' => 3600
        ];
    }

    private function createErrorResponse(string $message): array
    {
        return [
            'status' => 'error',
            'message' => $message,
            'carrier_id' => self::CARRIER_ID,
            'html' => '<div class="alert alert-danger">' . htmlspecialchars($message) . '</div>'
        ];
    }

}