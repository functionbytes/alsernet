<?php


namespace Checkout;


require_once dirname(__FILE__) . '/../BaseController.php';
require_once _PS_MODULE_DIR_ . 'alsernetshopping/controllers/front/Carriers/CarrierAvailabilityManager.php';

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
use AlsernetShopping\Carriers\CarrierRegistry;
use AlsernetShopping\Carriers\CarrierAvailabilityManager;

use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use DeliveryOptionsFinder;
use Configuration;
use Language;
use Address;
use Carrier;
use Context;
use Country;
use Module;
use Tools;
use Hook;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CheckoutDeliveryController extends \BaseController
{
    private $giftCost = 0;
    private $need_invoice = false;
    private $need_dni = false;
    private $giftAllowed = false;
    private $recyclablePackAllowed = false;

    public function __construct()
    {
        parent::__construct();
        $this->present = (new CartPresenter($this->context))->present($this->cart, false);
        $this->initializeProperties();
    }

    public function init()
    {
        $context = $this->context;
        $present = $this->present;
        $cart = $this->cart;
        $virtual = $this->cart->isVirtualCart();

        // ✅ CHECK: Skip delivery step for virtual carts
        if (isset($virtual) && $virtual) {
            $cart->step = 'payment';
            $cart->update();

            // Option 1: Use template with redirect
            $context->smarty->assign([
                'is_virtual_cart' => true,
                'skip_delivery' => true,
                'next_step' => 'payment'
            ]);

            return $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/view/virtual-cart-redirect.tpl');
        }

        $products_in_cart_pickup_gc = $cart->haveMultipleProductTypes($cart->id);
        $delivery_options = $this->getDeliveryOptions();
        $carrierRegistry = CarrierRegistry::getInstance();
        $carrierConfig = $carrierRegistry->getCarrierConfig();

        // Validar que el carrier seleccionado sigue estando disponible después de restricciones
        $this->validateSelectedCarrierAvailability($cart, $delivery_options);

        $delivery_option =  current($cart->getDeliveryOption(null, false, false));
        $selectedCarrierInterface = $this->getSelectedCarrierInterface();

        $context->smarty->assign([
            'hookDisplayBeforeCarrier' => Hook::exec('displayBeforeCarrier', ['cart' => $cart]),
            'hookDisplayAfterCarrier' => Hook::exec('displayAfterCarrier', ['cart' => $cart]),
            'id_address' => (int) $cart->id_address_delivery,
            'delivery_options' =>  $delivery_options,
            'products_in_cart_pickup_gc' =>  $products_in_cart_pickup_gc,
            'delivery_option' => $cart->getDeliveryOption(null, false, false),
            'selected_carrier_interface' => $selectedCarrierInterface,
            'selected_carrier' =>  $this->getSelectedCarrierId($cart),
            'selected_payment_option' => $this->getSelectedPaymentOption($cart),
            'recyclable' => (bool) $cart->recyclable,
            'recyclablePackAllowed' => $this->isRecyclablePackAllowed(),
            'delivery_message' => $this->getCartMessage(),
            'gift' => [
                'allowed' => $this->isGiftAllowed($cart),
                'isGift' => (bool) $cart->gift,
                'label' => $this->l('I would like my order to be gift wrapped ', 'checkoutdeliverycontroller') . $this->getGiftCostForLabel(),
                'message' => $cart->gift_message,
            ],
            'carrier_config' => $carrierConfig,
        ]);


        return $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/view/delivery.tpl');
    }

    public function getDeliveryOption($default_country = null, $dontAutoSelectOptions = false, $use_cache = true)
    {
        $cache_id = (int) (is_object($default_country) ? $default_country->id : 0) . '-' . (int) $dontAutoSelectOptions;
        if (isset(static::$cacheDeliveryOption[$cache_id]) && $use_cache) {
            return static::$cacheDeliveryOption[$cache_id];
        }

        $delivery_option_list = $this->getDeliveryOptionList($default_country);

        // The delivery option was selected
        if (isset($this->delivery_option) && $this->delivery_option != '') {
            $delivery_option = json_decode($this->delivery_option, true);
            $validated = true;

            if (is_array($delivery_option)) {
                foreach ($delivery_option as $id_address => $key) {
                    if (!isset($delivery_option_list[$id_address][$key])) {
                        $validated = false;

                        break;
                    }
                }

                if ($validated) {
                    static::$cacheDeliveryOption[$cache_id] = $delivery_option;

                    return $delivery_option;
                }
            }
        }

        if ($dontAutoSelectOptions) {
            return false;
        }

        // No delivery option selected or delivery option selected is not valid, get the better for all options
        $delivery_option = [];
        foreach ($delivery_option_list as $id_address => $options) {
            foreach ($options as $key => $option) {
                if (Configuration::get('PS_CARRIER_DEFAULT') == -1 && $option['is_best_price']) {
                    $delivery_option[$id_address] = $key;

                    break;
                } elseif (Configuration::get('PS_CARRIER_DEFAULT') == -2 && $option['is_best_grade']) {
                    $delivery_option[$id_address] = $key;

                    break;
                } elseif ($option['unique_carrier'] && in_array(Configuration::get('PS_CARRIER_DEFAULT'), array_keys($option['carrier_list']))) {
                    $delivery_option[$id_address] = $key;

                    break;
                }
            }

            reset($options);
            if (!isset($delivery_option[$id_address])) {
                $delivery_option[$id_address] = key($options);
            }
        }

        static::$cacheDeliveryOption[$cache_id] = $delivery_option;

        return $delivery_option;
    }

    private function initializeProperties()
    {
        $cart = $this->cart;

        if ($cart && $cart->id) {
            $this->need_dni = \CheckoutValidationService::checkNeedDNIByProductType($cart)
                || \CheckoutValidationService::checkNeedDNIByCategory($cart)
                || \CheckoutValidationService::checkNeedDNIByCountry($cart);

            $this->need_invoice = \CheckoutValidationService::checkNeedInvoiceByProductType($cart)
                || \CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart);
        }

        $this->giftAllowed = (bool) \Configuration::get('PS_GIFT_WRAPPING');
        $this->giftCost    = (float) \Configuration::get('PS_GIFT_WRAPPING_PRICE');
        $this->recyclablePackAllowed = (bool) \Configuration::get('PS_RECYCLABLE_PACK');

        $customerId = (int) \Context::getContext()->customer->id;
        $psTaxEnabled = (bool) \Configuration::get('PS_TAX');

        $includeByMethod = !\Product::getTaxCalculationMethod($customerId);
        $this->includeTaxes = $psTaxEnabled ? $includeByMethod : false;

        $this->displayTaxesLabel = $psTaxEnabled && ((int) \Configuration::get('PS_TAX_DISPLAY') === 1);
    }

    public function getdeliverys()
    {
        $context = $this->context;
        $cart = $this->cart;
        $customer = $this->customer;
        $id_lang = (int)$this->language->id;

        $authValidation = \ControllerHelper::validateAuthentication($context);
        if ($authValidation) {
            return \ResponseHelper::addressResponse(
                'warning',
                $authValidation['message']
            );
        }

        $addresses = $customer->getSimpleAddresses($id_lang);

        if (empty($addresses)) {
            return \ResponseHelper::addressResponse(
                'warning',
                $this->l('No addresses found.', 'checkoutdeliverycontroller'),
            );
        }

        $configuration = \ControllerHelper::getAddressConfiguration();
        $need_invoice_mandatory = (bool)Configuration::get('PS_INVOICE');
        $show_delivery_address_form = true;
        $show_invoice_address_form = true;
        $form_has_continue_button = true;
        $use_same_address = (int)$cart->id_address_delivery === (int)$cart->id_address_invoice;
        $iso = $context->language->iso_code;

        $context->smarty->assign([
            'addresses' => $addresses,
            'name' => 'id_address_delivery',
            'selected' => (int)$cart->id_address_delivery,
            'type' => 'delivery',
            'configuration' => $configuration,
            'cart_info' => ['id' => $cart->id, 'is_virtual' => $cart->isVirtualCart()],
            'countries' => Country::getCountries($id_lang),
            'need_invoice_mandatory' => $need_invoice_mandatory,
            'modal_need_invoice' => !$this->checkVatNumberIfNeedInvoice(),
            'show_delivery_address_form' => $show_delivery_address_form,
            'show_invoice_address_form' => $show_invoice_address_form,
            'form_has_continue_button' => $form_has_continue_button,
            'use_same_address' => $use_same_address,
            'need_invoice' => $cart->need_invoice,
            'need_dni' => $this->need_dni,
            'current_step' => 'addresses',
            'iso' => $iso,
            'name' => 'id_address_invoice',
            'selected' => (int)$cart->id_address_invoice,
            'type' => 'invoice',
        ]);

        $html_delivery = $context->smarty->fetch('module:checkoutdeliverycontroller/views/templates/front/checkout/partials/addresses/address-selector.tpl');
        $html_invoice = $context->smarty->fetch('module:checkoutdeliverycontroller/views/templates/front/checkout/partials/addresses/address-selector.tpl');

        return [
            'adddress' => $addresses,
            'status' => 'success',
            'message' => $this->l('Addresses loaded successfully.', 'checkoutdeliverycontroller'),
            'html_delivery' => $html_delivery,
            'html_invoice' => $html_invoice,
        ];
    }

    public function stepdelivery()
    {

        $id_carrier = 0;
        $cart = $this->cart;
        $context = $this->context;
        $id_address = (int)$this->cart->id_address_delivery;


        if (!$this->customer || !$this->customer->isLogged()) {
            return [
                'status'  => 'warning',
                'message' => $this->l('Unauthorized access.', 'checkoutdeliverycontroller'),
                'data'    => [],
            ];
        }

        $deliveryOption = \Tools::getValue('delivery_option', []);

        if (is_string($deliveryOption)) {
            $decoded = json_decode($deliveryOption, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $deliveryOption = $decoded;
            } else {
                $deliveryOption = [];
            }
        }


        if (!empty($deliveryOption) && is_array($deliveryOption)) {
            $id_carrier = (int)reset($deliveryOption);
            $firstKey   = key($deliveryOption);

            // IMPORTANTE: No usar la dirección del delivery_option si es diferente a la del carrito
            // Esto puede causar inconsistencias. Siempre usar la dirección actual del carrito.
            if ($firstKey !== null && (int)$firstKey === (int)$cart->id_address_delivery) {
                $id_address = (int)$firstKey;
            } else {
                // Log de advertencia si hay inconsistencia
                $id_address = (int)$cart->id_address_delivery;
            }
        }

        $gift_message = (string)\Tools::getValue('gift_message', '');
        $gift = ($gift_message != '') ? 1 : 0;
        $delivery_message = (string)\Tools::getValue('delivery_message', '');

        // Limpiar parámetros residuales de otros carriers que pueden causar conflictos
        $mondialrelayParam = \Tools::getValue('mondialrelay_selectedRelay', '');
        $correosParam = \Tools::getValue('correos_office', '');
        $storePickupParam = \Tools::getValue('store_pickup', '');

        // Detectar carriers InPost que manejan direcciones automáticamente
        $inpostCarriers = [107, 108, 109, 110, 111]; // IDs de carriers InPost
        $currentCarrierId = $this->getSelectedCarrierId($cart);
        $isChangingFromInpost = in_array($currentCarrierId, $inpostCarriers) && !in_array($id_carrier, $inpostCarriers);
        $isChangingToInpost = !in_array($currentCarrierId, $inpostCarriers) && in_array($id_carrier, $inpostCarriers);

        if ((int)$currentCarrierId > 0 && (int)$currentCarrierId !== (int)$id_carrier) {
            $id_shop = (int)$this->context->shop->id;
            $okPurge = $this->purgePreviousCarrierData(
                (int)$currentCarrierId,
                (int)$cart->id,
                (int)$this->context->customer->id,
                (int)$id_shop
            );
        }

        // Solo cambiar dirección si es diferente Y si la dirección es válida
        if ($id_address > 0 && $id_address !== (int)$cart->id_address_delivery) {
            // Caso especial: Si cambiamos DESDE InPost a otro carrier, restaurar dirección por defecto del cliente
            if ($isChangingFromInpost) {
                $defaultAddress = $this->getCustomerDefaultAddress();
                if ($defaultAddress) {
                    $id_address = (int)$defaultAddress->id;
                    $this->setIdAddressDeliveryInline($id_address);
                } else {
                    // Si no hay dirección por defecto, buscar la primera válida
                    $customerAddresses = $this->context->customer->getSimpleAddresses($this->context->language->id);
                    if (!empty($customerAddresses)) {
                        foreach ($customerAddresses as $addr) {
                            $checkAddress = new \Address($addr['id_address']);
                            if (\Validate::isLoadedObject($checkAddress) && !$checkAddress->deleted) {
                                $id_address = (int)$checkAddress->id;
                                $this->setIdAddressDeliveryInline($id_address);
                                // Marcarla como por defecto si no hay ninguna
                                $checkAddress->default = 1;
                                $checkAddress->update();
                                break;
                            }
                        }
                    } else {
                        // Fallback: mantener dirección actual del carrito
                        $id_address = (int)$cart->id_address_delivery;
                    }
                }
            }
            // Caso especial: Si cambiamos A InPost, permitir que InPost maneje la dirección
            elseif ($isChangingToInpost) {
                // No cambiar la dirección aquí, InPost lo manejará después
                $id_address = (int)$cart->id_address_delivery;
            }
            // Caso normal: validar y cambiar dirección si es válida
            else {
                $address = new \Address($id_address);
                if (\Validate::isLoadedObject($address) && !$address->deleted) {
                    // Verificar que la dirección pertenece al cliente actual
                    if ($address->id_customer == $this->context->customer->id) {
                        $this->setIdAddressDeliveryInline($id_address);
                    } else {
                        // Si la dirección no pertenece al cliente, usar la dirección actual del carrito
                        $id_address = (int)$cart->id_address_delivery;
                    }
                } else {
                    // Si la dirección no es válida, usar la dirección actual del carrito
                    $id_address = (int)$cart->id_address_delivery;
                }
            }
        } else {
            // Asegurar que usamos la dirección actual del carrito si no hay cambio
            $id_address = (int)$cart->id_address_delivery;
        }


        // Validar que tenemos carrier y dirección válidos
        if ($id_address <= 0) {
            return [
                'status'  => 'error',
                'message' => $this->l('Invalid delivery address.', 'checkoutdeliverycontroller'),
            ];
        }

        if ($id_carrier <= 0) {
            return [
                'status'  => 'error',
                'message' => $this->l('Invalid carrier selection.', 'checkoutdeliverycontroller'),
            ];
        }

        // Verificar que el carrier existe y está activo
        $carrier = new \Carrier($id_carrier);
        if (!\Validate::isLoadedObject($carrier) || !$carrier->active) {
            return [
                'status'  => 'error',
                'message' => $this->l('Selected carrier is not available.', 'checkoutdeliverycontroller'),
            ];
        }

        // Intentar establecer la opción de entrega con manejo de errores
        try {
            $result = $this->setDeliveryOptionInline($id_address, (string)$id_carrier);
            if (!$result) {
                return [
                    'status'  => 'error',
                    'message' => $this->l('Failed to set delivery option.', 'checkoutdeliverycontroller'),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status'  => 'error',
                'message' => $this->l('Error setting delivery option.', 'checkoutdeliverycontroller'),
            ];
        }


        $this->setGiftInline($gift, $gift_message);
        $this->updateCartMessage($delivery_message);
        $cart->step = 'payment';
        $cart->update();

        return [
            'status'               => 'success',
            'step'  => $cart->step,
            'selected_carrier_id'  => (int)$id_carrier,
            'selected_address_id'  => (int)$id_address,
            'delivery_message_set' => (string)$this->getCartMessage(),
        ];
    }

    public function setdelivery()
    {

        $id_carrier = Tools::getValue('id_carrier');
        $type = Tools::getValue('type');
        $context = $this->context;
        $cart = $this->cart;
        $id_address = $cart->id_address_delivery;

        $address = new Address($id_address);
        $carrier = new Carrier($id_carrier, $context->language->id);

        $authValidation = \ControllerHelper::validateAuthentication($context);
        if ($authValidation) {
            return $authValidation;
        }

        $carrierValidation = \ControllerHelper::validateCarrierParams($id_carrier);

        if ($carrierValidation) {
            return $carrierValidation;
        }

        //$this->setDeliveryOptionInline($id_address, (string)$id_carrier);
        //$cart->id_carrier = $id_carrier;
        $cart->step = 'delivery';
        $addressData = \ControllerHelper::getAddressData($address, $context);
        $cart->update();


        $requestData = array_merge($addressData, [
            'id_carrier' => $id_carrier,
            'id_address' => $id_address,
            'type' => $type,
            'carrier' => $carrier,
        ]);

        return \ControllerHelper::processCarrierRequest($id_carrier, $requestData, $context);
    }

    public function selectdelivery()
    {

        $context = \Context::getContext();
        $cart    = $context->cart;
        $custId  = (int)$context->customer->id;

        if ($auth = \ControllerHelper::validateAuthentication($context)) {
            die(json_encode($auth));
        }
        if (!$cart || !(int)$cart->id || (int)$cart->id_customer !== $custId) {
            die(json_encode(\ResponseHelper::error('Carrito no válido o no pertenece al usuario')));
        }

        $id_carrier = (int)\Tools::getValue('id_carrier');
        $type       = (string)\Tools::getValue('type'); // 'pickup' | 'home' | ...
        $payload    = \Tools::getValue('payload');

        if ($warn = \ControllerHelper::validateCarrierParams($id_carrier)) {
            die(json_encode($warn));
        }

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];
        } elseif (!is_array($payload)) {
            $payload = [];
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

        if (strlen((string)$payloadJson) > 16384) {
            die(json_encode(\ResponseHelper::warning('Selección demasiado grande')));
        }

        $id_address = (int)$cart->id_address_delivery;

        if ($id_address <= 0) {
            die(json_encode(\ResponseHelper::warning('No hay dirección de entrega seleccionada')));
        }

        $address = new \Address($id_address);

        if (!\Validate::isLoadedObject($address) || $address->deleted) {
            die(json_encode(\ResponseHelper::warning('La dirección de entrega no es válida')));
        }

        $type = $type !== '' ? $type : 'home';

        $currentCarrierId = (int)$this->getSelectedCarrierId($cart);

        if ($currentCarrierId > 0 && $currentCarrierId !== (int)$id_carrier) {
            $id_shop = (int)$this->context->shop->id;
            $okPurge = $this->purgePreviousCarrierData(
                $currentCarrierId,
                (int)$cart->id,
                (int)$context->customer->id,
                (int)$id_shop
            );
        }

        \ControllerHelper::updateCartCarrier($cart, $id_carrier);


        $requestData = array_merge(
            \ControllerHelper::getAddressData($address, $context),
            [
                'id_carrier' => $id_carrier,
                'id_address' => $id_address,
                'type'       => $type,
                'payload'    => $payload,
            ]
        );

        $result = \ControllerHelper::processCarrierSelection($id_carrier, $requestData, $context);

        if (($result['status'] ?? '') === 'success') {

            \ControllerHelper::persistCarrierSelection($context, $requestData, $result);
        }

        return $result;
    }

    protected function isRecyclablePackAllowed()
    {
        return $this->recyclablePackAllowed;
    }

    protected function isGiftAllowed($cart)
    {
        $products_list = $cart->getProducts();

        if ($products_list) {
            foreach ($products_list as $key => $product) {
                if ($product['features']) {
                    foreach ($product['features'] as $feature) {
                        if($feature['id_feature_value'] == 12615){
                            return false;
                        }
                    }
                }
            }
        }
        return $this->giftAllowed;
    }

    private function checkVatNumberIfNeedInvoice()
    {
        $cart = $this->context->cart;

        if (!$cart->id_address_invoice) {
            return false;
        }

        return \CheckoutValidationService::hasValidVATNumber($cart);
    }

    private function getIncludeTaxes()
    {
        return (bool)Configuration::get('PS_TAX');
    }

    private function getDisplayTaxesLabel()
    {
        return (bool)Configuration::get('PS_TAX_DISPLAY');
    }

    private function getSelectedDeliveryOptionInline()
    {
        $cart = $this->context->cart;
        return $cart->getDeliveryOption(null, false, true); // array con ids seleccionados
    }

    private function getCartMessage()
    {
        if ($message = \Message::getMessageByCartId((int)$this->context->cart->id)) {
            return $message['message'];
        }

        return '';
    }

    protected function getGiftCostForLabel()
    {
        if ($this->getGiftCost() != 0) {
            $taxLabel = '';
            $priceFormatter = new PriceFormatter();

            if ($this->getIncludeTaxes() && $this->getDisplayTaxesLabel()) {
                $taxLabel .= $this->l('tax incl.', 'checkoutdeliverycontroller');
            } elseif ($this->getDisplayTaxesLabel()) {
                $taxLabel .= $this->l('tax excl.', 'checkoutdeliverycontroller');
            }

            return $this->l(
                '(additional cost of %giftcost% %taxlabel%)',
                [
                    '%giftcost%' => $priceFormatter->convertAndFormat($this->getGiftCost()),
                    '%taxlabel%' => $taxLabel,
                ],
                'checkoutdeliverycontroller'
            );
        }

        return '';
    }

    protected function getGiftCost()
    {
        return $this->giftCost;
    }

    public function getDeliveryOptions(callable $callback = null)
    {

        $priceFormatter = new PriceFormatter();
        $objectPresenter = new ObjectPresenter();

        $finder = new DeliveryOptionsFinder(
            $this->context,
            $this->context->getTranslator(),
            $objectPresenter,
            $priceFormatter
        );

        $carriers_available = $finder->getDeliveryOptions();

        if (Module::isEnabled('kbgcstorelocatorpickup')) {
            $id_carrier_pickup_gc = 0;
            $id_feature_product_type = 0;
            $id_feature_value_product_type_pickup_gc = '';

            if (Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING') && is_numeric(Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING'))) {
                $id_carrier_pickup_gc = (int)Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING');
            }

            if (Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE') && is_numeric(Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE'))) {
                $id_feature_product_type = (int)Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
            }

            if (Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC')) {
                $id_feature_value_product_type_pickup_gc = Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC');
            }

            if ($id_carrier_pickup_gc && $id_feature_product_type && $id_feature_value_product_type_pickup_gc) {
                $is_pickup_gc = false;

                $cart = Context::getContext()->cart;
                if ($cart) {
                    $products_list = $cart->getProducts();
                    if ($products_list) {
                        foreach ($products_list as $key => $product) {
                            if ($product['features']) {
                                foreach ($product['features'] as $feature) {
                                    if ((int)$feature['id_feature'] == $id_feature_product_type) {
                                        if (strpos(',' . $id_feature_value_product_type_pickup_gc . ',', ',' . $feature['id_feature_value'] . ',') !== false) {
                                            $is_pickup_gc = true;
                                            break 2;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                foreach ($carriers_available as $key => $carrier) {
                    if (strpos(',' . $key . ',', ',' . $id_carrier_pickup_gc . ',') !== false) {
                        if (!$is_pickup_gc) {
                            unset($carriers_available[$key]);
                        }
                    } else {
                        if ($is_pickup_gc) {
                            unset($carriers_available[$key]);
                        }
                    }
                }
            }
        }


        $normalized = [];
        foreach ($carriers_available as $key => $carrier) {
            $cleanId = (int) rtrim($key, ',');
            $normalized[$cleanId] = $carrier;
        }

        $carriers_available = $normalized;

        $availabilityManager = CarrierAvailabilityManager::getInstance();
        $carriers_available = $availabilityManager->filterAvailableCarriers($carriers_available, $cart, $this->context);

        $carrierRegistry = CarrierRegistry::getInstance();

        foreach ($carriers_available as $carrierId => &$carrier) {
            $carrier['name'] = isset($carrier['name']) ? $carrier['name'] : 'Carrier #' . (int)$carrierId;

            $handler = $carrierRegistry->getHandler((int)$carrierId);

            if ($handler && $handler->isEnabled()) {
                $carrier['analytic'] = $handler->getAnalyticName();

                try {
                    $address = new Address($this->context->cart->id_address_delivery);
                    $carrier['extraContent'] = $handler->getExtraContent($address, $this->context);
                } catch (\Exception $e) {
                    $carrier['extraContent'] = '';
                }
            } else {
                switch ((int)$carrierId) {
                    case 39:
                        $carrier['analytic'] = 'Recogida en Guardia Civil';
                        break;
                    case 99:
                        $carrier['analytic'] = 'Envío a domicilio';
                        break;
                    case 66:
                        $carrier['analytic'] = 'Correos';
                        break;
                    case 78:
                        $carrier['analytic'] = 'Recogida en tienda';
                        break;
                    case 101:
                        $carrier['analytic'] = 'Entrega a dirección seleccionada';
                        break;
                    default:
                        $carrier['analytic'] = $callback ? $callback($carrier, $carrierId, $carrier['name']) : 'Otro método de envío';
                        break;
                }
            }
        }


        return $carriers_available;
    }

    private function getSelectedCarrierInterface(): array
    {
        $cart = $this->cart;
        $selectedCarrierId = $this->getSelectedCarrierId($cart);

        if (!$selectedCarrierId) {
            return [
                'status' => 'no_carrier',
                'html' => '',
                'carrier_id' => 0,
                'message' => 'No carrier selected'
            ];
        }

        $this->debug("Auto-loading interface for selected carrier: {$selectedCarrierId}");

        $address = new \Address($cart->id_address_delivery);
        $carrier = new \Carrier($selectedCarrierId, $this->getLanguageId());

        $addressData = \ControllerHelper::getAddressData($address, $this->context);
        $requestData = array_merge($addressData, [
            'id_carrier' => $selectedCarrierId,
            'id_address' => $cart->id_address_delivery,
            'type' => 'auto_load',
            'carrier' => $carrier,
        ]);

        $result = \ControllerHelper::processCarrierRequest($selectedCarrierId, $requestData, $this->context);
        $result['auto_loaded'] = true;
        $result['selected_carrier_id'] = $selectedCarrierId;

        return $result;
    }

    private function getSelectedCarrierId($cart): int
    {

        if (!empty($cart->id_carrier)) {
            return (int)$cart->id_carrier;
        }

        $deliveryOptions = $cart->getDeliveryOption(null, false, false);
        if (!empty($deliveryOptions)) {
            foreach ($deliveryOptions as $addressId => $carrierId) {
                if (!empty($carrierId)) {
                    return (int)$carrierId;
                }
            }
        }

        if (!empty($this->context->cookie->id_carrier)) {
            return (int)$this->context->cookie->id_carrier;
        }

        $availableCarriers = $this->getDeliveryOptions();
        if (!empty($availableCarriers)) {
            $firstCarrierId = array_key_first($availableCarriers);

            $cart->id_carrier = $firstCarrierId;
            $cart->setDeliveryOption([
                $cart->id_address_delivery => $firstCarrierId,
            ]);
            $cart->update();

            return (int)$firstCarrierId;
        }

        return 0;
    }

    private function getSelectedPaymentOption($cart): ?string
    {
        // Check if payment option is stored in cart
        if (!empty($cart->payment)) {
            return $cart->payment;
        }

        // Check payment option in session/cookie
        if (!empty($this->context->cookie->payment)) {
            return $this->context->cookie->payment;
        }

        // Check if payment option is stored in context
        if (isset($this->context->paymentSelected) && !empty($this->context->paymentSelected)) {
            return $this->context->paymentSelected;
        }

        // No payment option selected yet
        return null;
    }

    private function setIdAddressDeliveryInline($id_address)
    {
        $cart = $this->context->cart;
        $cart->updateAddressId((int)$cart->id_address_delivery, (int)$id_address);
        $cart->id_address_delivery = (int)$id_address;
        $cart->step = 'payment';
        $cart->save();

        return true;
    }

    private function setIdAddressInvoiceInline($id_address)
    {
        $cart = $this->context->cart;
        $cart->id_address_invoice = (int)$id_address;
        $cart->save();

        return true;
    }

    private function setDeliveryOptionInline($id_address, $option)
    {
        $cart = $this->context->cart;

        // Logging para debugging

        try {
            // Validar que el carrito es válido
            if (!$cart || !$cart->id || $cart->id <= 0) {
                return false;
            }

            // Validar que la dirección existe y es válida
            $address = new \Address($id_address);
            if (!\Validate::isLoadedObject($address) || $address->deleted) {
                return false;
            }

            // Verificar que la dirección pertenece al cliente del carrito
            if ((int)$address->id_customer !== (int)$cart->id_customer) {
                return false;
            }

            // Validar que el carrier existe y está activo
            $carrier = new \Carrier($option);
            if (!\Validate::isLoadedObject($carrier) || !$carrier->active) {
                return false;
            }

            // Verificar que el carrier está disponible para la zona de la dirección
            $zone = \Address::getZoneById($id_address);
            if (!$carrier->checkCarrierZone($carrier->id, $zone)) {
                return false;
            }

            // Verificar las opciones de entrega actuales del carrito
            $currentDeliveryOptions = $cart->getDeliveryOption(null, false, false);

            // Convertimos a string y añadimos coma al final
            $optionString = (string)$option . ',';
            $newDeliveryOptions = [(int)$id_address => $optionString];


            // IMPORTANTE: setDeliveryOption NO devuelve boolean, es void
            // Verificar ANTES que el carrier esté disponible
            $deliveryOptionList = $cart->getDeliveryOptionList();

            // Verificar si la opción está en la lista de opciones disponibles
            $addressOptions = $deliveryOptionList[$id_address] ?? [];
            $carrierAvailable = false;
            $validOptionKey = null;

            foreach ($addressOptions as $optKey => $optData) {
                if (strpos($optKey, (string)$option . ',') !== false) {
                    $carrierAvailable = true;
                    $validOptionKey = $optKey;
                    break;
                }
            }

            if (!$carrierAvailable) {
                return false;
            }

            // Usar la clave exacta que está disponible
            $finalDeliveryOptions = [(int)$id_address => $validOptionKey];

            // Establecer la opción de entrega (método void, no devuelve boolean)
            $cart->setDeliveryOption($finalDeliveryOptions);

            // Verificar que se estableció correctamente comparando el resultado
            $newDeliveryOptions = $cart->getDeliveryOption(null, false, false);
            $setCorrectly = false;

            if (!empty($newDeliveryOptions)) {
                foreach ($newDeliveryOptions as $addr => $carrierOpt) {
                    if ((int)$addr === (int)$id_address && strpos($carrierOpt, (string)$option . ',') !== false) {
                        $setCorrectly = true;
                        break;
                    }
                }
            }

            if (!$setCorrectly) {
                return false;
            }

            // Actualizar carrier en el carrito
            $cart->id_carrier = (int)$option;
            $cart->step = 'payment';

            $updateResult = $cart->update();
            if (!$updateResult) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function setRecyclableInline($recyclable)
    {
        $this->context->cart->recyclable = (int)$recyclable;

        return $this->context->cart->update();
    }

    private function setGiftInline($gift, $gift_message)
    {
        $this->context->cart->gift = (int)$gift;
        $this->context->cart->gift_message = (string)$gift_message;

        return $this->context->cart->update();
    }

    private function updateCartMessage($messageContent)
    {
        $cart = $this->context->cart;

        if ($messageContent) {
            if ($oldMessage = \Message::getMessageByCartId((int)$cart->id)) {
                $message = new \Message((int)$oldMessage['id_message']);
                $message->message = $messageContent;
                $message->update();
            } else {
                $message = new \Message();
                $message->message = $messageContent;
                $message->id_cart = (int)$cart->id;
                $message->id_customer = (int)$cart->id_customer;
                $message->add();
            }
        } else {
            if ($oldMessage = \Message::getMessageByCartId((int)$cart->id)) {
                $message = new \Message((int)$oldMessage['id_message']);
                $message->delete();
            }
        }

        return true;
    }

    private function processPaymentOptions(array $options, array $conditions, string $action = 'filter'): array
    {

        if (!in_array($action, ['filter', 'remove'])) {
            throw new InvalidArgumentException('El valor de acción debe ser "filter" o "remove".');
        }

        return array_filter($options, function ($option) use ($conditions, $action) {
            foreach ($conditions as $condition) {
                $isValid = true;
                foreach ($condition as $key => $value) {

                    if (is_array($value)) {
                        if (!isset($option[$key]) || !in_array($option[$key], $value)) {
                            $isValid = false;
                            break;
                        }
                    } else {
                        // Validar si el campo coincide exactamente con el valor
                        if (!isset($option[$key]) || $option[$key] !== $value) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    return $action === 'filter';
                }
            }

            return $action === 'remove';
        });
    }

    /**
     * Obtiene la dirección por defecto del cliente
     * Similar a la lógica en CheckoutAddressController
     */
    private function getCustomerDefaultAddress(): ?\Address
    {
        if (!$this->customer || !$this->customer->isLogged()) {
            return null;
        }

        $customerAddresses = $this->customer->getSimpleAddresses($this->context->language->id);
        if (empty($customerAddresses)) {
            return null;
        }

        // Buscar dirección marcada como por defecto
        foreach ($customerAddresses as $addr) {
            $addressObj = new \Address($addr['id_address']);
            if (
                \Validate::isLoadedObject($addressObj) &&
                !$addressObj->deleted &&
                (int)$addressObj->default === 1 &&
                (int)$addressObj->id_customer === (int)$this->customer->id
            ) {
                return $addressObj;
            }
        }

        // Si no hay ninguna por defecto, devolver la primera válida
        foreach ($customerAddresses as $addr) {
            $addressObj = new \Address($addr['id_address']);
            if (
                \Validate::isLoadedObject($addressObj) &&
                !$addressObj->deleted &&
                (int)$addressObj->id_customer === (int)$this->customer->id
            ) {
                return $addressObj;
            }
        }

        return null;
    }

    /**
     * Valida que el carrier seleccionado sigue estando disponible después de aplicar restricciones
     * Si no está disponible, limpia la selección (id_carrier = 0, delivery_option = '')
     */
    private function validateSelectedCarrierAvailability($cart, $delivery_options)
    {
        if (!$cart || !$cart->id) {
            return;
        }

        // Obtener el carrier actualmente seleccionado
        $currentCarrierId = 0;

        // Verificar desde cart->id_carrier
        if (!empty($cart->id_carrier)) {
            $currentCarrierId = (int)$cart->id_carrier;
        }

        // Verificar desde delivery_option si no hay id_carrier
        if (!$currentCarrierId) {
            $deliveryOptions = $cart->getDeliveryOption(null, false, false);
            if (!empty($deliveryOptions)) {
                foreach ($deliveryOptions as $addressId => $carrierId) {
                    if (!empty($carrierId)) {
                        // Extraer ID del carrier (quitar comas, etc.)
                        $currentCarrierId = (int)trim($carrierId, ',');
                        break;
                    }
                }
            }
        }

        // Si no hay carrier seleccionado, no hay nada que validar
        if (!$currentCarrierId) {
            return;
        }

        // Verificar si el carrier está en la lista de opciones disponibles
        $carrierStillAvailable = isset($delivery_options[$currentCarrierId]);

        // Si el carrier ya no está disponible, limpiar la selección
        if (!$carrierStillAvailable) {

            // Limpiar id_carrier del carrito
            $cart->id_carrier = 0;

            // Limpiar delivery_option
            $cart->setDeliveryOption([]);

            // Limpiar de la cookie también
            if (isset($this->context->cookie->id_carrier)) {
                $this->context->cookie->id_carrier = 0;
            }

            // Actualizar el carrito
            $cart->update();
        } else {
        }
    }

    private function purgePreviousCarrierData(int $previousCarrierId, int $id_cart, int $id_customer, int $id_shop): bool
    {

        try {
            $db = \Db::getInstance();
            $affected = 0;

            switch ($previousCarrierId) {
                case 39: // Guardia Civil / Pickup GC
                    // Simplified cleanup - id_cart and id_customer are sufficient
                    $sql = sprintf(
                        'DELETE FROM %s WHERE id_cart = %d AND id_customer = %d',
                        _DB_PREFIX_ . 'kb_gc_pickup_at_store_time',
                        (int)$id_cart,
                        (int)$id_customer
                    );
                    $db->execute($sql);
                    $affected += (int)$db->Affected_Rows();
                    break;

                case 78: // Recogida en tienda (módulo KB)
                    // Simplified cleanup - id_cart and id_customer are sufficient
                    $sql = sprintf(
                        'DELETE FROM %s WHERE id_cart = %d AND id_customer = %d',
                        _DB_PREFIX_ . 'kb_pickup_at_store_time',
                        (int)$id_cart,
                        (int)$id_customer
                    );
                    $db->execute($sql);
                    $affected += (int)$db->Affected_Rows();
                    break;

                case 66: // Correos / CEX oficinas
                    // SELECT de referencia:
                    // SELECT * FROM ps_cex_officedeliverycorreo WHERE id_cart = .. AND id_customer = ..
                    $sql = sprintf(
                        'DELETE FROM %s WHERE id_cart = %d AND id_customer = %d',
                        _DB_PREFIX_ . 'cex_officedeliverycorreo',
                        (int)$id_cart,
                        (int)$id_customer
                    );
                    $db->execute($sql);
                    $affected += (int)$db->Affected_Rows();
                    break;

                default:
                    // Para otros carriers no hacemos nada; es intencional
                    break;
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }


    public function l($string, $specific = false, $locale = null)
    {

        return $this->getModuleTranslation(
            $this->module,
            $string,
            ($specific) ? $specific : $this->name,
            null,
            false,
            $locale
        );
    }


    public  function getModuleTranslation(
        $module,
        $originalString,
        $source,
        $sprintf = null,
        $js = false,
        $locale = null,
        $fallback = true,
        $escape = true
    ) {
        global $_MODULES, $_MODULE, $_LANGADM;

        static $langCache = [];
        static $name = null;

        // $_MODULES is a cache of translations for all module.
        // $translations_merged is a cache of wether a specific module's translations have already been added to $_MODULES
        static $translationsMerged = [];


        $name = $module->name;

        if (null !== $locale) {
            $iso = Language::getIsoByLocale($locale);
        }

        if (empty($iso)) {
            $iso = Context::getContext()->language->iso_code;
        }

        if (!isset($translationsMerged[$name][$iso])) {
            $filesByPriority = [
                // PrestaShop 1.5 translations
                _PS_MODULE_DIR_ . $name . '/translations/' . $iso . '.php',
                // PrestaShop 1.4 translations
                _PS_MODULE_DIR_ . $name . '/' . $iso . '.php',
                // Translations in theme
                _PS_THEME_DIR_ . 'modules/' . $name . '/translations/' . $iso . '.php',
                _PS_THEME_DIR_ . 'modules/' . $name . '/' . $iso . '.php',
            ];
            foreach ($filesByPriority as $file) {
                if (file_exists($file)) {
                    include_once $file;
                    $_MODULES = !empty($_MODULES) ? array_merge($_MODULES, $_MODULE) : $_MODULE;
                }
            }
            $translationsMerged[$name][$iso] = true;
        }


        $string = preg_replace("/\\\*'/", "\'", $originalString);
        $key = md5($string);

        $cacheKey = $name . '|' . $string . '|' . $source . '|' . (int) $js . '|' . $iso;
        if (isset($langCache[$cacheKey])) {
            $ret = $langCache[$cacheKey];
        } else {
            $currentKey = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
            $defaultKey = strtolower('<{' . $name . '}prestashop>' . $source) . '_' . $key;

            if ('controller' == substr($source, -10, 10)) {
                $file = substr($source, 0, -10);
                $currentKeyFile = strtolower('<{' . $name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
                $defaultKeyFile = strtolower('<{' . $name . '}prestashop>' . $file) . '_' . $key;
            }

            if (isset($currentKeyFile) && !empty($_MODULES[$currentKeyFile])) {
                $ret = stripslashes($_MODULES[$currentKeyFile]);
            } elseif (isset($defaultKeyFile) && !empty($_MODULES[$defaultKeyFile])) {
                $ret = stripslashes($_MODULES[$defaultKeyFile]);
            } elseif (!empty($_MODULES[$currentKey])) {
                $ret = stripslashes($_MODULES[$currentKey]);
            } elseif (!empty($_MODULES[$defaultKey])) {
                $ret = stripslashes($_MODULES[$defaultKey]);
            } elseif (!empty($_LANGADM)) {
                // if translation was not found in module, look for it in AdminController or Helpers
                $ret = stripslashes(Translate::getGenericAdminTranslation($string, $key, $_LANGADM));
            } else {
                $ret = stripslashes($string);
            }

            if (
                $sprintf !== null &&
                (!is_array($sprintf) || !empty($sprintf)) &&
                !(count($sprintf) === 1 && isset($sprintf['legacy']))
            ) {
                $ret = Translate::checkAndReplaceArgs($ret, $sprintf);
            }

            if ($js) {
                $ret = addslashes($ret);
            } elseif ($escape) {
                $ret = htmlspecialchars($ret, ENT_COMPAT, 'UTF-8');
            }

            if ($sprintf === null) {
                $langCache[$cacheKey] = $ret;
            }
        }

        if (!is_array($sprintf) && null !== $sprintf) {
            $sprintf_for_trans = [$sprintf];
        } elseif (null === $sprintf) {
            $sprintf_for_trans = [];
        } else {
            $sprintf_for_trans = $sprintf;
        }

        if ($ret === $originalString && $fallback) {
            $ret = Context::getContext()->getTranslator()->trans($originalString, $sprintf_for_trans, null, $locale);
        }

        return $ret;
    }
}
