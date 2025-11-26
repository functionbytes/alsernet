<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once dirname(__FILE__) . '/BaseController.php';
require_once dirname(__FILE__) . '/../../classes/CheckoutValidationService.php';
include_once(dirname(__FILE__) . '/../front/Checkout/CheckoutAuthenticationController.php');

use Checkout\CheckoutAddressController;
use Checkout\CheckoutDeliveryController;
use Checkout\CheckoutPaymentController;
use Checkout\CheckoutAuthenticationController;

use PrestaShop\PrestaShop\Adapter\Presenter\Cart\CartPresenter;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductListingPresenter;

class CheckoutController extends BaseController
{
    private $current_step = "login";
    private $productsblocked = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function init()
    {
        $context = $this->context;
        $cart = $this->context->cart;
        $currency = $this->context->currency;
        $iso = Tools::getValue('iso') ?: $this->context->language->iso_code;
        $isLogged   = (bool) $this->context->customer->isLogged();
        $stepData = $this->getStep();

        $cartPresent = (new CartPresenter($this->context))->present($cart, false);

        // Agregar need_invoice al contexto del carrito
        $needInvoice = CheckoutValidationService::checkNeedInvoiceByProductType($cart)
            || CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart);

        $this->enforceInvoiceAddressState($cart, $needInvoice);

        $cartPresent['need_invoice'] = $needInvoice;

        $products = $cartPresent['products']?? [];
        $subtotals = $cartPresent['subtotals'] ?? [];
        $totals = $cartPresent['totals'] ?? [];
        $labels = $cartPresent['labels'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $discounts = $cartPresent['discounts'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $total_discounts = 0;

        if (!empty($vouchers['added']) && is_array($vouchers['added'])) {
            foreach ($vouchers['added'] as $coupon) {
                $formatted_value = $coupon['reduction_formatted'] ?? '0';
                $numeric_value = str_replace(',', '.', preg_replace('/[^0-9,-]/', '', $formatted_value));
                $total_discounts += (float) $numeric_value;
            }
        }

        $total_discounts_value = abs($total_discounts);
        $total_discounts= Tools::displayPrice($total_discounts_value, $currency);

        $configuration = [
            'display_prices_tax_incl' => Configuration::get('PS_TAX_DISPLAY'),
            'taxes_enabled' => Configuration::get('PS_TAX'),
            'guest_allowed' => (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
        ];

        $is_virtual = $cartPresent['is_virtual'];
        $productAmount = $cartPresent['subtotals']['products']['amount'];
        $restaIds = [65104, 65102];
        $resta = false;

        foreach ($cartPresent['products'] as &$product) {
            $productId = (int) $product['id'];

            if (in_array($productId, $restaIds)) {
                $resta = true;
            }

            $productObj = new Product($productId, false);
            $product['url_product'] = $context->link->getProductLink($productObj, null, null, null);
        }

        unset($product);

        if ($resta) {
            $productAmount -= 25;
        }

        $freeShippingThreshold = 99;
        $amountRemaining = max(0, $freeShippingThreshold - $productAmount);
        $percentage = min(99, round(($productAmount / $freeShippingThreshold) * 99, 2));

        $shipping_progress = [
            'active'           => !$is_virtual && $productAmount < $freeShippingThreshold,
            'resta_applied'    => $resta,
            'adjusted_amount'  => $productAmount,
            'amount_remaining' => $amountRemaining,
            'percentage'       => $percentage,
        ];

        $translations = [
            'cart' => $this->l('Cart','checkoutcontroller'),
            'view_cart' => $this->l('View cart','checkoutcontroller'),
            'checkout' => $this->l('Checkout purchase','checkoutcontroller'),
            'missing_for_free_shipping' => $this->l('Missing','checkoutcontroller'),
            'free_shipping_message' => $this->l('for FREE SHIPPING.','checkoutcontroller'),
            'iva_message' => $this->l('VAT FREE DAYS','checkoutcontroller'),
            'iva_message_additional' => $this->l('Get a DISCOUNT VOUCHER for your next purchase.','checkoutcontroller'),
            'order_summary' => $this->l('Order Summary','checkoutcontroller'),
            'with_this_purchase_you_save' => $this->l('With this purchase you save','checkoutcontroller'),
            'vat_discount_day' => $this->l('VAT discount day','checkoutcontroller'),
            'i_have_a' => $this->l('I have a','checkoutcontroller'),
            'promotional_code' => $this->l('promotional code','checkoutcontroller'),
            'or' => $this->l('or','checkoutcontroller'),
            'gift_card' => $this->l('gift card','checkoutcontroller'),
            'have_a_promo_code' => $this->l('Have a promo code?','checkoutcontroller'),
            'apply' => $this->l('Apply','checkoutcontroller'),
            'close' => $this->l('Close','checkoutcontroller'),
            'proceed_to_checkout' => $this->l('Proceed to checkout','checkoutcontroller'),
            'discount_voucher_message' => $this->l('Discount voucher message','checkoutcontroller'),
            'enter_coupon_code_here' => $this->l('Enter coupon code here...','checkoutcontroller'),
            'enter_verification_code_here' => $this->l('Enter coupon verification here...','checkoutcontroller'),
            'change_blockcart' => $this->l('Change address','checkoutcontroller')
        ];

        $context->smarty->assign([
            'step'          => $stepData['step'],
            'authentication'=> $isLogged ?? false,
            'cart' => $cartPresent,
            'products' => $products,
            'subtotals' => $subtotals,
            'totals' => $totals,
            'vouchers' => $vouchers['added'],
            'discounts' => $discounts,
            'total_discounts' => $total_discounts,
            'shippingprogress' => $shipping_progress,
            'labels' => $labels,
            'currency' => $currency,
            'configuration' => $configuration,
            'translations' => $translations,
        ]);


        return [
            'status' => 'success',
            'authentication'=> $isLogged ?? false,
            'shipping' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/shipping.tpl'),
            'summary' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/summary.tpl'),
        ];
    }

    public function validations()
    {
        $this->context = Context::getContext(); // Get fresh context

        $context = $this->context;
        $cart = $this->context->cart;


        $isLogged = $this->context->customer->isLogged();

        // SIMPLE AND DIRECT: If customer has valid ID and email, consider authenticated
        $hasValidCustomer = (
            $this->context->customer->id > 0 &&
            !empty($this->context->customer->email) &&
            Validate::isEmail($this->context->customer->email)
        );


        // User is authenticated if either isLogged() returns true OR we have valid customer data
        $isAuthenticated = $isLogged || $hasValidCustomer;


        // Additional debug - check cookie values directly
        $cookieValues = [];
        foreach ($_COOKIE as $key => $value) {
            if (strpos($key, 'PrestaShop') !== false) {
                $cookieValues[$key] = $value;
            }
        }

        $iso = Tools::getValue('iso') ?: $this->context->language->iso_code;
        $id_lang = Language::getIdByIso($iso);

        // Si no está logueado NI tiene auth guest válida, no validar
        if (!$isAuthenticated) {
            return $this->buildSuccessResponse($iso, []);
        }


        // Verificar si ya ejecutamos esta validación recientemente (evitar duplicación)
        static $lastValidation = null;
        $cartSignature = md5($cart->id . '_' . $cart->date_upd . '_' . $cart->id_address_delivery . '_' . $cart->id_address_invoice);

        if ($lastValidation && $lastValidation['signature'] === $cartSignature && (time() - $lastValidation['time']) < 3) {
            // Usar resultado en caché si es reciente (menos de 3 segundos)
            return $lastValidation['result'];
        }

        // USAR VALIDATION SERVICE CENTRALIZADO - PUNTO ÚNICO DE VALIDACIÓN
        $validationResult = CheckoutValidationService::validateCheckout($cart, $context, $iso);

        // Guardar resultado en caché
        $result = $validationResult['hasError'] ?
            $this->handleValidationError($validationResult['data'], $context, $iso) :
            $this->buildSuccessResponse($iso, []);

        $lastValidation = [
            'signature' => $cartSignature,
            'time' => time(),
            'result' => $result
        ];

        return $result;

    }

    public function ensureSingleAddressAsDelivery(Cart $cart, $alsoSetInvoice = true)
    {
        if (!$cart || !$cart->id || !$cart->id_customer) {
            return false;
        }

        // 1) Buscar direcciones activas del cliente (no eliminadas)
        $sql = 'SELECT a.id_address
            FROM '._DB_PREFIX_.'address a
            WHERE a.id_customer = '.(int)$cart->id_customer.'
              AND a.deleted = 0
              AND a.active = 1
            ORDER BY a.id_address ASC
            LIMIT 2'; // con 2 sabemos si hay exactamente 1

        $addresses = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        if (!is_array($addresses) || count($addresses) !== 1) {
            return false;
        }

        $idAddress = (int)$addresses[0]['id_address'];


        $addr = new Address($idAddress);
        if (!Validate::isLoadedObject($addr) || (int)$addr->id_customer !== (int)$cart->id_customer) {
            return false;
        }

        $changed = false;

        if ((int)$cart->id_address_delivery !== $idAddress) {
            $cart->id_address_delivery = $idAddress;
            $changed = true;
        }

        if ($changed) {
            if (!$cart->update()) {
                return false;
            }

            // 6) Sincronizar cart_product: asegurar el mismo id_address_delivery
            Db::getInstance()->update(
                'cart_product',
                ['id_address_delivery' => $idAddress],
                'id_cart = '.(int)$cart->id.' AND (id_address_delivery IS NULL OR id_address_delivery = 0 OR id_address_delivery != '.$idAddress.')'
            );

            return true;
        }

        return false; // no había nada que cambiar
    }

    private function sanitizeCartAddresses(Cart $cart)
    {
        if (!$cart || !$cart->id || !$cart->id_customer) {
            return;
        }

        foreach (['id_address_delivery', 'id_address_invoice'] as $field) {
            $idAddress = (int) $cart->$field;
            if ($idAddress > 0) {
                $addr = new \Address($idAddress);
                $invalid =
                    !Validate::isLoadedObject($addr) ||
                    (int)$addr->id_customer !== (int)$cart->id_customer ||
                    (int)$addr->deleted === 1 ||
                    (int)$addr->active !== 1;

                if ($invalid) {
                    $cart->$field = 0;
                }
            }
        }

        $cart->update();
    }

    private function handleValidationError($errorData, $context, $iso)
    {

        $step = $context->cart->step;
        $cart = $context->cart;
        $errorType = $errorData['type'] ?? null;
        $isLogged = $this->customer->isLogged();

        // Apply same direct validation logic as in validations()
        $hasValidCustomer = (
            $this->context->customer->id > 0 &&
            !empty($this->context->customer->email) &&
            Validate::isEmail($this->context->customer->email)
        );

        $isAuthenticated = $isLogged || $hasValidCustomer;

        if (!$errorType) {
            return $this->buildErrorResponse($iso, 'Invalid error data structure');
        }

        $modalHtml = '';

        $isVirtual = $cart->isVirtualCart();
        $this->ensureSingleAddressAsDelivery($cart, /*alsoSetInvoice*/ true, /*forceSameIfVirtual*/ $isVirtual);
        $this->sanitizeCartAddresses($cart);

        switch ($errorType) {
            case CheckoutValidationService::ERROR_MISSING_DELIVERY_ADDRESS:
                $context->smarty->assign([
                    'type' => $errorType,
                    'missing_address_delivery' => 1
                ]);
                $modalHtml = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/modal/missingdelivery.tpl');
                break;

            case CheckoutValidationService::ERROR_MISSING_INVOICE_ADDRESS:
                $context->smarty->assign([
                    'type' => $errorType,
                    'missing_address_invoice' => 1
                ]);
                $modalHtml = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/modal/missinginvoice.tpl');
                break;

            case CheckoutValidationService::ERROR_MISSING_VAT:
                $context->smarty->assign($errorData);
                $modalHtml = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/modal/vatnumber.tpl');
                break;

            case CheckoutValidationService::ERROR_BLOCKED_PRODUCTS:

                $cartPresent = (new CartPresenter($this->context))->present($this->context->cart, false);

                // Agregar need_invoice al contexto del carrito
                $needInvoice = CheckoutValidationService::checkNeedInvoiceByProductType($this->context->cart)
                    || CheckoutValidationService::checkNeedInvoiceByOrderTotal($this->context->cart);

                // Actualizar el campo need_invoice en el carrito
                $this->context->cart->need_invoice = $needInvoice;
                $this->context->cart->save();

                $cartPresent['need_invoice'] = $needInvoice;

                $translations = [
                    'title_blockcart' => $this->l('This products can not be sent to your delivery address','checkoutcontroller'),
                    'delete_blockcart' => $this->l('Delete product in cart','checkoutcontroller'),
                    'change_blockcart' => $this->l('Change address','checkoutcontroller')
                ];

                $context->smarty->assign([
                    'translations' => $translations,
                    'blockeds' => $errorData['blocked_products'],
                    'cart' => $cartPresent,
                ]);

                $modalHtml = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/modal/block.tpl');
                break;

            case CheckoutValidationService::ERROR_PRODUCT_AVAILABILITY:

                $products = $cart->getProducts(true);

                foreach ($products as $product) {
                    $productObj = new Product($product['id_product']);

                    if (!$productObj->checkQty($product['cart_quantity'])) {


                        $context->smarty->assign([
                            'error_cart' => $cart->id,
                            'error_product' => $product['id_product'],
                            'error_attribute' => $product['id_product_attribute'],
                            'error_name' => $product['name'],
                            'error_quantity' => $product['cart_quantity'],
                            'error_stock' => Product::getQuantity($product['id_product'])
                        ]);

                        $modalHtml = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/modal/error.tpl');

                    }
                }



                break;

            case CheckoutValidationService::ERROR_NO_ADDRESSES_REGISTERED:

                $modalHtml = $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/modal/no-addresses.tpl');
                break;
        }

        return [
            'status' => 'success',
            'step' => $step,
            'authentication' => $isAuthenticated,
            'errors' => [
                'hasError' => true,
                'type' => $errorType,
                'messages' => isset($errorData['message']) ? [$errorData['message']] : [],
                'modal_html' => $modalHtml,
            ]
        ];
    }

    /**
     * Construye una respuesta de error estándar
     */
    private function buildErrorResponse($iso, $message, $errorType = null)
    {
        return [
            'success' => false,
            'error' => true,
            'message' => $message,
            'type' => $errorType,
            'modal_html' => null,
            'translations' => [
                'error_generic' => $this->l('An error occurred','checkoutcontroller'),
            ]
        ];
    }

    /**
     * Construye una respuesta exitosa estándar
     */
    private function buildSuccessResponse($iso, $errors)
    {
        // Force refresh the complete context to get updated authentication state
        $context = $this->context;
        $step = $this->cart->step;
        $isLogged = $this->customer->isLogged();

        // Apply same direct validation logic as in validations()
        $hasValidCustomer = (
            $this->context->customer->id > 0 &&
            !empty($this->context->customer->email) &&
            Validate::isEmail($this->context->customer->email)
        );

        $isAuthenticated = $isLogged || $hasValidCustomer;

        $translations = [
            'cart' => $this->l('Cart','checkoutcontroller'),
            'view_cart' => $this->l('View cart','checkoutcontroller'),
            'checkout' => $this->l('Checkout purchase','checkoutcontroller'),
            'missing_for_free_shipping' => $this->l('Missing','checkoutcontroller'),
            'free_shipping_message' => $this->l('for FREE SHIPPING.','checkoutcontroller'),
            'iva_message' => $this->l('VAT FREE DAYS','checkoutcontroller'),
            'iva_message_additional' => $this->l('Get a DISCOUNT VOUCHER for your next purchase.','checkoutcontroller'),
            'order_summary' => $this->l('Order Summary','checkoutcontroller'),
            'with_this_purchase_you_save' => $this->l('With this purchase you save','checkoutcontroller'),
            'vat_discount_day' => $this->l('VAT discount day','checkoutcontroller'),
            'i_have_a' => $this->l('I have a','checkoutcontroller'),
            'promotional_code' => $this->l('promotional code','checkoutcontroller'),
            'or' => $this->l('or','checkoutcontroller'),
            'gift_card' => $this->l('gift card','checkoutcontroller'),
            'have_a_promo_code' => $this->l('Have a promo code?','checkoutcontroller'),
            'apply' => $this->l('Apply','checkoutcontroller'),
            'close' => $this->l('Close','checkoutcontroller'),
            'proceed_to_checkout' => $this->l('Proceed to checkout','checkoutcontroller'),
            'discount_voucher_message' => $this->l('Discount voucher message','checkoutcontroller'),
            'enter_coupon_code_here' => $this->l('Enter coupon code here...','checkoutcontroller'),
            'enter_verification_code_here' => $this->l('Enter coupon verification here...','checkoutcontroller'),
            'change_blockcart' => $this->l('Change address','checkoutcontroller')
        ];

        $context->smarty->assign([
            'authentication' => $isAuthenticated,
            'translations' => $translations,
        ]);

        return [
            'context' => $this->context,
            'step' => $step,
            'authentication' => $isAuthenticated,
            'status' => 'success',
            'errors' => $errors
        ];
    }

    public function step()
    {
        $context = $this->context;
        $cart = $this->context->cart;
        $isLogged = $this->context->customer->isLogged();
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);
        $context->language = new Language($id_lang);

        $requested_step = Tools::getValue('step', null);

        if (!$requested_step) {
            $requested_step = get_current_checkout_step_from_session();
        }

        $html_content =  '';
        switch ($requested_step) {
            case 'login':
                $html_content = $this->viewLogin();
                break;
            case 'address':
                $html_content = $this->viewAddress();
                break;
            case 'delivery':
                $html_content =  $this->viewDelivery();
                break;
            case 'payment':
                $html_content =  $this->viewPayment();
                break;
        }

        $this->current_step =  $requested_step;

        return [
            'logged'=> $isLogged ?? false,
            'context' => $context,
            'current_step' => $this->current_step,
            'status' => 'success',
            'step'   => $requested_step, // Devuelve el paso que se renderizó
            'html'   => $html_content,
            'error'  => false,
            'empty'  => ($this->context->cart->nbProducts() == 0)
        ];

    }


    public function steps()
    {
        $stepData = $this->getStep();

        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);
        $context->language = new Language($id_lang);
        $currency = new Currency($cart->id_currency);
        $cartPresent = (new CartPresenter)->present($cart, false, $id_lang);

        // Agregar need_invoice al contexto del carrito
        $needInvoice = CheckoutValidationService::checkNeedInvoiceByProductType($cart)
            || CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart);

        $this->enforceInvoiceAddressState($cart, $needInvoice);

        $cartPresent['need_invoice'] = $needInvoice;

        return [
            'context' => $context,
            'cart' => $cartPresent,
            'status' => 'success',
            'step' => $stepData['step'],
            'html' => $stepData['html'],
            'error' => $stepData['error'] ?? false,
            'empty' => $stepData['empty'] ?? false,
            'authentication' => $stepData['authentication'] ?? false
        ];

    }



    public function deletecoupon()
    {

        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);
        $rule = trim(Tools::getValue('rule'));
        $this->context->cart->removeCartRule($rule);
        CartRule::autoAddToCart($this->context);

        return [
            'status' => 'success',
            'message' => $this->l('Success','checkoutcontroller'),
        ];

    }

    public function coupon()
    {
        $context = $this->context;
        $cart = $context->cart;
        $code = trim(Tools::getValue('coupon'));
        $verifcode = trim(Tools::getValue('confirmation'));
        $iso = Tools::getValue('iso') ?: $this->context->language->iso_code;

        if (!$cart->id) {
            return [
                'status' => 'warning',
                'message' => $this->l('Cart not found.','checkoutcontroller'),
            ];
        }

        if (!$code) {
            return [
                'status' => 'warning',
                'message' => $this->l('You must enter a code.','checkoutcontroller'),
            ];
        }

        foreach ($cart->getCartRules() as $cartRule) {
            if (!($cr = new CartRule($cartRule['id_cart_rule'])) || !Validate::isLoadedObject($cr)) {
                continue;
            }
            if ($error = $cr->checkValidity($context, false, true)) {
                $cart->removeCartRule($cartRule['id_cart_rule']);
            }
        }

        if (!$cart->canApplyCartRule()) {
            return [
                'status' => 'warning',
                'message' => $this->l('No more coupons can be applied.','checkoutcontroller'),
            ];
        }

        if (!empty($verifcode)) {

            if (!class_exists('AlvarezERP')) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('ERP module not available.','checkoutcontroller'),
                ];
            }

            $cart_total = $cart->getOrderTotal();
            $bono = AlvarezERP::consultabono($code, $verifcode, $cart_total, AlvarezERP::BONO_ORIGEN_WEB);

            if (!$bono || !$bono['success']) {
                return [
                    'status' => 'warning',
                    'message' => $this->l($bono['message'] ?? 'Invalid code or not found.','checkoutcontroller'),
                ];
            }

            $data = $bono['data'] ?? null;
            if (!$data || !isset($data['estado_extendido'])) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Invalid coupon data.','checkoutcontroller'),
                ];
            }

            $estado = (int) $data['estado_extendido'];
            if ($estado === 0) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('This coupon is disabled.','checkoutcontroller'),
                ];
            }
            if ($estado === 2 || $estado === 3) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('This coupon has expired or has already been used.','checkoutcontroller'),
                ];
            }

            if (strtotime($data['fvalidez_desde']) > time()) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('This coupon is not yet valid.','checkoutcontroller'),
                ];
            }

            if (strtotime($data['fvalidez_hasta']) < time()) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('This coupon has expired.','checkoutcontroller'),
                ];
            }

            $importe_minimo = isset($data['importeminimoventa']) ? (float) $data['importeminimoventa'] : 0;
            if ($importe_minimo > $cart_total) {
                $msg = sprintf($this->l('You do not reach the minimum amount of %s to use this coupon.','checkoutcontroller'), Tools::displayPrice($importe_minimo));
                return [
                    'status' => 'warning',
                    'message' => $msg,
                ];
            }

            foreach ($cart->getProducts() as $product) {
                if (!empty($product['is_virtual'])) {
                    return [
                        'status' => 'warning',
                        'message' => $this->l('The Lottery does not allow discounts.','checkoutcontroller'),
                    ];
                }
            }

            $importe = isset($data['importe']) ? (float) $data['importe'] : 0;
            $cartRuleId = CartRule::createCartRuleAlvarez($code, $verifcode, $importe, $cart_total, $importe_minimo, $data, $context);

            if (!$cartRuleId || !(Validate::isLoadedObject($cr = new CartRule($cartRuleId)))) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('Error generating the coupon.','checkoutcontroller'),
                ];
            }

            $cart->addCartRule($cr->id);

        } else {


            if (!Validate::isCleanHtml($code)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('The code is not valid.','checkoutcontroller'),
                ];
            }

            if (Module::isEnabled('quantitydiscountpro')) {
                include_once _PS_MODULE_DIR_ . 'quantitydiscountpro/quantitydiscountpro.php';
                $quantityDiscount = new QuantityDiscountRule(QuantityDiscountRule::getQuantityDiscountRuleByCode($code));

                if (Validate::isLoadedObject($quantityDiscount)) {
                    if ($quantityDiscount->createAndRemoveRules($code) !== true) {
                        return [
                            'status' => 'warning',
                            'message' => $this->l('The code is invalid.','checkoutcontroller'),
                        ];
                    }
                }
            }

            $cartRule = new CartRule(CartRule::getIdByCode($code));
            if (!Validate::isLoadedObject($cartRule)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l('This coupon does not exist.','checkoutcontroller'),
                ];
            }

            if ($error = $cartRule->checkValidity($context, false, true)) {
                return [
                    'status' => 'warning',
                    'message' => $this->l($error,'checkoutcontroller'),
                ];
            }

            $cart->addCartRule($cartRule->id);
        }


        return [
            'status' => 'success',
            'message' => $this->l('Success','checkoutcontroller'),
        ];
    }

    public function getStep()
    {

        $cart = $this->context->cart;
        $customer = $this->context->customer;

        $iso = Tools::getValue('iso') ?: $this->context->language->iso_code;

        $needInvoice = CheckoutValidationService::checkNeedInvoiceByProductType($cart)
            || CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart);
        $this->enforceInvoiceAddressState($cart, $needInvoice);

        $step = '';
        $html = '';

        // PRIMER PASO: Verificar autenticación
        // Si el usuario NO está logueado, solo mostrar el paso de login
        if (!$customer->isLogged()) {
            $step = 'login';
            $html = $this->viewLogin();

            return [
                'step' => $step,
                'html' => $html,
                'authentication' => true
            ];
        }

        // VALIDACIONES POSTERIORES - Solo si el usuario está autenticado

        // Verificar que el carrito no esté vacío
        $products = $cart->nbProducts();

        if (empty($products)) {
            return [
                'step' => 'login',// Redirigir al login si el carrito está vacío
                'html' => $this->viewLogin(),
                'empty' => true,
                'error' => $this->l('Your cart is empty', 'checkoutcontroller', $iso)
            ];
        }

        $isAvailable = $this->areProductsAvailable();

        if (true !== $isAvailable) {
            return [
                'step' => 'login',
                'html' => $this->viewLogin(),
                'error' => $isAvailable
            ];
        }

        // SEGUNDO PASO: Verificar direcciones de entrega
        if (!(int) $cart->id_address_delivery) {

            $step = 'addresses';
            $html = $this->viewAddress();
            return [
                'step' => $step,
                'html' => $html
            ];
        }

        // Verificar dirección de facturación si es necesaria
        if ($this->needInvoiceAddress($cart) && !(int) $cart->id_address_invoice) {
            $step = 'addresses';
            $html = $this->viewAddress();
            return [
                'step' => $step,
                'html' => $html
            ];
        }


        // TERCER PASO: Verificar transportista para carritos no virtuales
        if (!$cart->isVirtualCart() && !(int) $cart->id_carrier) {
            $step = 'delivery';
            $html = $this->viewDelivery();
            return [
                'step' => $step,
                'html' => $html
            ];
        }


        // CUARTO PASO: Verificar método de pago
        if (!$this->hasValidPaymentMethod($cart)) {
            $step = 'payment';
            $html = $this->viewPayment();
            return [
                'step' => $step,
                'html' => $html
            ];
        }
        // QUINTO PASO: Si todo está completo, mostrar resumen
        $step = 'summary';
        $summaryResult = $this->stepsummary();
        $html = $summaryResult['summary'] ?? '';
        return [
            'step' => $step,
            'html' => $html,
            'error' => $summaryResult['error'] ?? false,
            'empty' => $summaryResult['empty'] ?? false
        ];
    }


    public function viewLogin()
    {
        $login = new CheckoutAuthenticationController();
        return $login->init();
    }

    public function viewLogins()
    {
        $context = $this->context;
        $customer = $context->customer;
        $iso = Tools::getValue('iso') ?: $context->language->iso_code;
        $id_lang = Language::getIdByIso($iso);

        $isLogged = $customer->isLogged();
        $showLoginForm = !$isLogged;
        $customerArray = [];

        if ($isLogged) {
            $customerArray = $customer->getFields();
            $customerArray['is_logged'] = true;
            $customerArray['is_guest'] = $customer->is_guest;
            $customerArray['firstname'] = $customer->firstname;
            $customerArray['lastname'] = $customer->lastname;
            $customerArray['email'] = $customer->email;
            $customerArray['birthday'] = $customer->birthday;
            $customerArray['newsletter'] = $customer->newsletter;
            $customerArray['optin'] = $customer->optin;
        }

        $checkoutUrls = [
            'pages' => [
                'identity' => $context->link->getPageLink('identity', null, $id_lang),
                'order' => $context->link->getPageLink('order', null, $id_lang),
                'authentication' => $context->link->getPageLink('authentication', null, $id_lang),
                'register' => $context->link->getPageLink('authentication', null, $id_lang, 'create_account=1'),
                'my_account' => $context->link->getPageLink('my-account', null, $id_lang),
            ],
            'actions' => [
                'logout' => $context->link->getPageLink('index', true, $id_lang, 'mylogout'),
                'login' => '/modules/alsernetshopping/controllers/routes.php?modalitie=checkout&action=authlogin&iso=' . $iso,
                'register' => '/modules/alsernetshopping/controllers/routes.php?modalitie=checkout&action=authregister&iso=' . $iso,
                'password' =>  $this->context->link->getPageLink('password', true, $this->context->language->id)
            ],
        ];

        $configuration = [
            'guest_allowed' => (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'empty_cart_on_logout' => !Configuration::get('PS_CART_FOLLOWING'),
            'account_creation_required' => !Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
            'password_policy' => [
                'length' => Configuration::get('PS_PASSWD_MIN_LENGTH') ?: 8,
                'score' => Configuration::get('PS_PASSWD_SCORE') ?: 2,
            ]
        ];

        $translations = [
            'sign_in' => $this->l('Sign in', 'checkoutcontroller', $iso),
            'create_account' => $this->l('Create an account', 'checkoutcontroller', $iso),
            'email' => $this->l('Email', 'checkoutcontroller', $iso),
            'password' => $this->l('Password', 'checkoutcontroller', $iso),
            'forgot_password' => $this->l('Forgot your password?', 'checkoutcontroller', $iso),
            'sign_in_button' => $this->l('Sign in', 'checkoutcontroller', $iso),
            'create_account_button' => $this->l('Create account', 'checkoutcontroller', $iso),
            'or' => $this->l('or', 'checkoutcontroller', $iso),
            'continue_as_guest' => $this->l('Continue as guest', 'checkoutcontroller', $iso),
            'guest_checkout_info' => $this->l('You can create an account after your purchase', 'checkoutcontroller', $iso),
            'already_have_account' => $this->l('Already have an account?', 'checkoutcontroller', $iso),
            'no_account_yet' => $this->l('No account yet?', 'checkoutcontroller', $iso),
            'firstname' => $this->l('First name', 'checkoutcontroller', $iso),
            'lastname' => $this->l('Last name', 'checkoutcontroller', $iso),
            'birthday' => $this->l('Birthdate', 'checkoutcontroller', $iso),
            'newsletter' => $this->l('Sign up for our newsletter', 'checkoutcontroller', $iso),
            'privacy_policy' => $this->l('I agree to the privacy policy', 'checkoutcontroller', $iso),
            'terms_conditions' => $this->l('I agree to the terms and conditions', 'checkoutcontroller', $iso),
        ];

        // Información del carrito para validaciones
        $cart = $context->cart;
        $cartInfo = [
            'id' => $cart->id,
            // 'products_count' => $cart->getNbProducts(),
            'total' => $cart->getOrderTotal(),
            //'is_empty' => !$cart->getNbProducts(),
        ];

        // Mensajes de error si existen
        $errors = [];
        if (Tools::getValue('login_error')) {
            $errors[] = $this->l('Authentication failed.', 'checkoutcontroller', $iso);
        }
        if (Tools::getValue('create_account_error')) {
            $errors[] = $this->l('An error occurred while creating your account.', 'checkoutcontroller', $iso);
        }

        $context->smarty->assign([
            'authenticated' => $isLogged,
            'show_login_form' => $showLoginForm,
            'customer' => $customerArray,
            'urls' => $checkoutUrls,
            'configuration' => $configuration,
            'translations' => $translations,
            'cart_info' => $cartInfo,
            'errors' => $errors,
            'iso' => $iso,
            'id_lang' => $id_lang,
            'current_step' => 'login',
            'checkout_session' => [
                'token' => Tools::getToken(false),
                'time' => time(),
            ],
        ]);

        return $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/view/login.tpl');
    }

    private function calculateShippingProgress($cartPresented)
    {
        $is_virtual = $cartPresented['is_virtual'];
        $productAmount = $cartPresented['subtotals']['products']['amount'];
        $freeShippingThreshold = 99;

        $amountRemaining = max(0, $freeShippingThreshold - $productAmount);
        $percentage = min(99, round(($productAmount / $freeShippingThreshold) * 99, 2));

        return [
            'active' => !$is_virtual && $productAmount < $freeShippingThreshold,
            'adjusted_amount' => $productAmount,
            'amount_remaining' => $amountRemaining,
            'percentage' => $percentage,
        ];
    }

    public function stepsummary()
    {

        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);
        $currency = new Currency($cart->id_currency);

        $cartPresent = (new CartPresenter)->present($cart, false, $id_lang);

        $needInvoice = CheckoutValidationService::checkNeedInvoiceByProductType($cart)
            || CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart);

        $this->enforceInvoiceAddressState($cart, $needInvoice);
        $cartPresent['need_invoice'] = $needInvoice;

        $shipping_progress = $this->calculateShippingProgress($cartPresent);

        $products = $cartPresent['products']?? [];
        $subtotals = $cartPresent['subtotals'] ?? [];
        $totals = $cartPresent['totals'] ?? [];
        $labels = $cartPresent['labels'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $discounts = $cartPresent['discounts'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $total_discounts = 0;

        if (!empty($vouchers['added']) && is_array($vouchers['added'])) {
            foreach ($vouchers['added'] as $coupon) {
                $formatted_value = $coupon['reduction_formatted'] ?? '0';
                $numeric_value = str_replace(',', '.', preg_replace('/[^0-9,-]/', '', $formatted_value));
                $total_discounts += (float) $numeric_value;
            }
        }

        $total_discounts_value = abs($total_discounts);
        $total_discounts= Tools::displayPrice($total_discounts_value, $currency);

        $configuration = [
            'display_prices_tax_incl' => Configuration::get('PS_TAX_DISPLAY'),
            'taxes_enabled' => Configuration::get('PS_TAX'),
            'guest_allowed' => (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
        ];

        $is_virtual = $cartPresent['is_virtual'];
        $productAmount = $cartPresent['subtotals']['products']['amount'];
        $restaIds = [65104, 65102];
        $resta = false;

        foreach ($cartPresent['products'] as &$product) {
            $productId = (int) $product['id'];

            if (in_array($productId, $restaIds)) {
                $resta = true;
            }

            $productObj = new Product($productId, false, $id_lang);
            $product['url_product'] = $context->link->getProductLink($productObj, null, null, null, $id_lang);
        }

        unset($product);

        if ($resta) {
            $productAmount -= 25;
        }

        $freeShippingThreshold = 99;
        $amountRemaining = max(0, $freeShippingThreshold - $productAmount);
        $percentage = min(99, round(($productAmount / $freeShippingThreshold) * 99, 2));
        $discount = $this->calculateDiscount($products);


        $translations = [
            'cart' => $this->l('Cart','checkoutcontroller'),
            'view_cart' => $this->l('View cart','checkoutcontroller'),
            'checkout' => $this->l('Checkout purchase','checkoutcontroller'),
            'missing_for_free_shipping' => $this->l('Missing','checkoutcontroller'),
            'free_shipping_message' => $this->l('for FREE SHIPPING.','checkoutcontroller'),
            'iva_message' => $this->l('VAT FREE DAYS','checkoutcontroller'),
            'iva_message_additional' => $this->l('Get a DISCOUNT VOUCHER for your next purchase.','checkoutcontroller'),
            'order_summary' => $this->l('Order Summary','checkoutcontroller'),
            'with_this_purchase_you_save' => $this->l('With this purchase you save','checkoutcontroller'),
            'vat_discount_day' => $this->l('VAT discount day','checkoutcontroller'),
            'i_have_a' => $this->l('I have a','checkoutcontroller'),
            'promotional_code' => $this->l('promotional code','checkoutcontroller'),
            'or' => $this->l('or','checkoutcontroller'),
            'gift_card' => $this->l('gift card','checkoutcontroller'),
            'have_a_promo_code' => $this->l('Have a promo code?','checkoutcontroller'),
            'apply' => $this->l('Apply','checkoutcontroller'),
            'close' => $this->l('Close','checkoutcontroller'),
            'proceed_to_checkout' => $this->l('Proceed to checkout','checkoutcontroller'),
            'discount_voucher_message' => $this->l('Discount voucher message','checkoutcontroller'),
            'enter_coupon_code_here' => $this->l('Enter coupon code here...','checkoutcontroller'),
            'enter_verification_code_here' => $this->l('Enter coupon verification here...','checkoutcontroller'),
        ];



        $context->smarty->assign([
            'id_cart' => $cart->id,
            'cart' => $cartPresent,
            'products' => $products,
            'subtotals' => $subtotals,
            'totals' => $totals,
            'vouchers' => $vouchers['added'],
            'discounts' => $discounts,
            'total_save' => $discount,
            'discount' => $discount,
            'total_discounts' => $total_discounts,
            'shippingprogress' => $shipping_progress,
            'labels' => $labels,
            'currency' => $currency,
            'configuration' => $configuration,
            'translations' => $translations,
            'checkout' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
        ]);


        return [
            'status' => 'success',
            'shipping' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/shipping.tpl'),
            'summary' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/summary.tpl'),
        ];

    }

    private function calculateDiscount($products)
    {
        $discount = 0;
        foreach ($products as $product) {
            $discount += (float)($product['reduction'] ?? 0)*$product['quantity'];
        }
        return number_format(ceil($discount * 100) / 100, 2, '.', '');
    }

    public function load()
    {

        $context = $this->context;
        $cart = $context->cart;
        $iso = Tools::getValue('iso');
        $id_lang = Language::getIdByIso($iso);
        $currency = new Currency($cart->id_currency);

        $cartPresent = (new CartPresenter)->present($cart, false, $id_lang);

        $needInvoice = CheckoutValidationService::checkNeedInvoiceByProductType($cart)
            || CheckoutValidationService::checkNeedInvoiceByOrderTotal($cart);

        $this->enforceInvoiceAddressState($cart, $needInvoice);
        $cartPresent['need_invoice'] = $needInvoice;

        $shipping_progress = $this->calculateShippingProgress($cartPresent);

        $products = $cartPresent['products']?? [];
        $subtotals = $cartPresent['subtotals'] ?? [];
        $totals = $cartPresent['totals'] ?? [];
        $labels = $cartPresent['labels'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $discounts = $cartPresent['discounts'] ?? [];
        $vouchers = $cartPresent['vouchers'] ?? [];
        $total_discounts = 0;

        if (!empty($vouchers['added']) && is_array($vouchers['added'])) {
            foreach ($vouchers['added'] as $coupon) {
                $formatted_value = $coupon['reduction_formatted'] ?? '0';
                $numeric_value = str_replace(',', '.', preg_replace('/[^0-9,-]/', '', $formatted_value));
                $total_discounts += (float) $numeric_value;
            }
        }

        $total_discounts_value = abs($total_discounts);
        $total_discounts= Tools::displayPrice($total_discounts_value, $currency);

        $configuration = [
            'display_prices_tax_incl' => Configuration::get('PS_TAX_DISPLAY'),
            'taxes_enabled' => Configuration::get('PS_TAX'),
            'guest_allowed' => (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED'),
        ];

        $is_virtual = $cartPresent['is_virtual'];
        $productAmount = $cartPresent['subtotals']['products']['amount'];
        $restaIds = [65104, 65102];
        $resta = false;

        foreach ($cartPresent['products'] as &$product) {
            $productId = (int) $product['id'];

            if (in_array($productId, $restaIds)) {
                $resta = true;
            }

            $productObj = new Product($productId, false, $id_lang);
            $product['url_product'] = $context->link->getProductLink($productObj, null, null, null, $id_lang);
        }

        unset($product);

        if ($resta) {
            $productAmount -= 25;
        }

        $freeShippingThreshold = 99;
        $amountRemaining = max(0, $freeShippingThreshold - $productAmount);
        $percentage = min(99, round(($productAmount / $freeShippingThreshold) * 99, 2));


        $translations = [
            'cart' => $this->l('Cart','checkoutcontroller'),
            'view_cart' => $this->l('View cart','checkoutcontroller'),
            'checkout' => $this->l('Checkout purchase','checkoutcontroller'),
            'missing_for_free_shipping' => $this->l('Missing','checkoutcontroller'),
            'free_shipping_message' => $this->l('for FREE SHIPPING.','checkoutcontroller'),
            'iva_message' => $this->l('VAT FREE DAYS','checkoutcontroller'),
            'iva_message_additional' => $this->l('Get a DISCOUNT VOUCHER for your next purchase.','checkoutcontroller'),
            'order_summary' => $this->l('Order Summary','checkoutcontroller'),
            'with_this_purchase_you_save' => $this->l('With this purchase you save','checkoutcontroller'),
            'vat_discount_day' => $this->l('VAT discount day','checkoutcontroller'),
            'i_have_a' => $this->l('I have a','checkoutcontroller'),
            'promotional_code' => $this->l('promotional code','checkoutcontroller'),
            'or' => $this->l('or','checkoutcontroller'),
            'gift_card' => $this->l('gift card','checkoutcontroller'),
            'have_a_promo_code' => $this->l('Have a promo code?','checkoutcontroller'),
            'apply' => $this->l('Apply','checkoutcontroller'),
            'close' => $this->l('Close','checkoutcontroller'),
            'proceed_to_checkout' => $this->l('Proceed to checkout','checkoutcontroller'),
            'discount_voucher_message' => $this->l('Discount voucher message','checkoutcontroller'),
            'enter_coupon_code_here' => $this->l('Enter coupon code here...','checkoutcontroller'),
            'enter_verification_code_here' => $this->l('Enter coupon verification here...','checkoutcontroller'),
        ];

        $context->smarty->assign([
            'id_cart' => $cart->id,
            'cart' => $cartPresent,
            'products' => $products,
            'subtotals' => $subtotals,
            'totals' => $totals,
            'vouchers' => $vouchers['added'],
            'discounts' => $discounts,
            'total_discounts' => $total_discounts,
            'shippingprogress' => $shipping_progress,
            'labels' => $labels,
            'currency' => $currency,
            'configuration' => $configuration,
            'translations' => $translations,
            'checkout' => $this->context->link->getPageLink('order', null, $id_lang, false, null, true),
        ]);

        return [
            'status' => 'success',
            'shipping' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/shipping.tpl'),
            'summary' => $context->smarty->fetch('module:alsernetshopping/views/templates/front/checkout/partials/summary.tpl'),
        ];

    }


    private function enforceInvoiceAddressState(Cart $cart, bool $needInvoice): void
    {
        // Persistir flag en el carrito
        $cart->need_invoice = (int) $needInvoice;

        // Si no se requiere factura, eliminamos cualquier address_invoice previa
        if (!$needInvoice && (int) $cart->id_address_invoice !== 0) {
            $cart->id_address_invoice = 0;
        }

        // Guardamos solo si hubo cambios relevantes (para evitar writes innecesarios)
        $cart->save();
    }
    public function viewAddress()
    {
        $address = new CheckoutAddressController();
        return $address->init();
    }

    public function viewDelivery()
    {

        $controllerDelivery = new CheckoutDeliveryController();
        return $controllerDelivery->init();
    }

    public function viewPayment()
    {
        $controllerPayment = new CheckoutPaymentController();
        return $controllerPayment->init();
    }


    private function needInvoiceAddress($cart)
    {
        // Usar ValidationService centralizado
        return CheckoutValidationService::needsInvoiceAddress($cart);
    }

    /**
     * Verifica si tiene un método de pago válido seleccionado
     */
    private function hasValidPaymentMethod($cart)
    {

        // Si es un carrito gratuito, no necesita método de pago
        if ((float)$cart->getOrderTotal(true, Cart::BOTH) === 0.0) {
            return true;
        }

        // Verificar si hay algún método de pago seleccionado
        $selectedPayment = Tools::getValue('payment_method') ?:
            $this->context->cookie->payment_method ?:
                null;

        return !empty($selectedPayment);
    }


    /**
     * @deprecated Usar CheckoutValidationService::checkNeedInvoiceByProductType() directamente
     */
    protected function checkNeedInvoiceByProductTypeInCart() {
        return CheckoutValidationService::checkNeedInvoiceByProductType($this->context->cart);
    }

    /**
     * @deprecated Usar CheckoutValidationService::checkNeedInvoiceByOrderTotal() directamente
     */
    protected function checkNeedInvoiceByOrderTotal(): bool
    {
        return CheckoutValidationService::checkNeedInvoiceByOrderTotal($this->context->cart);
    }

    /**
     * @deprecated Usar CheckoutValidationService::checkNeedDNIByProductType() directamente
     */
    protected function checkNeedDNIByProductTypeInCart(): bool
    {
        return CheckoutValidationService::checkNeedDNIByProductType($this->context->cart);
    }

    /**
     * @deprecated Usar CheckoutValidationService::checkNeedDNIByCategory() directamente
     */
    protected function checkNeedDNIByCategoryInCart(): bool
    {
        return CheckoutValidationService::checkNeedDNIByCategory($this->context->cart);
    }

    /**
     * Método legacy para compatibilidad hacia atrás
     * Ahora delega toda la lógica al método consolidado validations()
     *
     * @deprecated Usar validations() directamente
     */
    public function getCartErrors($iso = null)
    {
        $iso = $iso ?: $this->context->language->iso_code;

        // Delegar al método principal consolidado
        $validationsResult = $this->validations();

        // Si hay errores en validations(), retornar en formato esperado por getCartErrors
        if (isset($validationsResult['errors']) && !empty($validationsResult['errors'])) {
            return $validationsResult['errors'];
        }

        // Sin errores
        return ['hasError' => false];
    }


    /**
     * @deprecated Usar CheckoutValidationService::checkNeedDNIByCountry() directamente
     */
    protected function checkNeedDNIByCountry(): bool
    {
        return CheckoutValidationService::checkNeedDNIByCountry($this->context->cart);
    }

    /**
     * @deprecated Usar CheckoutValidationService::hasValidVATNumber() directamente
     */
    protected function checkVatNumberIfNeedInvoice(): bool
    {
        return CheckoutValidationService::hasValidVATNumber($this->context->cart);
    }

    /**
     * @deprecated Usar CheckoutValidationService::getBlockedProducts() directamente
     */
    private function checkBlocks(): array
    {
        $this->productsblocked = CheckoutValidationService::getBlockedProducts(
            $this->context->cart,
            $this->context->language->id
        );

        return $this->productsblocked;
    }

    /**
     * @deprecated Usar CheckoutValidationService::needsInvoiceAddress() directamente
     */
    private function checkRequirements()
    {
        return [
            'need_invoice' => CheckoutValidationService::needsInvoiceAddress($this->context->cart),
            'need_dni' => CheckoutValidationService::needsInvoiceAddress($this->context->cart)
        ];
    }

    /**
     * @deprecated Usar CheckoutValidationService::checkProductsAvailability() directamente
     */
    private function areProductsAvailable()
    {
        return CheckoutValidationService::checkProductsAvailability($this->context->cart, $this->context);
    }



    public function l($string, $specific = false, $locale = null){

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