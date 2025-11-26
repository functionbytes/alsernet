<?php


namespace Checkout;

require_once dirname(__FILE__) . '/../BaseController.php';
// CheckoutStoreLocatorService eliminado - lógica movida a handlers individuales

use Kbgcstorelocatorpickup;
use Kbstorelocatorpickup;

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Core\Checkout\ConditionsToApproveFinderCore;
use PrestaShop\PrestaShop\Adapter\Entity\PaymentOptionsFinder;
use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use DeliveryOptionsFinderCore;
use Address;
use AddressFormat;
use Carrier;
use Cart;
use Configuration;
use Context;
use Country;
use Db;
use Exception;
use Hook;
use Language;
use Module;
use Product;
use State;
use Store;
use Tools;
use Translate;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CheckoutPaymentController extends \BaseController
{

    public function __construct()
    {
        parent::__construct();
        $this->present = (new CartPresenter($this->context))->present($this->cart, false);
    }

    public function init()
    {
        // Free orders are now handled by PrestaShop's native system via PaymentOptionsFinder::findFree()

        // Load HiPay resources if the module is active
        $this->loadHipayMedia();

        $context = $this->context;
        $cart = $this->present;
        $id_cart = (int) $this->context->cart->id;
        $id_customer = (int) $this->context->customer->id;
        $id_lang = (int) $this->context->language->id;
        $id_carrier = (int) $this->context->cart->id_carrier;
        $iso = strtolower($this->context->language->iso_code);

        $selected_payment_option = Tools::getValue('payment_option');
        if (!$selected_payment_option) {
            $selected_payment_option = $this->context->cookie->selected_payment_option ?? null;
        }

        $deliveryOptionsList = $this->context->cart->getDeliveryOptionList();
        $deliveryOptionKeyList = $this->context->cart->getDeliveryOption(null, false, false);

        $carrierInstances = [];

        foreach ($deliveryOptionsList as $addressDelivery => $options) {
            foreach ($options as $optionKey => $option) {
                if (isset($option['carrier_list']) && is_array($option['carrier_list'])) {
                    foreach ($option['carrier_list'] as $carrierData) {
                        if (isset($carrierData['instance'])) {
                            $carrierInstances[$optionKey][] = $carrierData['instance'];
                        }
                    }
                }
            }
        }

        $selectedCarrierInstances = [];

        foreach ($deliveryOptionKeyList as $addressDelivery => $selectedOptionKey) {
            if (isset($carrierInstances[$selectedOptionKey])) {
                $selectedCarrierInstances = $carrierInstances[$selectedOptionKey];
                break;
            }
        }

        $billingAddress = new Address($this->context->cart->id_address_invoice);

        if (!Validate::isLoadedObject($billingAddress)) {
            // Si no hay dirección de facturación, usar la de envío
            $this->context->cart->id_address_invoice = $this->context->cart->id_address_delivery;
            $this->context->cart->update();
            $billingAddress = new Address($this->context->cart->id_address_delivery);
        }

        // Calculate if order is free BEFORE using PaymentOptionsFinder
        $isFree = 0 == (float) $context->cart->getOrderTotal(true, Cart::BOTH);

        // Use PrestaShop's native PaymentOptionsFinder with free order support
        $paymentOptionsFinder = new PaymentOptionsFinder();

        if ($isFree) {
            // For free orders, use only the native free order option
            $paymentOptions = $paymentOptionsFinder->present($isFree);
            $allPaymentOptions = [];

            if (!empty($paymentOptions)) {
                $flattened = array_filter(array_values($paymentOptions), 'is_array');
                if (!empty($flattened)) {
                    $allPaymentOptions = call_user_func_array('array_merge', $flattened);
                }
            }

            // Skip all filtering for free orders - use native PaymentOption as-is
            $filteredOptions = $allPaymentOptions;

            // error_log('🆓 Free order detected - using native PaymentOptionsFinder::findFree()');
        } else {
            // For paid orders, use normal flow with filtering
            $paymentOptions = $paymentOptionsFinder->present($isFree);

            $totalCart = $cart['totals']['total']['amount'];
            $iso = strtolower($this->context->language->iso_code);
            $lottery = $cart['lottery'];
            $card = $cart['card'];
            $armero = $cart['armero'];
            $cartucho = $cart['cartucho'];
            $armas = $cart['armas'];
            $armas_balines = $cart['armas_balines'];
            $licencia = $cart['licencia'];

            $allPaymentOptions = [];

            if (!empty($paymentOptions)) {
                $flattened = array_filter(array_values($paymentOptions), 'is_array');
                if (!empty($flattened)) {
                    $allPaymentOptions = call_user_func_array('array_merge', $flattened);
                }
            }

            $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                ['module_name' => 'AddisDemoday']
            ], 'remove');

            if ($armas_balines) {
                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'credit_card'],
                    ['module_name' => 'local_payment_hipay'],
                    ['module_name' => 'klarnapayment']

                ], 'remove');
            }

            if ($armas) {
                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'paypal'],
                    ['module_name' => 'paypal_bnpl'],
                    ['module_name' => 'ps_cashondelivery'],
                    ['module_name' => 'credit_card'],
                    ['module_name' => 'local_payment_hipay'],
                    ['module_name' => 'klarnapayment']
                ], 'remove');
            }

            if ($armero) {
                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'paypal'],
                    ['module_name' => 'paypal_bnpl'],
                    ['module_name' => 'credit_card'],
                    ['module_name' => 'local_payment_hipay']
                ], 'remove');
            }
            if ($cartucho) {
                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'ps_cashondelivery'],
                    ['module_name' => 'credit_card'],
                    ['module_name' => 'local_payment_hipay'],
                    ['module_name' => 'klarnapayment']
                ], 'remove');
            }
            if ($licencia) {
                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'ps_cashondelivery'],
                    ['module_name' => 'banlendismart'],
                    ['module_name' => 'credit_card'],
                    ['module_name' => 'sequra'],
                    ['module_name' => 'paypal'],
                    ['module_name' => 'paypal_bnpl'],
                    ['module_name' => 'local_payment_hipay'],
                    ['module_name' => 'klarnapayment']
                ], 'remove');
            }
            if ($lottery || $card) {

                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'ps_cashondelivery'],
                    ['module_name' => 'banlendismart'],
                    ['module_name' => 'sequra'],
                    ['module_name' => 'paypal'],
                    ['module_name' => 'paypal_bnpl'],
                    ['module_name' => 'local_payment_hipay'],
                    ['module_name' => 'klarnapayment']
                ], 'remove');
            }


            if ($iso == 'es') {

                if ($totalCart < 300) {

                    $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                        ['module_name' => 'banlendismart']
                    ], 'remove');


                } elseif ($totalCart > 300) {

                    $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                        ['module_name' => 'sequra']
                    ], 'remove');

                } else {
                    $filteredOptions = $allPaymentOptions;
                }
            }
            elseif ($iso == 'pt') {

                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'banlendismart'],
                    //['module_name' => 'ps_cashondelivery']
                ], 'remove');

                if ($totalCart > 3000) {

                    $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                        ['module_name' => 'sequra']
                    ], 'remove');

                } else {
                    $filteredOptions = $allPaymentOptions;
                }

            }
            elseif ($iso == 'fr') {

                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'banlendismart'],
                    //['module_name' => 'ps_cashondelivery']
                ], 'remove');

                if ($totalCart > 3000) {

                    $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                        ['module_name' => 'sequra']
                    ], 'remove');

                } else {
                    $filteredOptions = $allPaymentOptions;
                }

            }elseif ($iso == 'it') {

                $allPaymentOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'banlendismart'],
                    //['module_name' => 'ps_cashondelivery']
                ], 'remove');

                if ($totalCart > 3000) {

                    $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                        ['module_name' => 'sequra']
                    ], 'remove');

                } else {
                    $filteredOptions = $allPaymentOptions;
                }

            } else {

                $filteredOptions = $this->processPaymentOptions($allPaymentOptions, [
                    ['module_name' => 'banlendismart'],
                    ['module_name' => 'sequra'],
                    ['module_name' => 'ps_cashondelivery'],
                    //['module_name' => 'inespay'],
                ], 'remove');

            }

        } // End of else block for paid orders

        // Convert PaymentOption objects to arrays for Smarty template AFTER filtering
        $convertedOptions = [];
        if (!empty($filteredOptions)) {
            foreach ($filteredOptions as $option) {
                if (is_object($option)) {
                    // Convert PaymentOption object to array
                    $convertedOptions[] = [
                        'id' => method_exists($option, 'getId') ? $option->getId() : (isset($option->id) ? $option->id : uniqid('payment-')),
                        'module_name' => method_exists($option, 'getmodule_name') ? $option->getmodule_name() : (isset($option->module_name) ? $option->module_name : ''),
                        'call_to_action_text' => method_exists($option, 'getCallToActionText') ? $option->getCallToActionText() : (isset($option->call_to_action_text) ? $option->call_to_action_text : 'Pay'),
                        'logo' => method_exists($option, 'getLogo') ? $option->getLogo() : (isset($option->logo) ? $option->logo : ''),
                        'form' => method_exists($option, 'getForm') ? $option->getForm() : (isset($option->form) ? $option->form : ''),
                        'additionalInformation' => method_exists($option, 'getAdditionalInformation') ? $option->getAdditionalInformation() : (isset($option->additionalInformation) ? $option->additionalInformation : ''),
                        'action' => method_exists($option, 'getAction') ? $option->getAction() : (isset($option->action) ? $option->action : ''),
                        'inputs' => method_exists($option, 'getInputs') ? $option->getInputs() : (isset($option->inputs) ? $option->inputs : [])
                    ];
                } elseif (is_array($option)) {
                    // Already an array, just ensure required keys exist
                    $convertedOptions[] = array_merge([
                        'id' => uniqid('payment-'),
                        'module_name' => '',
                        'call_to_action_text' => 'Pay',
                        'logo' => '',
                        'form' => '',
                        'additionalInformation' => '',
                        'action' => '',
                        'inputs' => []
                    ], $option);
                }
            }
        }

        $filteredOptions = $convertedOptions;

        $totalCart = $cart['totals']['total']['amount'];
        $iso = strtolower($this->context->language->iso_code);
        $lottery = $cart['lottery'];
        $card = $cart['card'];
        $armero = $cart['armero'];
        $cartucho = $cart['cartucho'];
        $armas = $cart['armas'];
        $armas_balines = $cart['armas_balines'];


        $pickup_time = null;
        if ($id_carrier == (int)Configuration::get('KB_PICKUP_AT_STORE_SHIPPING')) {
            // $this->context->controller->addJS($this->_path . '/views/js/front/kb_front.js');

            $pickup = Db::getInstance()->getRow('SELECT * FROM ' . _DB_PREFIX_ . 'kb_pickup_at_store_time
                    WHERE id_cart=' . $id_cart . '
                    AND id_shop=' . (int) $this->context->shop->id . '
                    AND id_customer=' . $id_customer);

            if ($pickup) {
                $pickup_time = $pickup['preferred_date'];
            }
        }


        if (isset($this->selected_payment_option)) {
            $selected_payment_option = $this->selected_payment_option;
        } else {
            $selected_payment_option = NULL;
        }

        $store = null;
        $selectedDeliveryOption = 0;

        if (is_array($deliveryOptionKeyList)) {
            foreach ($deliveryOptionKeyList as $addressDeliveryId => $selectedOptionKey) {
                if (isset($deliveryOptionsList[$addressDeliveryId][$selectedOptionKey])) {
                    $selectedDeliveryOption = $deliveryOptionsList[$addressDeliveryId][$selectedOptionKey];
                    break; // Solo agarramos el primero en caso normal de una dirección
                }
            }
        }

        $deliveryOptions = $this->getDeliveryOptions();

        //$conditionsToApprove = $this->conditionsToApproveFinder->getConditionsToApproveForTemplate();

        $deliveryOptionKey = $context->cart->getDeliveryOption(null, false, false);

        $customer = $context->customer;
        $addresses = $customer->getAddresses($context->language->id);

        $customer->addresses = [];
        foreach ($addresses as $addr) {
            $customer->addresses[$addr['id_address']] = $addr;
        }

        $deliveryOptionsList   = $context->cart->getDeliveryOptionList();
        $deliveryOptionKeyList = $context->cart->getDeliveryOption(null, false, false);

        $selectedDeliveryOption = ['name' => '', 'show_name' => ''];
        foreach ($deliveryOptionKeyList as $idAddress => $optKey) {
            if (isset($deliveryOptionsList[$idAddress][$optKey])) {
                $option = $deliveryOptionsList[$idAddress][$optKey];
                // Tomamos el primer carrier
                if (!empty($option['carrier_list'])) {
                    $carrierData      = reset($option['carrier_list']);
                    $carrierInstance  = $carrierData['instance'];
                    $selectedDeliveryOption['name'] = $carrierInstance->name;
                    $selectedDeliveryOption['show_name'] = isset($carrierInstance->show_name)?$carrierInstance->show_name[$context->language->id]:$carrierInstance->name;
                }
            }
            break;
        }

        $store       = null;
        $pickup_time = null;
        $idCart      = (int)$context->cart->id;
        $idCustomer  = (int)$context->customer->id;
        $pickIdConf  = (int)Configuration::get('KB_PICKUP_AT_STORE_SHIPPING');

        if ($context->cart->id_carrier == $pickIdConf) {
            $row = Db::getInstance()->getRow('
            SELECT * FROM `' . _DB_PREFIX_ . 'kb_pickup_at_store_time`
            WHERE id_cart = ' . $idCart . '
              AND id_shop = ' . (int)$context->shop->id . '
              AND id_customer = ' . $idCustomer
            );
            if ($row) {
                $pickup_time = $row['preferred_date'];
                if (!empty($row['id_store'])) {
                    // Asegúrate de que tu clase Store acepte idioma en el constructor
                    $store = new Store($row['id_store'], $context->language->id);
                }
            }
        }


        $addresses = $customer->getAddresses($context->language->id);
        $byId = [];
        foreach ($addresses as $addr) {
            $byId[$addr['id_address']] = $addr;
        }

        $deliveryAddress = $byId[$context->cart->id_address_delivery] ?? null;
        $invoiceAddress  = $byId[$context->cart->id_address_invoice]  ?? null;

        $context    = $this->context;
        $translator = $context->getTranslator();
        $finder = new \ConditionsToApproveFinderCore($context, $translator);
        $conditionsToApprove = $finder->getConditionsToApproveForTemplate();

        $data = [
            'id_lang' => $id_lang,
            'cart' => $cart,
            'customer' => $customer,
            'payment_options' => $filteredOptions,
            'need_invoice' => $this->context->cart->need_invoice,
            'conditions_to_approve' => $conditionsToApprove,
            'selected_payment_option' => $selected_payment_option,
            'selected_delivery_option' => $selectedDeliveryOption,
            'show_final_summary' => Configuration::get('PS_FINAL_SUMMARY_ENABLED'),
            'store' => $store,
            'pickup_time' => $pickup_time,
            'is_free' => $isFree,
            'delivery_address'    => $deliveryAddress,
            'invoice_address'     => $invoiceAddress,
        ];
        $template = 'module:alsernetshopping/views/templates/front/checkout/view/payment.tpl';
        $this->context->smarty->assign($data);
        return $this->context->smarty->fetch($template);

    }

    public function steppayment()
    {

        $context = $this->context;
        $customer = $this->customer;
        $cart = $context->cart;

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'warning',
                'message' => $this->l('Unauthorized access.'),
                'data' => [],
            ];
        }

        $id_carrier = (int)Tools::getValue('id_carrier');
        $id_address = $cart->id_address_delivery;

        return [
            'status' => 'success',
            'id_carrier' => $id_carrier,
            'id_address' => $id_address,
        ];

    }

    public function setpayment()
    {
        $context = $this->context;
        $cart = $this->cart;
        $customer = $this->customer;

        $id_carrier = (int)Tools::getValue('id_carrier');
        $type = Tools::getValue('type'); // 'delivery' o 'invoice'

        if (!$customer || !$customer->isLogged()) {
            return [
                'status' => 'error',
                'message' => $this->trans(
                    'You must be logged in.',
                    [],
                    'Shop.Notifications.Error',
                    $context->language->locale
                ),
                'data' => []
            ];
        }

        $cart->id_carrier = $id_carrier;
        $cart->update();

        return [
            'status' => 'success',
            'message' => $this->l('Address updated successfully.'),
            'data' => [
                'type' => $type
            ]
        ];
    }

    private function processPaymentSelection()
    {
        $payment_method = Tools::getValue('payment_method');
        $accept_terms = Tools::getValue('accept_terms');
        $cart = $this->cart;
        $language =  $this->language;
        $cart->id_payment = $payment_method;

        // Check if order is free (total = 0)
        $isFree = (float)$cart->getOrderTotal(true, Cart::BOTH) === 0.0;

        if (Configuration::get('PS_CONDITIONS') && !$accept_terms) {
            return [
                'status' => 'error',
                'message' => $this->l('You must accept the terms and conditions.')
            ];
        }

        // Only require payment method if order is not free
        if (!$isFree && !$payment_method) {
            return [
                'status' => 'error',
                'message' => $this->l('Please select a payment method.')
            ];
        }

        if ($cart->update()) {
            return [
                'status' => 'success',
                'message' => $this->l('Payment method saved successfully.'),
                'next_step' => 'summary'
            ];
        } else {
            return [
                'status' => 'error',
                'message' => $this->l('Error saving payment method.')
            ];
        }

    }

    private function getDeliveryOptions(callable $callback = null)
    {
        $context = $this->context;
        $cart = $this->cart;
        $priceFormatter = new PriceFormatter();
        $objectPresenter = new ObjectPresenter();

        $finder = new DeliveryOptionsFinderCore(
            $context,
            $context->getTranslator(),
            $objectPresenter,
            $priceFormatter
        );

        $carriers_available = $finder->getDeliveryOptions();

        if (Module::isEnabled('kbgcstorelocatorpickup')) {
            $id_carrier_pickup_gc = (int)Configuration::get('KB_GC_PICKUP_AT_STORE_SHIPPING');
            $id_feature_product_type = (int)Configuration::get('BAN_PRODUCT_FEATURE_ID_PRODUCT_TYPE');
            $id_feature_value_product_type_pickup_gc = Configuration::get('BAN_PRODUCT_FEATURE_VALUE_ID_LIST_PRODUCT_TYPE_PICKUP_GC');

            if ($id_carrier_pickup_gc && $id_feature_product_type && $id_feature_value_product_type_pickup_gc) {
                $is_pickup_gc = false;

                $products_list = $cart->getProducts();
                foreach ($products_list as $product) {
                    if (!empty($product['features'])) {
                        foreach ($product['features'] as $feature) {
                            if ((int)$feature['id_feature'] === $id_feature_product_type &&
                                strpos(',' . $id_feature_value_product_type_pickup_gc . ',', ',' . $feature['id_feature_value'] . ',') !== false
                            ) {
                                $is_pickup_gc = true;
                                break 2;
                            }
                        }
                    }
                }

                foreach ($carriers_available as $key => $carrier) {
                    if ((string)$key === (string)$id_carrier_pickup_gc) {
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

        foreach ($carriers_available as $carrierId => &$carrier) {
            $carrier['name'] = isset($carrier['name']) ? $carrier['name'] : 'Carrier #' . (int)$carrierId;

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
                default:
                    $carrier['analytic'] = $callback ? $callback($carrier, $carrierId, $carrier['name']) : 'Otro método de envío';
                    break;
            }
        }

        $carrier_selected = $cart->getDeliveryOption(null, false, true);
        $is_carrier_selected_in_list = false;

        foreach ($carrier_selected as $selectedCarrierId) {
            foreach ($carriers_available as $key => $value) {
                if ((string)$key === (string)$selectedCarrierId) {
                    $is_carrier_selected_in_list = true;
                    break;
                }
            }
        }

        if (!$is_carrier_selected_in_list && !empty($carriers_available)) {
            $firstKey = array_key_first($carriers_available);
            $cart->setDeliveryOption([
                $cart->id_address_delivery => $firstKey,
            ]);
            $cart->update();
        }

        return $carriers_available;
    }

    private function filterPaymentOptions(array $options, array $conditions): array
    {
        return array_filter($options, function ($option) use ($conditions) {
            foreach ($conditions as $condition) {
                $isValid = true;
                foreach ($condition as $key => $value) {

                    if (is_array($value)) {
                        if (!isset($option[$key]) || !in_array($option[$key], $value)) {
                            $isValid = false;
                            break;
                        }
                    } else {

                        if (!isset($option[$key]) || $option[$key] !== $value) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    return true;
                }
            }
            return false;
        });
    }

    private function removePaymentOptions(array $options, array $conditions): array
    {
        return array_filter($options, function ($option) use ($conditions) {
            foreach ($conditions as $condition) {
                $isValid = true;
                foreach ($condition as $key => $value) {

                    if (is_array($value)) {
                        if (!isset($option[$key]) || !in_array($option[$key], $value)) {
                            $isValid = false;
                            break;
                        }
                    } else {

                        if (!isset($option[$key]) || $option[$key] !== $value) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    return false;
                }
            }

            return true;
        });
    }

    private function processPaymentOptions(array $options, array $conditions, string $action = 'filter'): array
    {
        if (!in_array($action, ['filter', 'remove'], true)) {
            throw new InvalidArgumentException('El valor de acción debe ser "filter" o "remove".');
        }

        $getValue = function ($option, string $key) {
            // Arrays
            if (is_array($option)) {
                return array_key_exists($key, $option) ? $option[$key] : null;
            }

            // Objetos: intentar getter getKey()
            if (is_object($option)) {
                // module_name -> getmodule_name
                $method = 'get' . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $key)));
                if (method_exists($option, $method)) {
                    return $option->{$method}();
                }

                // Fallback: propiedad pública (si existiera)
                if (property_exists($option, $key)) {
                    return $option->{$key};
                }
            }

            return null;
        };

        $filtered = array_filter($options, function ($option) use ($conditions, $action, $getValue) {
            foreach ($conditions as $condition) {
                $isValid = true;

                foreach ($condition as $key => $expected) {
                    $actual = $getValue($option, $key);

                    if (is_array($expected)) {
                        // "IN" semantics
                        if (!in_array($actual, $expected, true)) {
                            $isValid = false;
                            break;
                        }
                    } else {
                        if ($actual !== $expected) {
                            $isValid = false;
                            break;
                        }
                    }
                }

                if ($isValid) {
                    // Coincide con una condición
                    return $action === 'filter'; // true => conservar, false => eliminar
                }
            }

            // No coincidió con ninguna condición
            return $action === 'remove'; // true => conservar (cuando quitamos coincidencias)
        });

        // Reindexar
        return array_values($filtered);
    }

    private function isPaymentModuleAllowedForCountry(string $moduleId): bool
    {
        $module = Module::getInstanceByName($moduleId);

        if (!$module || !$module->active) {
            return false;
        }

        // Obtener el país de la dirección de facturación
        $billingAddress = new Address($this->context->cart->id_address_invoice);
        if (!Validate::isLoadedObject($billingAddress)) {
            return false;
        }

        $countryId = $billingAddress->id_country;

        // Verificar restricciones de país en la tabla ps_module_country
        $sql = 'SELECT 1 FROM `' . _DB_PREFIX_ . 'module_country` mc
                WHERE mc.id_module = ' . (int)$module->id . '
                AND mc.id_country = ' . (int)$countryId . '
                AND mc.id_shop = ' . (int)$this->context->shop->id;

        $result = Db::getInstance()->getValue($sql);

        // Si hay registro en module_country, el módulo está permitido para este país
        // Si no hay registros, asumir que está permitido (por defecto PrestaShop permite todos)
        $isAllowed = !empty($result) || $this->isModuleAllowedByDefault($moduleId);

        if (!$isAllowed) {
            error_log("🚫 Module '{$moduleId}' blocked for country ID: {$countryId}");
        }

        return $isAllowed;
    }

    /**
     * Verificar si un módulo está permitido por defecto (cuando no hay restricciones específicas)
     */
    private function isModuleAllowedByDefault(string $moduleId): bool
    {
        // Verificar si hay alguna restricción configurada para este módulo
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'module_country` mc
                WHERE mc.id_module = (SELECT id_module FROM `' . _DB_PREFIX_ . 'module` WHERE name = "' . pSQL($moduleId) . '")
                AND mc.id_shop = ' . (int)$this->context->shop->id;

        $restrictionsCount = Db::getInstance()->getValue($sql);

        // Si no hay restricciones configuradas, el módulo está permitido por defecto
        return empty($restrictionsCount);
    }

    /**
     * Load HiPay media resources (CSS/JS) if the module is active
     * Calls both hipayActionFrontControllerSetMedia and hookDisplayBackOfficeHeader methods
     */
    private function loadHipayMedia()
    {
        try {
            // Check if HiPay Enterprise module is installed and active
            if (Module::isInstalled('hipay_enterprise') && Module::isEnabled('hipay_enterprise')) {
                $hipayModule = Module::getInstanceByName('hipay_enterprise');

                if ($hipayModule) {
                    $loadedResources = [];

                    // Call HiPay's front controller media loading method (SDK resources)
                    if (method_exists($hipayModule, 'hipayActionFrontControllerSetMedia')) {
                        // Set context controller to order for HiPay media loading
                        $originalPhpSelf = $this->context->controller->php_self;
                        $this->context->controller->php_self = 'order';

                        $hipayModule->hipayActionFrontControllerSetMedia();

                        // Restore original php_self
                        $this->context->controller->php_self = $originalPhpSelf;

                        $loadedResources[] = 'hipayActionFrontControllerSetMedia (SDK)';
                    }

                    // Call HiPay's back office header hook (admin CSS/JS resources)
                    if (method_exists($hipayModule, 'hookDisplayBackOfficeHeader')) {
                        $hipayModule->hookDisplayBackOfficeHeader();
                        $loadedResources[] = 'hookDisplayBackOfficeHeader (admin resources)';
                    }

                    // if (!empty($loadedResources)) {
                    //     error_log('✅ HiPay media resources loaded in CheckoutPaymentController: ' . implode(', ', $loadedResources));
                    // } else {
                    //     error_log('⚠️ HiPay module found but required methods not available');
                    // }
                }
                // else {
                //     error_log('⚠️ HiPay module instance could not be created');
                // }
            }
            // else {
            //     error_log('ℹ️ HiPay Enterprise module not installed or not active');
            // }
        } catch (Exception $e) {
            // Log error but don't break the checkout process
            error_log('🚫 Error loading HiPay media: ' . $e->getMessage());
        }
    }



}






